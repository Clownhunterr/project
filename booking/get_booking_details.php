<?php
// ============================================================
//  booking/get_booking_details.php
//  AJAX endpoint to retrieve details of a specific booking
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require '../database/db.php';

$bookingId = (int)($_GET['booking_id'] ?? 0);

if (!$bookingId) {
    echo json_encode(['success' => false, 'error' => 'Missing booking ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.barcode_value, b.total_amount, b.status,
               st.show_date, st.show_time,
               m.title
        FROM bookings b
        JOIN showtimes st ON b.showtime_id = st.showtime_id
        JOIN movies m ON st.movie_id = m.movie_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }

    // Fetch seats
    $stmtSeats = $pdo->prepare("
        SELECT s.seat_row, s.seat_number
        FROM booking_seats bs
        JOIN seats s ON bs.seat_id = s.seat_id
        WHERE bs.booking_id = ?
    ");
    $stmtSeats->execute([$bookingId]);
    $seats = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);

    // Format date: e.g. "Mon 7"
    $ts = strtotime($booking['show_date']);
    $dayName = date('D', $ts);
    $dayNum = date('j', $ts);

    // Format time: e.g. "17:30"
    $time = date('H:i', strtotime($booking['show_time']));

    echo json_encode([
        'success' => true,
        'booking' => [
            'title' => $booking['title'],
            'date' => [
                'day' => $dayName,
                'num' => $dayNum,
                'fullDate' => $booking['show_date']
            ],
            'time' => $time,
            'barcode' => $booking['barcode_value'],
            'seats' => array_map(function($s) {
                return [
                    'row' => $s['seat_row'],
                    'seatNumber' => (int)$s['seat_number']
                ];
            }, $seats)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
