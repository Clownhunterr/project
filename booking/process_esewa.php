<?php
// ============================================================
//  booking/process_esewa.php
//  Intercepts the return from eSewa, verifies the HMAC signature,
//  and if valid, marks the booking as confirmed and inserts seats.
// ============================================================
// Note: session_start() is already called in booking.php before including this

$dataParam = $_GET['data'] ?? '';
if (!$dataParam) {
    header('Location: ../index.php');
    exit;
}

$decoded = base64_decode($dataParam);
$parsed = json_decode($decoded, true);

if (!$parsed || !isset($parsed['status'])) {
    die('Invalid eSewa response format.');
}

if ($parsed['status'] !== 'COMPLETE') {
    die('Payment was not completed. <a href="../index.php">Return Home</a>');
}

// Verify Signature
$esewaSecret = '8gBm/:&EnhH.1/q';
$fieldNames = explode(',', $parsed['signed_field_names'] ?? '');
$messageParts = [];
foreach ($fieldNames as $field) {
    if (isset($parsed[$field])) {
        $messageParts[] = "{$field}={$parsed[$field]}";
    }
}
$message = implode(',', $messageParts);
$expectedSignature = base64_encode(hash_hmac('sha256', $message, $esewaSecret, true));

if ($expectedSignature !== ($parsed['signature'] ?? '')) {
    die('Security Error: Payment signature verification failed.');
}

// Payment is verified! Update the DB
require '../database/db.php';

$uuid = $parsed['transaction_uuid'] ?? '';
$bookingId = $_SESSION['pending_booking_id_' . $uuid] ?? null;
$seatIds = $_SESSION['pending_booking_seats_' . $uuid] ?? [];

if ($bookingId) {
    try {
        $pdo->beginTransaction();
        
        // Update booking to confirmed
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
        $stmt->execute([$bookingId]);

        // Insert seats
        $stmtSeat = $pdo->prepare("INSERT IGNORE INTO booking_seats (booking_id, seat_id) VALUES (?, ?)");
        foreach ($seatIds as $sid) {
            $stmtSeat->execute([$bookingId, $sid]);
        }

        $pdo->commit();

        // Get movie_id to redirect back to booking.php
        $stmtMovie = $pdo->prepare("SELECT st.movie_id FROM bookings b JOIN showtimes st ON b.showtime_id = st.showtime_id WHERE b.booking_id = ?");
        $stmtMovie->execute([$bookingId]);
        $movie = $stmtMovie->fetch();
        $movieId = $movie ? $movie['movie_id'] : 0;

        // Clear session data
        unset($_SESSION['pending_booking_id_' . $uuid]);
        unset($_SESSION['pending_booking_seats_' . $uuid]);

        // Redirect back to booking.php to show the ticket locally
        header('Location: booking.php?id=' . $movieId . '&show_ticket=1&booking_id=' . $bookingId);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die('Database error while confirming booking: ' . $e->getMessage());
    }
}

// Redirect to profile page's booking tab as fallback
header('Location: ../profile/profile.php#tab-bookings');
exit;
