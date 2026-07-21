<?php
// ============================================================
//  booking/booking.php  — Dynamic movie booking page
//  Reads ?id= from the URL, loads the movie from the DB
//  (or the built-in fallback array for demo movies),
//  then renders the full seat-selection / payment UI.
// ============================================================
session_start();

// ---- Intercept eSewa Return ----
if (isset($_GET['data'])) {
    require 'process_esewa.php';
    exit;
}

// ---- Auth guard: booking requires a logged-in account ----
if (!isset($_SESSION['user_id'])) {
    $back = urlencode('booking/booking.php?id=' . (int)($_GET['id'] ?? 0));
    header('Location: ../login/login.php?next=' . $back);
    exit;
}

require '../database/db.php';
require '../includes/movie_functions.php';

$movieId = (int)($_GET['id'] ?? 0);
$movie   = null;

// 1. Try the database (positive IDs = real movies)
if ($movieId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM movies WHERE movie_id = ?');
        $stmt->execute([$movieId]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // DB miss or schema issue — fall through to fallback
    }
}

// 2. Negative IDs = fallback demo movies (or DB miss)
if (!$movie) {
    foreach (getFallbackMovies() as $fb) {
        if ((int)$fb['movie_id'] === $movieId) {
            $movie = $fb;
            break;
        }
    }
}

// 3. Nothing matched — send user home
if (!$movie) {
    header('Location: ../index.php');
    exit;
}

// ---- Helpers ----
function fmtDuration($min)
{
    $min = (int) $min;
    if ($min <= 0) return '—';
    $h = intdiv($min, 60);
    $m = $min % 60;
    if ($h > 0 && $m > 0) return "{$h}h {$m}min";
    if ($h > 0) return "{$h}h";
    return "{$m}min";
}

// ---- Build next-8-day date strip ----
$days = [];
for ($i = 0; $i < 8; $i++) {
    $ts     = strtotime("+{$i} day");
    $days[] = [
        'dayName' => date('D', $ts),  // Mon, Tue …
        'dayNum'  => (int) date('j', $ts),
        'fullDate'=> date('Y-m-d', $ts),
        'active'  => ($i === 0),
    ];
}

// ---- Convenience variables ----
$posterUrl  = htmlspecialchars($movie['poster_url']  ?? '');
$trailerUrl = htmlspecialchars($movie['trailer_url'] ?? '');
$title      = htmlspecialchars($movie['title']       ?? 'Movie');
$duration   = fmtDuration($movie['duration_minutes'] ?? 0);
$ageRating  = htmlspecialchars($movie['age_rating']  ?? '');
$genre      = htmlspecialchars($movie['genre']       ?? '');
$description = htmlspecialchars($movie['description'] ?? '');
$userName   = htmlspecialchars($_SESSION['name']     ?? '');
$isComingSoon = ($movie['status'] ?? '') === 'coming_soon';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBooking | Book — <?php echo $title; ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ── Back-to-home nav bar ── */
        .booking-topbar {
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 30px;
            background: rgba(31,32,37,0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .booking-topbar .logo {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fd6565;
            text-decoration: none;
            letter-spacing: 0.5px;
        }
        .booking-topbar .back-home {
            display: flex;
            align-items: center;
            gap: 7px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.82rem;
            transition: color .2s;
        }
        .booking-topbar .back-home:hover { color: #fff; }
        .booking-topbar .user-tag {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
        }

        /* Push content below the fixed nav */
        body { padding-top: 54px; }
        .book  { margin-top: 0; }

        /* Coming-soon overlay */
        .coming-soon-overlay {
            position: absolute;
            inset: 0;
            background: rgba(31,32,37,0.82);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            z-index: 20;
            border-radius: 12px;
        }
        .coming-soon-overlay i { font-size: 2.4rem; color: #fd6565; }
        .coming-soon-overlay h3 { color: #fff; font-size: 1.3rem; font-weight: 600; }
        .coming-soon-overlay p  { color: rgba(255,255,255,0.6); font-size: 0.85rem; }
        .coming-soon-overlay a  {
            margin-top: 6px;
            padding: 9px 24px;
            border-radius: 8px;
            background: #fd6565;
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background .2s;
        }
        .coming-soon-overlay a:hover { background: #e04f4f; }

        /* Genre badge on poster panel */
        .genre-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            background: rgba(253,101,101,0.18);
            border: 1px solid rgba(253,101,101,0.4);
            color: #fd6565;
            font-size: 11px;
            font-weight: 600;
            margin: 8px 20px 4px;
        }
        /* Description blurb */
        .movie-desc {
            color: rgba(255,255,255,0.55);
            font-size: 10.5px;
            line-height: 1.55;
            padding: 0 20px 10px;
        }
    </style>
</head>

<body>

    <!-- ── Top navigation bar ── -->
    <nav class="booking-topbar">
        <a href="../index.php" class="logo">CineBooking</a>
        <span class="user-tag"><i class="fa-solid fa-circle-user"></i> <?php echo $userName; ?></span>
        <a href="../index.php" class="back-home">
            <i class="bi bi-arrow-left"></i> Back to movies
        </a>
    </nav>

    <div class="book">

        <!-- ══════════════════ LEFT PANEL — poster + info ══════════════════ -->
        <div class="left">
            <?php if ($posterUrl): ?>
                <img src="../<?php echo $posterUrl; ?>" alt="<?php echo $title; ?>">
            <?php else: ?>
                <div style="width:100%;height:200px;background:#2e3037;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.3);font-size:13px;">No Poster</div>
            <?php endif; ?>

            <div class="play">
                <i class="bi bi-play-fill"></i>
            </div>

            <?php if ($genre): ?>
                <span class="genre-badge"><?php echo $genre; ?></span>
            <?php endif; ?>

            <div class="cont">
                <?php if ($description): ?>
                    <p class="movie-desc"><?php echo $description; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══════════════════ RIGHT PANEL — booking UI ══════════════════ -->
        <div class="right" style="position:relative;">

            <!-- Background trailer video (muted, loops) -->
            <?php if ($trailerUrl): ?>
                <video src="../<?php echo $trailerUrl; ?>" id="bgVideo" autoplay muted loop playsinline></video>
            <?php else: ?>
                <!-- No trailer — use backdrop image as CSS bg via inline style -->
                <?php
                $backdrop = htmlspecialchars($movie['backdrop_url'] ?? $movie['poster_url'] ?? '');
                if ($backdrop): ?>
                    <style>.book .right::before { background-image: url(../<?php echo $backdrop; ?>); }</style>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Coming-soon movies can't be booked yet -->
            <?php if ($isComingSoon): ?>
                <div class="coming-soon-overlay">
                    <i class="bi bi-clock-history"></i>
                    <h3><?php echo $title; ?></h3>
                    <p>This movie is not yet showing. Bookmark it and check back soon.</p>
                    <a href="../index.php">Browse Now Showing</a>
                </div>
            <?php endif; ?>

            <!-- ── Movie title + runtime ── -->
            <div class="head_time">
                <h1><?php echo $title; ?></h1>
                <div class="time">
                    <?php if ($duration !== '—'): ?>
                        <h6><i class="bi bi-clock"></i><?php echo $duration; ?></h6>
                    <?php endif; ?>
                    <?php if ($ageRating): ?>
                        <button><?php echo $ageRating; ?></button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Date & showtime pickers ── -->
            <div class="date_type">
                <!-- Date strip: today + next 7 days, generated dynamically -->
                <div class="left_card">
                    <h6 class="title"><?php echo date('l j M'); ?></h6>
                    <div class="card_month crd">
                        <?php foreach ($days as $d): ?>
                            <li>
                                <h6><?php echo $d['dayName']; ?></h6>
                                <h6 class="date_point<?php echo $d['active'] ? ' h6_active' : ''; ?>" data-fulldate="<?php echo $d['fullDate']; ?>"><?php echo $d['dayNum']; ?></h6>
                            </li>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Showtime strip -->
                <div class="right_card">
                    <h6 class="title">Show Time</h6>
                    <div class="card_month crd">
                        <li><h6>2D</h6><h6>10:00</h6></li>
                        <li><h6>2D</h6><h6>12:30</h6></li>
                        <li><h6>2D</h6><h6>14:30</h6></li>
                        <li><h6>2D</h6><h6 class="h6_active">17:30</h6></li>
                        <li><h6>2D</h6><h6>19:30</h6></li>
                    </div>
                </div>
            </div>

            <!-- ── Screen label ── -->
            <div class="screen">Screen</div>

            <!-- ── Seat map: 8 rows (A–H), 23 seats each ── -->
            <div class="chair">
                <?php
                $rowLabels = ['A','B','C','D','E','F','G','H'];
                foreach ($rowLabels as $rowLabel):
                ?>
                <div class="row">
                    <span><?php echo $rowLabel; ?></span>
                    <?php for ($s = 1; $s <= 23; $s++): ?>
                        <li class="seat">560</li>
                    <?php endfor; ?>
                    <span><?php echo $rowLabel; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Legend ── -->
            <div class="details" id="det">
                <div class="details_chair">
                    <li>Available</li>
                    <li>Selected</li>
                    <li>Booked</li>
                </div>
            </div>

            <!-- ── Ticket display (hidden until payment completes) ── -->
            <div class="ticket">
                <div class="tic">
                    <div class="barcode">
                        <div class="card">
                            <h6>Row —</h6>
                            <h6>— —</h6>
                        </div>
                        <div class="card">
                            <h6>Seat —</h6>
                            <h6>—:—</h6>
                        </div>
                        <svg id="barcode"></svg>
                        <h5>CINEBOOKING</h5>
                    </div>
                    <div class="tic_details">
                        <div class="type">2DX</div>
                        <h5 class="pvr"><span>Cine</span>Booking</h5>
                        <h1><?php echo $title; ?></h1>
                        <div class="seat_det">
                            <div class="seat_cr">
                                <h6>ROW</h6>
                                <h6>—</h6>
                            </div>
                        </div>
                        <div class="seat_det">
                            <div class="seat_cr"><h6>ROW</h6><h6>—</h6></div>
                            <div class="seat_cr"><h6>SEAT</h6><h6>—</h6></div>
                            <div class="seat_cr"><h6>DATE</h6><h6>—<sub>—</sub></h6></div>
                            <div class="seat_cr"><h6>TIME</h6><h6>—<sub>—</sub></h6></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Action buttons ── -->
            <button class="book_ticket">
                <i class="bi bi-arrow-right-short"></i>
            </button>
            <button class="back_ticket">
                <i class="bi bi-arrow-right-short"></i>
            </button>

        </div><!-- /right -->
    </div><!-- /book -->

    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.12.1/JsBarcode.all.js"
        integrity="sha512-iKSej9nrLjdo6RKseKJcAZgTLj6ESwnUjj/vEFhuDhNTzULrk5RBPWeG2YIVWcI6hAIEccsY9Qf6+g39jpDfnw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        JsBarcode("#barcode", "CB<?php echo abs($movieId); ?><?php echo date('Ymd'); ?>", {
            width: 1.5,
            height: 40,
            displayValue: false
        });
    </script>

</body>
</html>
