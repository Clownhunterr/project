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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" />
    <link rel="stylesheet" href="home.css" />
    <title>CineBooking</title>
</head>

<body>
    <header>
        <a href="home.php" class="logo">CineBooking</a>

        <div class="search">
            <input type="text" placeholder="Search" />
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </div>

        <?php if ($isLoggedIn): ?>
            <a href="/profile/profile.php" class="profile-btn">
                <i class="fa-solid fa-circle-user"></i>
                <?php echo htmlspecialchars($_SESSION['name']); ?>
            </a>
        <?php else: ?>
            <a href="/login/login.html" class="auth-btn">Login / Register</a>
        <?php endif; ?>
    </header>

    <div class="trailer" id="trailerOverlay">
        <div class="video-wrapper">
            <iframe id="trailerVideo" src="" title="Movie Trailer" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
        </div>
    </div>

    <div class="banner" id="banner">
        <div class="content active" id="movieContent">
            <img src="img/NWHPLogoPoster.jpg" alt="" class="movie-title" id="movieTitleImg">
            <h4>
                <span id="movieYear">2022</span>
                <span><i id="movieRating">12+</i></span>
                <span id="movieDuration">2h 14min</span>
                <span id="movieGenre">Romance</span>
            </h4>
            <p id="movieDesc">
                With Spider-Man's identity now revealed, Peter asks Doctor Strange for help. When a spell goes wrong,
                dangerous foes from other worlds start to appear.
            </p>
            <div class="button">
                <a href="javascript:void(0)" onclick="openTrailer()"><i class="fa-solid fa-play"
                        aria-hidden="true"></i>Watch Trailer</a>
                <a href="/booking/booking.html"><i class="fa-solid fa-plus" aria-hidden="true"></i>Book Ticket</a>
            </div>
        </div>

        <div class="carousel-box" id="carouselBox">
            <div class="carousel">

                <div class="carousel-item" id="spidermanItem" data-bg="img/NWHfromTMD.jpg"
                    data-title-img="img/NWHPLogoPoster.jpg" data-year="2022" data-rating="12+" data-duration="2h 14min"
                    data-genre="Romance"
                    data-desc="With Spider-Man's identity now revealed, Peter asks Doctor Strange for help. When a spell goes wrong, dangerous foes from other worlds start to appear."
                    data-trailer="REPLACE_WITH_YOUTUBE_ID_SPIDERMAN">
                    <img src="img/spiderman.jpg" alt="Spider-Man" />
                </div>

                <div class="carousel-item" data-bg="img/johnWickfromTMBD.jpg" data-title-img="img/Jhon Wick.jpg"
                    data-year="2023" data-rating="15+" data-duration="2h 9min" data-genre="Action"
                    data-desc="John Wick uncovers a path to defeating The High Table, but before he can earn his freedom, he must face off against a new enemy."
                    data-trailer="REPLACE_WITH_YOUTUBE_ID_JOHNWICK">
                    <img src="img/Jhon Wick.jpg" alt="John Wick" />
                </div>

                <div class="carousel-item" data-bg="img/UTRHfromTMD.jpg" data-title-img="img/UTRHLogoPoster.jpg"
                    data-year="2023" data-rating="12+" data-duration="2h 5min" data-genre="Sci-Fi"
                    data-desc="There's a mystery afoot in Gotham City, and Batman must go toe-to-toe with a mysterious vigilante, who goes by the name of Red Hood. Subsequently, old wounds reopen and old, once buried memories come into the light"
                    data-trailer="REPLACE_WITH_YOUTUBE_ID_REDHOOD">
                    <img src="img/5GZRRD4Q9kQhyveYU3CFw27sQxi.jpg" alt="Ant Man" />
                </div>

                <div class="carousel-item" data-bg="img/the-avengers-in-the-avengers-2012.jpg"
                    data-title-img="img/AvengersfromTMDB.jpg" data-year="2019" data-rating="12+" data-duration="3h 1min"
                    data-genre="Action"
                    data-desc="The remaining Avengers must find a way to bring back their fallen allies for one final, epic battle."
                    data-trailer="REPLACE_WITH_YOUTUBE_ID_AVENGERS">
                    <img src="img/avengers.jpg" alt="Avengers" />
                </div>

                <div class="carousel-item" data-bg="img/MoonKnightfromTMD.jpg" data-title-img="img/MoonKnightPoster.jpg"
                    data-year="2022" data-rating="15+" data-duration="6 episodes" data-genre="Fantasy"
                    data-desc="A man with dissociative identity disorder becomes entangled in a deadly mystery involving Egyptian gods."
                    data-trailer="REPLACE_WITH_YOUTUBE_ID_MOONKNIGHT">
                    <img src="img/moon knight.jpg" alt="Moon Knight" />
                </div>

            </div>
        </div>
    </div>

    <!-- NOW SHOWING -->
    <section class="movie-section">
        <h2 class="section-title">Now Showing</h2>
        <div class="movie-grid">
            <div class="movie-card">
                <img src="img/Jhon Wick.jpg" alt="John Wick" />
                <div class="movie-info">
                    <h3>John Wick</h3>
                    <p>Action • 2h 9min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
            <div class="movie-card">
                <img src="img/thor love of thunder.jpg" alt="Thor: Love and Thunder" />
                <div class="movie-info">
                    <h3>Thor: Love and Thunder</h3>
                    <p>Action • 1h 59min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
            <div class="movie-card">
                <img src="img/spiderman.jpg" alt="Spider-Man" />
                <div class="movie-info">
                    <h3>Spider-Man</h3>
                    <p>Romance • 2h 14min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
            <div class="movie-card">
                <img src="img/avengers.jpg" alt="Avengers" />
                <div class="movie-info">
                    <h3>Avengers</h3>
                    <p>Action • 3h 1min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
        </div>
    </section>

    <!-- COMING SOON -->
    <section class="movie-section">
        <h2 class="section-title">Coming Soon</h2>
        <div class="movie-grid">
            <div class="movie-card">
                <img src="img/moon knight.jpg" alt="Moon Knight" />
                <span class="badge">Releasing Aug 15</span>
                <div class="movie-info">
                    <h3>Moon Knight</h3>
                    <p>Fantasy • 6 episodes</p>
                    <a href="#" class="btn-notify">Notify Me</a>
                </div>
            </div>
            <div class="movie-card">
                <img src="img/money heist.jpg" alt="Money Heist" />
                <span class="badge">Releasing Sep 2</span>
                <div class="movie-info">
                    <h3>Money Heist</h3>
                    <p>Crime • 2h 10min</p>
                    <a href="#" class="btn-notify">Notify Me</a>
                </div>
            </div>
        </div>
    </section>

    <!-- POPULAR -->
    <section class="movie-section">
        <h2 class="section-title">Popular</h2>
        <div class="movie-grid">
            <div class="movie-card">
                <span class="rank">1</span>
                <img src="img/avengers.jpg" alt="Avengers" />
                <div class="movie-info">
                    <h3>Avengers</h3>
                    <p>Action • 3h 1min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
            <div class="movie-card">
                <span class="rank">2</span>
                <img src="img/Jhon Wick.jpg" alt="John Wick" />
                <div class="movie-info">
                    <h3>John Wick</h3>
                    <p>Action • 2h 9min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
            <div class="movie-card">
                <span class="rank">3</span>
                <img src="img/spiderman.jpg" alt="Spider-Man" />
                <div class="movie-info">
                    <h3>Spider-Man</h3>
                    <p>Romance • 2h 14min</p>
                    <a href="#" class="btn-book">Book Ticket</a>
                </div>
            </div>
        </div>
    </section>

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
                    <li><a href="#">Now Showing</a></li>
                    <li><a href="#">Coming Soon</a></li>
                    <li><a href="/profile/profile.php">My Bookings</a></li>
                    <li><a href="#">Contact</a></li>
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

    <ul class="sci">
        <li><a href="#"><i class="fa-brands fa-facebook"></i></a></li>
        <li><a href="#"><i class="fa-brands fa-twitter"></i></a></li>
        <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
    </ul>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="home.js"></script>
</body>

</html>