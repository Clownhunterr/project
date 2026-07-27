<?php
require 'admin_auth_check.php';
require '../database/db.php';

function handleUpload($file, $webFolder, $allowedExts)
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return false;
    }
    $diskFolder = '../' . $webFolder;
    if (!is_dir($diskFolder)) {
        mkdir($diskFolder, 0777, true);
    }
    $filename = uniqid('movie_', true) . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $diskFolder . $filename);
    return $webFolder . $filename;
}

$isEdit = isset($_GET['id']);
$movie = [
    'movie_id' => null,
    'title' => '',
    'genre' => '',
    'age_rating' => '',
    'duration_minutes' => '',
    'description' => '',
    'director' => '',
    'producer' => '',
    'actors' => '',
    'poster_url' => '',
    'backdrop_url' => '',
    'trailer_url' => '',
    'release_date' => '',
    'ticket_start_date' => '',
    'ticket_end_date' => '',
    'status' => 'now_showing',
    'is_featured' => 0,
    'featured_at' => null
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE movie_id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header("Location: manage_movies.php");
        exit;
    }
    $movie = $existing;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $ageRating = trim($_POST['age_rating'] ?? '');
    $duration = (int) ($_POST['duration_minutes'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $director = trim($_POST['director'] ?? '');
    $producer = trim($_POST['producer'] ?? '');
    $actors = trim($_POST['actors'] ?? '');
    $releaseDate = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
    $ticketStartDate = !empty($_POST['ticket_start_date']) ? $_POST['ticket_start_date'] : null;
    $ticketEndDate = !empty($_POST['ticket_end_date']) ? $_POST['ticket_end_date'] : null;
    $status = ($_POST['status'] ?? 'now_showing') === 'coming_soon' ? 'coming_soon' : 'now_showing';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    // FIFO Carousel Queue Management:
    // If set to featured, assign current timestamp and unfeature oldest if capacity (5) reached.
    $featuredAt = $movie['featured_at'];
    if ($isFeatured) {
        if (empty($featuredAt) || !$movie['is_featured']) {
            $featuredAt = date('Y-m-d H:i:s');
        }
        $excludeId = $isEdit ? (int)$movie['movie_id'] : 0;
        $countStmt = $pdo->prepare("SELECT movie_id FROM movies WHERE is_featured = 1 AND movie_id != ? ORDER BY featured_at ASC, movie_id ASC");
        $countStmt->execute([$excludeId]);
        $currentlyFeatured = $countStmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($currentlyFeatured) >= 5) {
            $toUnfeatureCount = count($currentlyFeatured) - 4;
            $toUnfeature = array_slice($currentlyFeatured, 0, $toUnfeatureCount);
            if (!empty($toUnfeature)) {
                $inClause = implode(',', array_map('intval', $toUnfeature));
                $pdo->exec("UPDATE movies SET is_featured = 0 WHERE movie_id IN ($inClause)");
            }
        }
    } else {
        $featuredAt = null;
    }

    $posterUrl = $movie['poster_url'];
    $backdropUrl = $movie['backdrop_url'];
    $trailerUrl = $movie['trailer_url'];

    $posterResult = handleUpload($_FILES['poster'] ?? [], 'uploads/posters/', ['jpg', 'jpeg', 'png', 'webp']);
    $backdropResult = handleUpload($_FILES['backdrop'] ?? [], 'uploads/backdrops/', ['jpg', 'jpeg', 'png', 'webp']);
    $trailerUrl = trim($_POST['trailer_url'] ?? $movie['trailer_url']);

    if ($posterResult === false) {
        $error = "Poster must be a JPG, PNG, or WEBP image.";
    } elseif ($backdropResult === false) {
        $error = "Background image must be a JPG, PNG, or WEBP image.";
    } elseif ($title === '') {
        $error = "Title is required.";
    } else {
        if ($posterResult)
            $posterUrl = $posterResult;
        if ($backdropResult)
            $backdropUrl = $backdropResult;

        if ($isEdit) {
            $stmt = $pdo->prepare("
                UPDATE movies
                SET title=?, genre=?, age_rating=?, duration_minutes=?, description=?,
                    director=?, producer=?, actors=?,
                    poster_url=?, backdrop_url=?, trailer_url=?,
                    release_date=?, ticket_start_date=?, ticket_end_date=?,
                    status=?, is_featured=?, featured_at=?
                WHERE movie_id=?
            ");
            $stmt->execute([
                $title,
                $genre,
                $ageRating,
                $duration,
                $description,
                $director,
                $producer,
                $actors,
                $posterUrl,
                $backdropUrl,
                $trailerUrl,
                $releaseDate,
                $ticketStartDate,
                $ticketEndDate,
                $status,
                $isFeatured,
                $featuredAt,
                $movie['movie_id']
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO movies (title, genre, age_rating, duration_minutes, description, director, producer, actors, poster_url, backdrop_url, trailer_url, release_date, ticket_start_date, ticket_end_date, status, is_featured, featured_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $title,
                $genre,
                $ageRating,
                $duration,
                $description,
                $director,
                $producer,
                $actors,
                $posterUrl,
                $backdropUrl,
                $trailerUrl,
                $releaseDate,
                $ticketStartDate,
                $ticketEndDate,
                $status,
                $isFeatured,
                $featuredAt
            ]);
        }
        header("Location: manage_movies.php");
        exit;
    }

    $movie['title'] = $title;
    $movie['genre'] = $genre;
    $movie['age_rating'] = $ageRating;
    $movie['duration_minutes'] = $duration;
    $movie['description'] = $description;
    $movie['director'] = $director;
    $movie['producer'] = $producer;
    $movie['actors'] = $actors;
    $movie['release_date'] = $releaseDate;
    $movie['ticket_start_date'] = $ticketStartDate;
    $movie['ticket_end_date'] = $ticketEndDate;
    $movie['status'] = $status;
    $movie['is_featured'] = $isFeatured;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
    <title>CineBooking | <?php echo $isEdit ? 'Edit' : 'Add'; ?> Movie</title>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="admin-layout">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-main">
            <h1 class="admin-page-title"><?php echo $isEdit ? 'Edit Movie' : 'Add New Movie'; ?></h1>
            <p class="admin-welcome">
                <?php echo $isEdit ? 'Update details for "' . htmlspecialchars($movie['title']) . '"' : 'Fill in the details below to add a movie to the site.'; ?>
            </p>

            <?php if ($error): ?>
                <div class="admin-alert admin-alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form class="admin-form" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Movie Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Genre</label>
                        <input type="text" name="genre" value="<?php echo htmlspecialchars($movie['genre']); ?>"
                            placeholder="e.g. Action">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Age Rating</label>
                        <input type="text" name="age_rating"
                            value="<?php echo htmlspecialchars($movie['age_rating']); ?>" placeholder="e.g. 12+">
                    </div>
                    <div class="form-group">
                        <label>Runtime (minutes)</label>
                        <input type="number" name="duration_minutes"
                            value="<?php echo htmlspecialchars($movie['duration_minutes']); ?>" min="1">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Director</label>
                        <input type="text" name="director" value="<?php echo htmlspecialchars($movie['director']); ?>" placeholder="Director Name">
                    </div>
                    <div class="form-group">
                        <label>Producer</label>
                        <input type="text" name="producer" value="<?php echo htmlspecialchars($movie['producer']); ?>" placeholder="Producer Name">
                    </div>
                </div>

                <div class="form-group">
                    <label>Cast / Actors</label>
                    <input type="text" name="actors" value="<?php echo htmlspecialchars($movie['actors']); ?>" placeholder="e.g. Tom Holland, Zendaya, Jacob Batalon">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Ticket Sales Start Date</label>
                        <input type="date" name="ticket_start_date" value="<?php echo htmlspecialchars($movie['ticket_start_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Ticket Sales End Date</label>
                        <input type="date" name="ticket_end_date" value="<?php echo htmlspecialchars($movie['ticket_end_date'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Release Date</label>
                        <input type="date" name="release_date"
                            value="<?php echo htmlspecialchars($movie['release_date']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Listing Status</label>
                        <select name="status">
                            <option value="now_showing" <?php echo $movie['status'] === 'now_showing' ? 'selected' : ''; ?>>Now Showing</option>
                            <option value="coming_soon" <?php echo $movie['status'] === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?php echo !empty($movie['is_featured']) ? 'checked' : ''; ?>>
                        Feature in homepage carousel (Max 5 items, oldest rotates out)
                    </label>
                    <p class="current-file-note">Independent of Now Showing / Coming Soon — check this to add the movie to the main carousel banner.</p>
                </div>

                <div class="form-group">
                    <label>Synopsis / Description</label>
                    <textarea name="description"><?php echo htmlspecialchars($movie['description']); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Poster Image</label>
                        <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">
                        <?php if ($movie['poster_url']): ?>
                            <p class="current-file-note">Current: <?php echo htmlspecialchars($movie['poster_url']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Background Image (banner)</label>
                        <input type="file" name="backdrop" accept=".jpg,.jpeg,.png,.webp">
                        <?php if ($movie['backdrop_url']): ?>
                            <p class="current-file-note">Current: <?php echo htmlspecialchars($movie['backdrop_url']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Trailer Link (URL)</label>
                    <input type="url" name="trailer_url" value="<?php echo htmlspecialchars($movie['trailer_url'] ?? ''); ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-admin btn-admin-primary">
                        <i class="fa-solid fa-check"></i> <?php echo $isEdit ? 'Save Changes' : 'Add Movie'; ?>
                    </button>
                    <a href="manage_movies.php" class="btn-admin btn-admin-outline">Cancel</a>
                </div>
            </form>
        </main>
    </div>

</body>

</html>