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
    'poster_url' => '',
    'backdrop_url' => '',
    'trailer_url' => '',
    'release_date' => '',
    'status' => 'now_showing',
    'is_featured' => 0
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
    $releaseDate = $_POST['release_date'] ?? null;
    $status = ($_POST['status'] ?? 'now_showing') === 'coming_soon' ? 'coming_soon' : 'now_showing';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    $posterUrl = $movie['poster_url'];
    $backdropUrl = $movie['backdrop_url'];
    $trailerUrl = $movie['trailer_url'];

    $posterResult = handleUpload($_FILES['poster'] ?? [], 'uploads/posters/', ['jpg', 'jpeg', 'png', 'webp']);
    $backdropResult = handleUpload($_FILES['backdrop'] ?? [], 'uploads/backdrops/', ['jpg', 'jpeg', 'png', 'webp']);
    $trailerResult = handleUpload($_FILES['trailer'] ?? [], 'uploads/trailers/', ['mp4', 'webm', 'mov']);

    if ($posterResult === false) {
        $error = "Poster must be a JPG, PNG, or WEBP image.";
    } elseif ($backdropResult === false) {
        $error = "Background image must be a JPG, PNG, or WEBP image.";
    } elseif ($trailerResult === false) {
        $error = "Trailer must be an MP4, WEBM, or MOV file.";
    } elseif ($title === '') {
        $error = "Title is required.";
    } else {
        if ($posterResult)
            $posterUrl = $posterResult;
        if ($backdropResult)
            $backdropUrl = $backdropResult;
        if ($trailerResult)
            $trailerUrl = $trailerResult;

        if ($isEdit) {
            $stmt = $pdo->prepare("
                UPDATE movies
                SET title=?, genre=?, age_rating=?, duration_minutes=?, description=?,
                    poster_url=?, backdrop_url=?, trailer_url=?, release_date=?, status=?, is_featured=?
                WHERE movie_id=?
            ");
            $stmt->execute([
                $title,
                $genre,
                $ageRating,
                $duration,
                $description,
                $posterUrl,
                $backdropUrl,
                $trailerUrl,
                $releaseDate,
                $status,
                $isFeatured,
                $movie['movie_id']
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO movies (title, genre, age_rating, duration_minutes, description, poster_url, backdrop_url, trailer_url, release_date, status, is_featured)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $title,
                $genre,
                $ageRating,
                $duration,
                $description,
                $posterUrl,
                $backdropUrl,
                $trailerUrl,
                $releaseDate,
                $status,
                $isFeatured
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
    $movie['release_date'] = $releaseDate;
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
                        Feature in homepage carousel
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
                    <label>Trailer (local video file)</label>
                    <input type="file" name="trailer" accept=".mp4,.webm,.mov">
                    <?php if ($movie['trailer_url']): ?>
                        <p class="current-file-note">Current: <?php echo htmlspecialchars($movie['trailer_url']); ?></p>
                    <?php endif; ?>
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