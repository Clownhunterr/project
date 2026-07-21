<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="static-page.css" />
    <title>CineBooking | About Us</title>
</head>

<body>

    <header>
        <a href="index.php" class="logo">CineBooking</a>
        <form class="search" method="GET" action="index.php">
            <input type="text" name="search" placeholder="Search movies, genres..." />
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <?php if ($isLoggedIn): ?>
            <a href="profile/profile.php" class="profile-btn"><i class="fa-solid fa-circle-user"></i>
                <?php echo htmlspecialchars($_SESSION['name']); ?></a>
        <?php else: ?>
            <a href="login/login.php" class="auth-btn">Login / Register</a>
        <?php endif; ?>
    </header>

    <main class="static-page">
        <h1>About CineBooking</h1>
        <p class="lead">CineBooking is a simple, fast way to browse movies, check showtimes, and book your seats online
            — no queues, no hassle.</p>

        <div class="static-grid">
            <div class="static-block">
                <i class="fa-solid fa-ticket"></i>
                <h3>Easy Booking</h3>
                <p>Pick a movie, choose your seats, and get a digital ticket in under a minute.</p>
            </div>
            <div class="static-block">
                <i class="fa-solid fa-heart"></i>
                <h3>Your Wishlist</h3>
                <p>Save movies you're excited about and keep track of them in your profile.</p>
            </div>
            <div class="static-block">
                <i class="fa-solid fa-shield-halved"></i>
                <h3>Secure Accounts</h3>
                <p>Your details are protected with modern password hashing and secure sessions.</p>
            </div>
        </div>

        <h2>Our Story</h2>
        <p>CineBooking started as a student project built to explore how a real-world ticket booking platform comes
            together — from database design to seat selection to admin tooling. It's a work in progress, and we're
            always adding new features.</p>
    </main>

    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3 class="footer-logo">CineBooking</h3>
                <p>Your seat, your show, booked in seconds.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Now Showing</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Follow Us</h4>
                <ul class="footer-social">
                    <li><a href="#"><i class="fa-brands fa-facebook"></i></a></li>
                    <li><a href="#"><i class="fa-brands fa-twitter"></i></a></li>
                    <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 CineBooking. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>