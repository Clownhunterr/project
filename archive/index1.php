<?php
/*
 * ARCHIVED: This was the placeholder homepage that used to load at the site
 * root before the real homepage (now home.php) was pointed to correctly.
 * It's kept here for reference only - it is not linked from anywhere in the
 * live site anymore. Safe to delete once you've confirmed you don't need it.
 * (Its own links - login.php, register.php, logout.php - refer to the old
 * root file locations and would need updating if you ever revive this page.)
 */
session_start(); // must be first line

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head><title>Home</title></head>
<body>
    <?php if ($isLoggedIn): ?>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
        <p>You are logged in as: <?php echo htmlspecialchars($_SESSION['role']); ?></p>
        <p><a href="logout.php">Logout</a></p>
    <?php else: ?>
        <h1>Welcome, Guest!</h1>
        <p><a href="login.php">Login</a> | <a href="register.php">Register</a></p>
    <?php endif; ?>

    <hr>
    <h2>Now Showing</h2>
    <p>(Movie listings will go here)</p>
</body>
</html>