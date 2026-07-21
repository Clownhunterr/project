<?php
require 'admin_auth_check.php';
require '../database/db.php';

$deleteError = '';

if (isset($_GET['delete'])) {
    $movieId = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM movies WHERE movie_id = ?");
        $stmt->execute([$movieId]);
        header("Location: manage_movies.php");
        exit;
    } catch (PDOException $e) {
        $deleteError = "Cannot delete this movie — it has existing showtimes or bookings linked to it.";
    }
}

$movies = $pdo->query("SELECT * FROM movies ORDER BY movie_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
    <title>CineBooking | Manage Movies</title>
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <div class="admin-layout">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-toolbar">
                <h1 class="admin-page-title">Manage Movies</h1>
                <a href="movie_form.php" class="btn-admin btn-admin-primary">
                    <i class="fa-solid fa-plus"></i> Add Movie
                </a>
            </div>

            <?php if ($deleteError): ?>
                <div class="admin-alert admin-alert-error"><?php echo htmlspecialchars($deleteError); ?></div>
            <?php endif; ?>

            <?php if (count($movies) === 0): ?>
                <div class="admin-empty">No movies added yet. Click "Add Movie" to get started.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Duration</th>
                            <th>Release Date</th>
                            <th>Status</th>
                            <th>Carousel</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td>
                                    <?php if ($movie['poster_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($movie['poster_url']); ?>" class="thumb" alt="">
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                                <td><?php echo (int) $movie['duration_minutes']; ?> min</td>
                                <td><?php echo $movie['release_date'] ? date('M j, Y', strtotime($movie['release_date'])) : '—'; ?>
                                </td>
                                <td>
                                    <?php echo $movie['status'] === 'now_showing' ? 'Now Showing' : 'Coming Soon'; ?>
                                </td>
                                <td>
                                    <?php if (!empty($movie['is_featured'])): ?>
                                        <span class="role-badge role-admin">Featured</span>
                                    <?php else: ?>
                                        <span style="color: rgba(255,255,255,0.4);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="movie_form.php?id=<?php echo $movie['movie_id']; ?>"
                                            class="btn-admin btn-admin-outline btn-sm">
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </a>
                                        <a href="manage_movies.php?delete=<?php echo $movie['movie_id']; ?>"
                                            class="btn-admin btn-admin-danger btn-sm"
                                            onclick="return confirm('Delete this movie? This cannot be undone.');">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

</body>

</html>