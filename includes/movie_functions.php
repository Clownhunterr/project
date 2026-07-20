<?php

function getFallbackMovies()
{
    return [
        [
            'movie_id' => -1,
            'title' => 'Spider-Man: No Way Home',
            'genre' => 'Romance',
            'age_rating' => '12+',
            'duration_minutes' => 134,
            'description' => "With Spider-Man's identity now revealed, Peter asks Doctor Strange for help. When a spell goes wrong, dangerous foes from other worlds start to appear.",
            'poster_url' => 'img/spiderman.jpg',
            'backdrop_url' => 'img/NWHfromTMD.jpg',
            'title_img' => 'img/NWHPLogoPoster.jpg',
            'trailer_url' => '',
            'release_date' => '2022-01-01',
            'status' => 'now_showing',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -2,
            'title' => 'John Wick',
            'genre' => 'Action',
            'age_rating' => '15+',
            'duration_minutes' => 129,
            'description' => 'John Wick uncovers a path to defeating The High Table, but before he can earn his freedom, he must face off against a new enemy.',
            'poster_url' => 'img/Jhon Wick.jpg',
            'backdrop_url' => 'img/johnWickfromTMBD.jpg',
            'title_img' => 'img/Jhon Wick.jpg',
            'trailer_url' => '',
            'release_date' => '2023-01-01',
            'status' => 'now_showing',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -3,
            'title' => 'Under the Red Hood',
            'genre' => 'Sci-Fi',
            'age_rating' => '12+',
            'duration_minutes' => 125,
            'description' => "There's a mystery afoot in Gotham City, and Batman must go toe-to-toe with a mysterious vigilante, who goes by the name of Red Hood. Subsequently, old wounds reopen and old, once buried memories come into the light.",
            'poster_url' => 'img/5GZRRD4Q9kQhyveYU3CFw27sQxi.jpg',
            'backdrop_url' => 'img/UTRHfromTMD.jpg',
            'title_img' => 'img/UTRHLogoPoster.jpg',
            'trailer_url' => '',
            'release_date' => '2023-01-01',
            'status' => 'now_showing',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -4,
            'title' => 'Avengers',
            'genre' => 'Action',
            'age_rating' => '12+',
            'duration_minutes' => 181,
            'description' => 'The remaining Avengers must find a way to bring back their fallen allies for one final, epic battle.',
            'poster_url' => 'img/avengers.jpg',
            'backdrop_url' => 'img/the-avengers-in-the-avengers-2012.jpg',
            'title_img' => 'img/AvengersfromTMDB.jpg',
            'trailer_url' => '',
            'release_date' => '2019-01-01',
            'status' => 'now_showing',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -5,
            'title' => 'Moon Knight',
            'genre' => 'Fantasy',
            'age_rating' => '15+',
            'duration_minutes' => null,
            'description' => 'A man with dissociative identity disorder becomes entangled in a deadly mystery involving Egyptian gods.',
            'poster_url' => 'img/moon knight.jpg',
            'backdrop_url' => 'img/MoonKnightfromTMD.jpg',
            'title_img' => 'img/MoonKnightPoster.jpg',
            'trailer_url' => '',
            'release_date' => '2022-01-01',
            'status' => 'coming_soon',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -6,
            'title' => 'Money Heist',
            'genre' => 'Crime',
            'age_rating' => '15+',
            'duration_minutes' => 130,
            'description' => 'A criminal mastermind manipulates hostages and police to carry out his plan, aiming for the biggest heist in history.',
            'poster_url' => 'img/money heist.jpg',
            'backdrop_url' => 'img/money heist.jpg',
            'title_img' => 'img/money heist.jpg',
            'trailer_url' => '',
            'release_date' => '2026-09-02',
            'status' => 'coming_soon',
            'is_fallback' => true,
        ],
    ];
}

function getNowShowing(PDO $pdo, $limit = null)
{
    $sql = "SELECT * FROM movies WHERE status = 'now_showing' ORDER BY release_date DESC";
    if ($limit !== null) {
        $sql .= " LIMIT " . (int) $limit;
    }
    $rows = $pdo->query($sql)->fetchAll();

    if (count($rows) === 0) {
        $rows = array_values(array_filter(getFallbackMovies(), function ($m) {
            return $m['status'] === 'now_showing';
        }));
        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }
    }

    return $rows;
}

function getComingSoon(PDO $pdo, $limit = null)
{
    $sql = "SELECT * FROM movies WHERE status = 'coming_soon' ORDER BY release_date ASC";
    if ($limit !== null) {
        $sql .= " LIMIT " . (int) $limit;
    }
    $rows = $pdo->query($sql)->fetchAll();

    if (count($rows) === 0) {
        $rows = array_values(array_filter(getFallbackMovies(), function ($m) {
            return $m['status'] === 'coming_soon';
        }));
        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }
    }

    return $rows;
}

function getPopularMovies(PDO $pdo, $limit = 4)
{
    $sql = "
        SELECT m.*, COUNT(bs.seat_id) AS tickets_sold
        FROM movies m
        JOIN showtimes s ON m.movie_id = s.movie_id
        JOIN bookings b ON s.showtime_id = b.showtime_id AND b.status = 'confirmed'
        JOIN booking_seats bs ON b.booking_id = bs.booking_id
        GROUP BY m.movie_id
        ORDER BY tickets_sold DESC
        LIMIT " . (int) $limit;
    $popular = $pdo->query($sql)->fetchAll();

    if (count($popular) === 0) {
        $popular = getNowShowing($pdo, $limit);
    }
    return $popular;
}

function getCarouselMovies(PDO $pdo, $limit = 5)
{
    return getNowShowing($pdo, $limit);
}

function getMovieById(PDO $pdo, $movieId)
{
    $movieId = (int) $movieId;

    if ($movieId < 0) {
        foreach (getFallbackMovies() as $m) {
            if ($m['movie_id'] === $movieId) {
                return $m;
            }
        }
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch();
    return $movie ?: null;
}

function getMovieCount(PDO $pdo)
{
    return (int) $pdo->query("SELECT COUNT(*) AS c FROM movies")->fetch()['c'];
}

function searchMovies(PDO $pdo, $query)
{
    if (getMovieCount($pdo) === 0) {
        $needle = strtolower($query);
        return array_values(array_filter(getFallbackMovies(), function ($m) use ($needle) {
            return strpos(strtolower($m['title']), $needle) !== false
                || strpos(strtolower($m['genre']), $needle) !== false
                || strpos((string) $m['duration_minutes'], $needle) !== false;
        }));
    }

    $like = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT * FROM movies
        WHERE title LIKE ?
           OR genre LIKE ?
           OR CAST(duration_minutes AS CHAR) LIKE ?
        ORDER BY release_date DESC
    ");
    $stmt->execute([$like, $like, $like]);
    return $stmt->fetchAll();
}

function isInWishlist(PDO $pdo, $userId, $movieId)
{
    if ($movieId < 0) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$userId, $movieId]);
    return (bool) $stmt->fetch();
}

function getUserWishlistIds(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT movie_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    return array_column($stmt->fetchAll(), 'movie_id');
}