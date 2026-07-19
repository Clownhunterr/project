<?php
require 'admin_auth_check.php';
require '../database/db.php';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$userId = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: manage_users.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT b.booking_id, b.barcode_value, b.total_amount, b.status, b.checked_in, b.booked_at,
           m.title,
           s.show_date, s.show_time,
           h.hall_name,
           GROUP_CONCAT(CONCAT(st.seat_row, st.seat_number) SEPARATOR ', ') AS seats
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.showtime_id
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN halls h ON s.hall_id = h.hall_id
    LEFT JOIN booking_seats bs ON b.booking_id = bs.booking_id
    LEFT JOIN seats st ON bs.seat_id = st.seat_id
    WHERE b.user_id = ?
    GROUP BY b.booking_id, b.barcode_value, b.total_amount, b.status, b.checked_in, b.booked_at,
             m.title, s.show_date, s.show_time, h.hall_name
    ORDER BY b.booked_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
    <title>CineBooking | <?php echo htmlspecialchars($user['name']); ?></title>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="admin-layout">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-main">
            <a href="manage_users.php" class="btn-admin btn-admin-outline btn-sm"
                style="margin-bottom: 20px; display: inline-flex;">
                <i class="fa-solid fa-arrow-left"></i> Back to Users
            </a>

            <h1 class="admin-page-title"><?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="admin-welcome"><?php echo htmlspecialchars($user['email']); ?> &middot; Joined
                <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>

            <h2 class="admin-section-title">Booking History</h2>

            <?php if (count($bookings) === 0): ?>
                <div class="admin-empty">This user hasn't made any bookings yet.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th>Showtime</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Checked In</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['title']); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($b['show_date'])); ?>
                                    at <?php echo date('g:i A', strtotime($b['show_time'])); ?>
                                    (<?php echo htmlspecialchars($b['hall_name']); ?>)
                                </td>
                                <td><?php echo htmlspecialchars($b['seats'] ?: 'N/A'); ?></td>
                                <td>Rs. <?php echo number_format($b['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($b['status'])); ?></td>
                                <td><?php echo $b['checked_in'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

</body>

</html>