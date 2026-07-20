<?php
session_start();
require 'database/db.php';
require 'includes/movie_functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
$wishlistIds = $isLoggedIn ? getUserWishlistIds($pdo, $_SESSION['user_id']) : [];

$searchQuery = trim($_GET['search'] ?? '');
$searchResults = [];
if ($searchQuery !== '') {
    $searchResults = searchMovies($pdo, $searchQuery);
}

$carouselMovies = getCarouselMovies($pdo, 5);
$nowShowing = getNowShowing($pdo, 8);
$comingSoon = getComingSoon($pdo, 8);
$popularMovies = getPopularMovies($pdo, 4);

function formatDuration($minutes)
{
    $minutes = (int) $minutes;
    if ($minutes <= 0)
        return '';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0 && $m > 0)
        return "{$h}h {$m}min";
    if ($h > 0)
        return "{$h}h";
    return "{$m}min";
}

function movieCard($movie, $wishlistIds, $isLoggedIn, $buttonLabel = 'Book Ticket')
{
    $isFallback = !empty($movie['is_fallback']);
    $inWishlist = !$isFallback && in_array($movie['movie_id'], $wishlistIds);
    $poster = $movie['poster_url'] ?: 'img/placeholder-poster.jpg';
    ?>
    <div class="movie-card">
        <?php if ($isLoggedIn && !$isFallback): ?>
            <button class="heart-btn <?php echo $inWishlist ? 'active' : ''; ?>"
                data-movie-id="<?php echo (int) $movie['movie_id']; ?>" onclick="toggleWishlist(this)" title="Add to wishlist">
                <i class="<?php echo $inWishlist ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
            </button>
        <?php endif; ?>
        <img src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" />
        <div class="movie-info">
            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
            <p><?php echo htmlspecialchars($movie['genre']); ?><?php echo $movie['duration_minutes'] ? ' • ' . formatDuration($movie['duration_minutes']) : ''; ?>
            </p>
            <a href="<?php echo $buttonLabel === 'Book Ticket' ? 'booking/booking.php?id=' . (int)$movie['movie_id'] : '#'; ?>" class="btn-book"><?php echo htmlspecialchars($buttonLabel); ?></a>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" />
    <link rel="stylesheet" href="home.css" />
    <title>CineBooking</title>
</head>

<body>
    <header>
        <a href="index.php" class="logo">CineBooking</a>

        <form class="search" method="GET" action="index.php">
            <input type="text" name="search" placeholder="Search movies, genres..."
                value="<?php echo htmlspecialchars($searchQuery); ?>" />
            <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"
                    aria-hidden="true"></i></button>
        </form>

        <?php if ($isLoggedIn): ?>
            <a href="profile/profile.php" class="profile-btn">
                <i class="fa-solid fa-circle-user"></i>
                <?php echo htmlspecialchars($_SESSION['name']); ?>
            </a>
        <?php else: ?>
            <a href="login/login.php" class="auth-btn">Login / Register</a>
        <?php endif; ?>
    </header>

    <div class="trailer" id="trailerOverlay">
        <div class="video-wrapper">
            <video id="trailerVideo" controls></video>
        </div>
    </div>

    <?php if ($searchQuery !== ''): ?>

        <!-- SEARCH RESULTS -->
        <section class="movie-section search-section">
            <h2 class="section-title">Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
            <?php if (count($searchResults) === 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <p>No movies matched your search.</p>
                    <a href="index.php" class="btn-primary">Back to Home</a>
                </div>
            <?php else: ?>
                <div class="movie-grid">
                    <?php foreach ($searchResults as $movie): ?>
                        <?php movieCard($movie, $wishlistIds, $isLoggedIn); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>

        <div class="banner" id="banner">
            <?php if (count($carouselMovies) > 0):
                $first = $carouselMovies[0];
                $firstTitleImg = $first['title_img'] ?? $first['poster_url'];
                ?>
                <div class="content active" id="movieContent">
                    <img src="<?php echo htmlspecialchars($firstTitleImg ?: 'img/placeholder-poster.jpg'); ?>" alt=""
                        class="movie-title" id="movieTitleImg">
                    <h4>
                        <span
                            id="movieYear"><?php echo $first['release_date'] ? date('Y', strtotime($first['release_date'])) : ''; ?></span>
                        <span><i id="movieRating"><?php echo htmlspecialchars($first['age_rating']); ?></i></span>
                        <span id="movieDuration"><?php echo formatDuration($first['duration_minutes']); ?></span>
                        <span id="movieGenre"><?php echo htmlspecialchars($first['genre']); ?></span>
                    </h4>
                    <p id="movieDesc"><?php echo htmlspecialchars($first['description']); ?></p>
                    <div class="button">
                        <a href="javascript:void(0)" onclick="openTrailer()"><i class="fa-solid fa-play"
                                aria-hidden="true"></i>Watch Trailer</a>
                        <a href="#" id="bannerBookBtn"><i class="fa-solid fa-plus" aria-hidden="true"></i>Book Ticket</a>
                    </div>
                </div>

                <div class="carousel-box" id="carouselBox">
                    <div class="carousel">
                        <?php foreach ($carouselMovies as $movie):
                            $titleImg = $movie['title_img'] ?? $movie['poster_url'];
                            ?>
                            <div class="carousel-item"
                                data-movie-id="<?php echo (int)$movie['movie_id']; ?>"
                                data-bg="<?php echo htmlspecialchars($movie['backdrop_url'] ?: $movie['poster_url']); ?>"
                                data-title-img="<?php echo htmlspecialchars($titleImg); ?>"
                                data-year="<?php echo $movie['release_date'] ? date('Y', strtotime($movie['release_date'])) : ''; ?>"
                                data-rating="<?php echo htmlspecialchars($movie['age_rating']); ?>"
                                data-duration="<?php echo formatDuration($movie['duration_minutes']); ?>"
                                data-genre="<?php echo htmlspecialchars($movie['genre']); ?>"
                                data-desc="<?php echo htmlspecialchars($movie['description']); ?>"
                                data-trailer="<?php echo htmlspecialchars($movie['trailer_url']); ?>"
                                data-status="<?php echo htmlspecialchars($movie['status']); ?>">
                                <img src="<?php echo htmlspecialchars($movie['poster_url'] ?: 'img/placeholder-poster.jpg'); ?>"
                                    alt="<?php echo htmlspecialchars($movie['title']); ?>" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- NOW SHOWING -->
        <section class="movie-section">
            <h2 class="section-title">Now Showing</h2>
            <div class="movie-grid">
                <?php foreach ($nowShowing as $movie): ?>
                    <?php movieCard($movie, $wishlistIds, $isLoggedIn); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- COMING SOON -->
        <section class="movie-section">
            <h2 class="section-title">Coming Soon</h2>
            <div class="movie-grid">
                <?php foreach ($comingSoon as $movie): ?>
                    <?php movieCard($movie, $wishlistIds, $isLoggedIn, 'Notify Me'); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- POPULAR -->
        <section class="movie-section">
            <h2 class="section-title">Popular</h2>
            <div class="movie-grid">
                <?php foreach ($popularMovies as $index => $movie):
                    $isFallback = !empty($movie['is_fallback']);
                    $inWishlist = !$isFallback && in_array($movie['movie_id'], $wishlistIds);
                    ?>
                    <div class="movie-card">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <?php if ($isLoggedIn && !$isFallback): ?>
                            <button class="heart-btn <?php echo $inWishlist ? 'active' : ''; ?>"
                                data-movie-id="<?php echo (int) $movie['movie_id']; ?>" onclick="toggleWishlist(this)"
                                title="Add to wishlist">
                                <i class="<?php echo $inWishlist ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                            </button>
                        <?php endif; ?>
                        <img src="<?php echo htmlspecialchars($movie['poster_url'] ?: 'img/placeholder-poster.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($movie['title']); ?>" />
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p><?php echo htmlspecialchars($movie['genre']); ?><?php echo $movie['duration_minutes'] ? ' • ' . formatDuration($movie['duration_minutes']) : ''; ?>
                            </p>
                            <a href="booking/booking.php?id=<?php echo (int)$movie['movie_id']; ?>" class="btn-book">Book Ticket</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php endif; ?>

    <!-- FOOTER -->
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
                    <li><a href="profile/profile.php">My Bookings</a></li>
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

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        const CINEBOOKING_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script src="home.js"></script>
</body>

</html>