<?php
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