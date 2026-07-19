<?php
require 'admin_auth_check.php';
require '../database/db.php';
require '../includes/movie_functions.php';

$totalTickets = $pdo->query("
    SELECT COUNT(*) AS c
    FROM booking_seats bs
    JOIN bookings b ON bs.booking_id = b.booking_id
    WHERE b.status = 'confirmed'
")->fetch()['c'];

$totalBuyers = $pdo->query("
    SELECT COUNT(DISTINCT user_id) AS c
    FROM bookings
    WHERE status = 'confirmed'
")->fetch()['c'];

$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) AS r
    FROM bookings
    WHERE status = 'confirmed'
")->fetch()['r'];

$totalUsers = $pdo->query("
    SELECT COUNT(*) AS c FROM users WHERE role = 'customer'
")->fetch()['c'];

$totalMovies = $pdo->query("SELECT COUNT(*) AS c FROM movies")->fetch()['c'];

// Top-selling movies. Revenue is computed with a correlated subquery at the
// booking level (not the seat level) so a booking with multiple seats doesn't
// inflate revenue when joined against booking_seats for the ticket count.
$topMovies = $pdo->query("
    SELECT m.movie_id, m.title,
           COUNT(bs.seat_id) AS tickets_sold,
           (
               SELECT COALESCE(SUM(b2.total_amount), 0)
               FROM bookings b2
               JOIN showtimes s2 ON b2.showtime_id = s2.showtime_id
               WHERE s2.movie_id = m.movie_id AND b2.status = 'confirmed'
           ) AS revenue
    FROM movies m
    JOIN showtimes s ON m.movie_id = s.movie_id
    JOIN bookings b ON s.showtime_id = b.showtime_id AND b.status = 'confirmed'
    JOIN booking_seats bs ON b.booking_id = bs.booking_id
    GROUP BY m.movie_id, m.title
    ORDER BY tickets_sold DESC
    LIMIT 5
")->fetchAll();

$adminName = $_SESSION['admin_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
    <title>CineBooking | Admin Dashboard</title>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="admin-layout">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-main">
            <h1 class="admin-page-title">Dashboard</h1>
            <p class="admin-welcome">Welcome back, <?php echo htmlspecialchars($adminName); ?></p>

            <div class="stat-grid">
                <div class="stat-card">
                    <i class="fa-solid fa-ticket"></i>
                    <div>
                        <span class="stat-value"><?php echo number_format($totalTickets); ?></span>
                        <span class="stat-label">Tickets Sold</span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-users"></i>
                    <div>
                        <span class="stat-value"><?php echo number_format($totalBuyers); ?></span>
                        <span class="stat-label">Paying Customers</span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <div>
                        <span class="stat-value">Rs. <?php echo number_format($totalRevenue, 2); ?></span>
                        <span class="stat-label">Total Revenue</span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-user-group"></i>
                    <div>
                        <span class="stat-value"><?php echo number_format($totalUsers); ?></span>
                        <span class="stat-label">Registered Users</span>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-film"></i>
                    <div>
                        <span class="stat-value"><?php echo number_format($totalMovies); ?></span>
                        <span class="stat-label">Movies Listed</span>
                    </div>
                </div>
            </div>

            <h2 class="admin-section-title">Highest Selling Movies</h2>

            <?php if (count($topMovies) === 0): ?>
                <div class="admin-empty">No confirmed bookings yet.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th>Tickets Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topMovies as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['title']); ?></td>
                                <td><?php echo number_format($m['tickets_sold']); ?></td>
                                <td>Rs. <?php echo number_format($m['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

</body>

</html>