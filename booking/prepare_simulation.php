<?php
// ============================================================
//  booking/prepare_simulation.php
//  AJAX endpoint to simulate a successful payment instantly.
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require '../database/db.php';

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

try {
    $stmt = $pdo->prepare("SELECT movie_id FROM movies WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    if (!$stmt->fetch()) {
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

    $stmt = $pdo->prepare("SELECT hall_id FROM halls LIMIT 1");
    $stmt->execute();
    $hall = $stmt->fetch();
    if (!$hall) {
        $pdo->query("INSERT INTO halls (hall_name, total_seats) VALUES ('Main Hall', 200)");
        $hallId = $pdo->lastInsertId();
    } else {
        $hallId = $hall['hall_id'];
    }

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

    $totalAmount = count($seats) * 560; 
    $uuid = 'SIM' . time() . rand(1000, 9999);
    
    // Create the confirmed booking instantly
    $stmtBooking = $pdo->prepare("INSERT INTO bookings (user_id, showtime_id, barcode_value, total_amount, status) VALUES (?, ?, ?, ?, 'confirmed')");
    $stmtBooking->execute([$_SESSION['user_id'], $showtimeId, $uuid, $totalAmount]);
    $bookingId = $pdo->lastInsertId();

    // Insert the seats directly
    $stmtSeat = $pdo->prepare("INSERT IGNORE INTO booking_seats (booking_id, seat_id) VALUES (?, ?)");
    foreach ($seatIds as $sid) {
        $stmtSeat->execute([$bookingId, $sid]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'redirect_url' => '../profile/profile.php#tab-bookings'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
