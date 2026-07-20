<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // No backend mail handling yet — this just acknowledges the submission for now.
    $submitted = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="static-page.css" />
    <title>CineBooking | Contact</title>
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
        <h1>Contact Us</h1>
        <p class="lead">Questions, feedback, or issues with a booking? Reach out below.</p>

        <div class="contact-layout">
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p>support@cinebooking.com</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <h4>Phone</h4>
                        <p>+977-1-4XXXXXX</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <h4>Address</h4>
                        <p>Kathmandu, Nepal</p>
                    </div>
                </div>
            </div>

            <form class="contact-form" method="POST">
                <?php if ($submitted): ?>
                    <div class="form-success">
                        <i class="fa-solid fa-circle-check"></i> Thanks — your message has been noted.
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Your Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Your Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Send Message</button>
            </form>
        </div>
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