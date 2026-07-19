<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <nav>
        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>
        <a href="manage_movies.php" class="<?php echo in_array($currentPage, ['manage_movies.php', 'movie_form.php']) ? 'active' : ''; ?>">
            <i class="fa-solid fa-film"></i> Manage Movies
        </a>
        <a href="manage_users.php" class="<?php echo in_array($currentPage, ['manage_users.php', 'user_details.php']) ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Manage Users
        </a>
    </nav>
</aside>
