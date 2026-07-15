<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login/login.html");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: /login/login.html");
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

$wishlist = [];
$wishlistReady = true;
try {
    $stmt = $pdo->prepare("
        SELECT w.wishlist_id, m.movie_id, m.title, m.genre, m.duration_minutes
        FROM wishlist w
        JOIN movies m ON w.movie_id = m.movie_id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ");
    $stmt->execute([$userId]);
    $wishlist = $stmt->fetchAll();
} catch (PDOException $e) {
    $wishlistReady = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="profile.css" />
    <title>CineBooking | My Profile</title>
</head>

<body>

    <header>
        <a href="/home.php" class="logo">CineBooking</a>
        <div class="search">
            <input type="text" placeholder="Search" />
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </div>
        <a href="profile.php" class="profile-btn active">
            <i class="fa-solid fa-circle-user"></i>
            <?php echo htmlspecialchars($user['name']); ?>
        </a>
    </header>

    <div class="profile-page">
        <aside class="profile-sidebar">
            <div class="sidebar-user">
                <i class="fa-solid fa-circle-user"></i>
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item active" data-tab="account">
                    <i class="fa-solid fa-id-card"></i> Account Overview
                </button>
                <button class="nav-item" data-tab="bookings">
                    <i class="fa-solid fa-ticket"></i> Booking History
                </button>
                <button class="nav-item" data-tab="wishlist">
                    <i class="fa-solid fa-heart"></i> Wishlist
                </button>
                <a href="logout.php" class="nav-item sign-out"
                    onclick="return confirm('Are you sure you want to sign out?');">
                    <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                </a>
            </nav>
        </aside>

        <main class="profile-content">

            <section class="tab-panel active" id="tab-account">
                <h2>Account Overview</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-label">Account Type</span>
                        <span class="info-value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-label">Member Since</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-label">Total Bookings</span>
                        <span class="info-value"><?php echo count($bookings); ?></span>
                    </div>
                </div>
            </section>

            <section class="tab-panel" id="tab-bookings">
                <h2>Booking History</h2>

                <?php if (count($bookings) === 0): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-ticket"></i>
                        <p>You haven't booked any tickets yet.</p>
                        <a href="/home.php" class="btn-primary">Browse Movies</a>
                    </div>
                <?php else: ?>
                    <div class="booking-list">
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-main">
                                    <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                    <p>
                                        <?php echo date('D, M j, Y', strtotime($booking['show_date'])); ?>
                                        at <?php echo date('g:i A', strtotime($booking['show_time'])); ?>
                                    </p>
                                    <p>
                                        <?php echo htmlspecialchars($booking['hall_name']); ?> •
                                        Seats: <?php echo htmlspecialchars($booking['seats'] ?: 'N/A'); ?>
                                    </p>
                                </div>
                                <div class="booking-side">
                                    <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                    </span>
                                    <p class="booking-amount">Rs. <?php echo number_format($booking['total_amount'], 2); ?></p>
                                    <p class="booking-code"><?php echo htmlspecialchars($booking['barcode_value']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tab-panel" id="tab-wishlist">
                <h2>Wishlist</h2>

                <?php if (!$wishlistReady): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-heart"></i>
                        <p>Wishlist is coming soon.</p>
                    </div>
                <?php elseif (count($wishlist) === 0): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-heart"></i>
                        <p>Your wishlist is empty.</p>
                        <a href="/home.php" class="btn-primary">Browse Movies</a>
                    </div>
                <?php else: ?>
                    <div class="wishlist-grid">
                        <?php foreach ($wishlist as $item): ?>
                            <div class="wishlist-card">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['genre']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <script src="profile.js"></script>
</body>

</html>