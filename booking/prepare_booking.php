<?php
// ============================================================
//  booking/prepare_booking.php
//  AJAX endpoint called by frontend to create a pending booking
//  before redirecting to eSewa. Generates the HMAC-SHA256 signature.
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require '../database/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$movieId = (int)($input['movie_id'] ?? 0);
$date    = $input['date'] ?? '';
$time    = $input['time'] ?? '';
$seats   = $input['seats'] ?? [];

if (!$movieId || !$date || !$time || empty($seats)) {
    echo json_encode(['success' => false, 'error' => 'Missing booking details']);
    exit;
}

// Ensure the movie exists in the DB, if not, create a placeholder for the fallback
try {
    $stmt = $pdo->prepare("SELECT movie_id FROM movies WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    if (!$stmt->fetch()) {
        // Fallback movie not in DB yet - we need it in the DB to make foreign keys work
        require '../includes/movie_functions.php';
        $fallback = null;
        foreach (getFallbackMovies() as $fb) {
            if ($fb['movie_id'] == $movieId) {
                $fallback = $fb;
                break;
            }
        }
        if ($fallback) {
            $stmt = $pdo->prepare("INSERT INTO movies (movie_id, title, genre, duration_minutes, poster_image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$movieId, $fallback['title'], $fallback['genre'], $fallback['duration_minutes'], $fallback['poster_url']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid movie']);
            exit;
        }
    }

    // Ensure a Hall exists
    $stmt = $pdo->prepare("SELECT hall_id FROM halls LIMIT 1");
    $stmt->execute();
    $hall = $stmt->fetch();
    if (!$hall) {
        $pdo->query("INSERT INTO halls (hall_name, total_seats) VALUES ('Main Hall', 200)");
        $hallId = $pdo->lastInsertId();
    } else {
        $hallId = $hall['hall_id'];
    }

    // Ensure seats exist in DB
    $pdo->beginTransaction();
    $seatIds = [];
    $stmtCheckSeat = $pdo->prepare("SELECT seat_id FROM seats WHERE hall_id = ? AND seat_row = ? AND seat_number = ?");
    $stmtInsertSeat = $pdo->prepare("INSERT INTO seats (hall_id, seat_row, seat_number, seat_type) VALUES (?, ?, ?, 'regular')");
    
    foreach ($seats as $s) {
        $row = $s['row'];
        $num = (int)$s['seatNumber'];
        
        $stmtCheckSeat->execute([$hallId, $row, $num]);
        $seatRow = $stmtCheckSeat->fetch();
        if ($seatRow) {
            $seatIds[] = $seatRow['seat_id'];
        } else {
            $stmtInsertSeat->execute([$hallId, $row, $num]);
            $seatIds[] = $pdo->lastInsertId();
        }
    }

    // Ensure a Showtime exists
    $stmtCheckShowtime = $pdo->prepare("SELECT showtime_id FROM showtimes WHERE movie_id = ? AND hall_id = ? AND show_date = ? AND show_time = ?");
    $stmtCheckShowtime->execute([$movieId, $hallId, $date, $time . ':00']);
    $showtime = $stmtCheckShowtime->fetch();
    if ($showtime) {
        $showtimeId = $showtime['showtime_id'];
    } else {
        $stmtInsertShowtime = $pdo->prepare("INSERT INTO showtimes (movie_id, hall_id, show_date, show_time) VALUES (?, ?, ?, ?)");
        $stmtInsertShowtime->execute([$movieId, $hallId, $date, $time . ':00']);
        $showtimeId = $pdo->lastInsertId();
    }

    // Real-time double booking check inside transaction
    $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
    $stmtCheckBooked = $pdo->prepare("
        SELECT COUNT(*) AS booked_count
        FROM booking_seats bs
        JOIN bookings b ON bs.booking_id = b.booking_id
        WHERE b.showtime_id = ? AND b.status = 'confirmed' AND bs.seat_id IN ($placeholders)
    ");
    $params = array_merge([$showtimeId], $seatIds);
    $stmtCheckBooked->execute($params);
    $checkResult = $stmtCheckBooked->fetch();

    if ($checkResult && $checkResult['booked_count'] > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'One or more selected seats have already been booked by another user. Please choose different seats.']);
        exit;
    }

    // Create the pending booking
    $totalAmount = count($seats) * 560; // 560 per seat
    $uuid = 'CB' . time() . rand(1000, 9999); // transaction_uuid
    
    $stmtBooking = $pdo->prepare("INSERT INTO bookings (user_id, showtime_id, barcode_value, total_amount, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmtBooking->execute([$_SESSION['user_id'], $showtimeId, $uuid, $totalAmount]);
    $bookingId = $pdo->lastInsertId();

    // Insert pending seats to session so we can attach them after payment
    // (We only link them in `booking_seats` once confirmed, so they don't block others if payment fails)
    $_SESSION['pending_booking_seats_' . $uuid] = $seatIds;
    $_SESSION['pending_booking_id_' . $uuid] = $bookingId;

    $pdo->commit();

    // Generate eSewa Signature
    $esewaSecret = '8gBm/:&EnhH.1/q';
    $productCode = 'EPAYTEST';
    
    $message = "total_amount={$totalAmount},transaction_uuid={$uuid},product_code={$productCode}";
    $signature = base64_encode(hash_hmac('sha256', $message, $esewaSecret, true));

    echo json_encode([
        'success' => true,
        'transaction_uuid' => $uuid,
        'total_amount' => $totalAmount,
        'signature' => $signature,
        'product_code' => $productCode
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
