<?php

function getFallbackMovies()
{
    return [
        [
            'movie_id' => -1,
            'title' => 'The Odyssey',
            'genre' => 'Adventure, Action, and Fantasy',
            'age_rating' => '12+',
            'duration_minutes' => 177,
            'description' => "Odysseus, the legendary King of Ithaca, embarks on a long and perilous journey home following the Trojan War. Throughout his voyage, he is forced to confront the whims of gods, mythological monsters, and trials that stretch both his cunning and his humanity to the breaking point.",
            'poster_url' => 'img/odsesseyPoster.jpg',
            'backdrop_url' => 'img/odsesseyBackdrop.jpg',
            'title_img' => 'img/odsesseyPoster.jpg',
            'trailer_url' => '',
            'release_date' => '2022-01-01',
            'status' => 'now_showing',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -2,
            'title' => 'Scary Movie',
            'genre' => 'Comedy',
            'age_rating' => '15+',
            'duration_minutes' => 96,
            'description' => 'Twenty-six years after outrunning a suspiciously familiar masked killer, the Core Four are back in the killer\'s crosshairs and no horror movie IP is safe.',
            'poster_url' => 'img/scaryMoviePoster.jpg',
            'backdrop_url' => 'img/scaryMovieBackdrop.jpg',
            'title_img' => 'img/scaryMovieTitle.jpg',
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
            'title' => 'Spider-Man: Brand New Day',
            'genre' => 'Science Fiction, Action and Adventure',
            'age_rating' => '12+',
            'duration_minutes' => 145,
            'description' => 'Fighting crime full-time as Spider-Man in a world that doesn\'t remember him—and the pressure of seeing his old friends move on without him—sparks a change in Peter Parker he may not have the power to control. But that transformation might also be the only thing that can stop a shocking new threat to the city and those he loves - a powerful villain no one can even see.',
            'poster_url' => 'img/BrandNewDayPoster.jpg',
            'backdrop_url' => 'img/BrandNewDayBackdrop.jpg',
            'title_img' => 'img/BrandNewDayTitle.jpg',
            'trailer_url' => '',
            'release_date' => '2022-01-01',
            'status' => 'coming_soon',
            'is_fallback' => true,
        ],
        [
            'movie_id' => -6,
            'title' => 'Gauthali',
            'genre' => 'Drama',
            'age_rating' => '12+',
            'duration_minutes' => 139,
            'description' => 'Gauthali, a child bride overcomes abuse, loss, and discrimination through the power of education. Her determination to reclaim her future challenges deep-rooted traditions inspiring to stand against gender inequality.',
            'poster_url' => 'img/gauthaliPoster.jpg',
            'backdrop_url' => 'img/gauthaliBackdrop.jpg',
            'title_img' => 'img/gauthaliPoster.jpg',
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

    try {
        $rows = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        // Schema doesn't have the expected columns yet — fall back safely
        $rows = [];
    }

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

    try {
        $rows = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        $rows = [];
    }

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
        SELECT m.*, COUNT(bs.id) AS tickets_sold
        FROM movies m
        JOIN showtimes s ON m.movie_id = s.movie_id
        JOIN bookings b ON s.showtime_id = b.showtime_id AND b.status = 'confirmed'
        JOIN booking_seats bs ON b.booking_id = bs.booking_id
        GROUP BY m.movie_id
        ORDER BY tickets_sold DESC
        LIMIT " . (int) $limit;

    try {
        $popular = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        $popular = [];
    }

    if (count($popular) === 0) {
        $popular = getNowShowing($pdo, $limit);
    }
    return $popular;
}

function getCarouselMovies(PDO $pdo, $limit = 5)
{
    $limit = $limit ?: 5;
    $sql = "SELECT * FROM movies WHERE is_featured = 1 ORDER BY featured_at DESC, movie_id DESC LIMIT " . (int) $limit;

    try {
        $rows = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        $rows = [];
    }

    // Pad with active 'now_showing' movies if we don't have enough featured ones
    if (count($rows) < $limit) {
        $padLimit = $limit - count($rows);
        $excludeIds = empty($rows) ? [0] : array_column($rows, 'movie_id');
        $inClause = implode(',', array_map('intval', $excludeIds));

        try {
            $padSql = "SELECT * FROM movies WHERE status = 'now_showing' AND movie_id NOT IN ($inClause) ORDER BY release_date DESC LIMIT $padLimit";
            $padRows = $pdo->query($padSql)->fetchAll();
            $rows = array_merge($rows, $padRows);
        } catch (PDOException $e) {
            // Ignore if schema not fully ready
        }
    }

    if (count($rows) === 0) {
        $rows = array_slice(getFallbackMovies(), 0, $limit);
    }

    return $rows;
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
    try {
        return (int) $pdo->query("SELECT COUNT(*) AS c FROM movies")->fetch()['c'];
    } catch (PDOException $e) {
        return 0;
    }
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
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM movies
            WHERE title LIKE ?
               OR genre LIKE ?
               OR CAST(duration_minutes AS CHAR) LIKE ?
            ORDER BY release_date DESC
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function isInWishlist(PDO $pdo, $userId, $movieId)
{
    if ($movieId < 0) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND movie_id = ?");
        $stmt->execute([$userId, $movieId]);
        return (bool) $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function getUserWishlistIds(PDO $pdo, $userId)
{
    try {
        $stmt = $pdo->prepare("SELECT movie_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'movie_id');
    } catch (PDOException $e) {
        return [];
    }
}

function getUserNotifiedIds(PDO $pdo, $userId)
{
    try {
        $stmt = $pdo->prepare("SELECT movie_id FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'movie_id');
    } catch (PDOException $e) {
        return [];
    }
}