<?php
// ============================================================
//  booking/get_booked_seats.php
//  AJAX endpoint to retrieve already booked seats for a showtime
// ============================================================
session_start();
header('Content-Type: application/json');

require '../database/db.php';

$movieId = (int)($_GET['movie_id'] ?? 0);
$date    = $_GET['date'] ?? '';
$time    = $_GET['time'] ?? '';

if (!$movieId || !$date || !$time) {
    echo json_encode(['success' => false, 'error' => 'Missing movie, date, or time']);
    exit;
}

try {
    // We append :00 if time is only HH:MM
    if (strlen($time) === 5) {
        $time .= ':00';
    }

    $stmt = $pdo->prepare("
        SELECT s.seat_row, s.seat_number 
        FROM booking_seats bs
        JOIN seats s ON bs.seat_id = s.seat_id
        JOIN bookings b ON bs.booking_id = b.booking_id
        JOIN showtimes st ON b.showtime_id = st.showtime_id
        WHERE st.movie_id = ? 
          AND st.show_date = ? 
          AND st.show_time = ? 
          AND b.status = 'confirmed'
    ");
    $stmt->execute([$movieId, $date, $time]);
    $bookedSeats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'booked_seats' => array_map(function($seat) {
            return [
                'row' => $seat['seat_row'],
                'seat_number' => (int)$seat['seat_number']
            ];
        }, $bookedSeats)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
