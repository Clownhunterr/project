<?php
session_start();
require 'db.php';
require 'includes/movie_functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$movieId = $_GET['id'] ?? null;

if ($movieId === null) {
    header("Location: home.php");
    exit;
}

$movie = getMovieById($pdo, $movieId);

if (!$movie) {
    header("Location: home.php");
    exit;
}

$isFallback = !empty($movie['is_fallback']);
$inWishlist = false;
if ($isLoggedIn && !$isFallback) {
    $inWishlist = isInWishlist($pdo, $_SESSION['user_id'], $movie['movie_id']);
}

function formatDuration($minutes)
{
    $minutes = (int) $minutes;
    if ($minutes <= 0) return '';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0 && $m > 0) return "{$h}h {$m}min";
    if ($h > 0) return "{$h}h";
    return "{$m}min";
}

$backdrop = $movie['backdrop_url'] ?: $movie['poster_url'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="movie.css" />
    <title>CineBooking | <?php echo htmlspecialchars($movie['title']); ?></title>
</head>

<body>
    <header>
        <a href="home.php" class="logo">CineBooking</a>

        <form class="search" method="GET" action="home.php">
            <input type="text" name="search" placeholder="Search movies, genres..." />
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
        </form>

        <?php if ($isLoggedIn): ?>
            <div class="header-right">
                <a href="profile/profile.php?tab=wishlist" class="mylist-btn" title="My List">
                    <i class="fa-solid fa-heart"></i>
                </a>
                <a href="profile/profile.php" class="profile-btn">
                    <i class="fa-solid fa-circle-user"></i>
                    <?php echo htmlspecialchars($_SESSION['name']); ?>
                </a>
            </div>
        <?php else: ?>
            <a href="/login/login.html" class="auth-btn">Login / Register</a>
        <?php endif; ?>
    </header>

    <div class="trailer" id="trailerOverlay">
        <div class="video-wrapper">
            <video id="trailerVideo" controls></video>
        </div>
    </div>

    <section class="movie-hero" style="background-image: url('<?php echo htmlspecialchars($backdrop ?: 'img/placeholder-poster.jpg'); ?>');">
        <div class="movie-hero-overlay"></div>
        <div class="movie-hero-content">
            <img class="movie-hero-poster" src="<?php echo htmlspecialchars($movie['poster_url'] ?: 'img/placeholder-poster.jpg'); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">

            <div class="movie-hero-info">
                <h1><?php echo htmlspecialchars($movie['title']); ?></h1>

                <div class="movie-meta">
                    <?php if ($movie['release_date']): ?>
                        <span><?php echo date('Y', strtotime($movie['release_date'])); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($movie['age_rating'])): ?>
                        <span class="tag"><?php echo htmlspecialchars($movie['age_rating']); ?></span>
                    <?php endif; ?>
                    <?php if ($movie['duration_minutes']): ?>
                        <span><?php echo formatDuration($movie['duration_minutes']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($movie['genre'])): ?>
                        <span><?php echo htmlspecialchars($movie['genre']); ?></span>
                    <?php endif; ?>
                </div>

                <p class="movie-desc"><?php echo htmlspecialchars($movie['description']); ?></p>

                <div class="movie-actions">
                    <?php if (!empty($movie['trailer_url'])): ?>
                        <button type="button" class="btn-hero btn-hero-primary" onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url'], ENT_QUOTES); ?>')">
                            <i class="fa-solid fa-play"></i> Watch Trailer
                        </button>
                    <?php endif; ?>

                    <a href="#" class="btn-hero btn-hero-primary" title="Showtimes and seat selection coming soon">
                        <i class="fa-solid fa-ticket"></i> Book Ticket
                    </a>

                    <?php if ($isLoggedIn && !$isFallback): ?>
                        <button type="button" class="btn-hero btn-hero-outline heart-btn <?php echo $inWishlist ? 'active' : ''; ?>"
                            data-movie-id="<?php echo (int) $movie['movie_id']; ?>" onclick="toggleWishlist(this)">
                            <i class="<?php echo $inWishlist ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                            <span><?php echo $inWishlist ? 'In Wishlist' : 'Add to Wishlist'; ?></span>
                        </button>
                    <?php elseif (!$isLoggedIn): ?>
                        <a href="/login/login.html" class="btn-hero btn-hero-outline">
                            <i class="fa-regular fa-heart"></i> Add to Wishlist
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="movie-back-link">
        <a href="home.php"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
    </div>

    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3 class="footer-logo">CineBooking</h3>
                <p>Your seat, your show, booked in seconds.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="home.php">Now Showing</a></li>
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

    <script>
        const CINEBOOKING_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

        function playTrailer(src) {
            const overlay = document.getElementById('trailerOverlay');
            const video = document.getElementById('trailerVideo');
            video.src = src;
            overlay.classList.add('active');
            video.play();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const trailerOverlay = document.getElementById('trailerOverlay');
            trailerOverlay.addEventListener('click', function () {
                const video = document.getElementById('trailerVideo');
                video.pause();
                video.removeAttribute('src');
                video.load();
                trailerOverlay.classList.remove('active');
            });
            document.querySelector('.video-wrapper').addEventListener('click', e => e.stopPropagation());
        });
    </script>
    <script src="home.js"></script>
</body>

</html>