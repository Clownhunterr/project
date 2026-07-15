<?php
/*
 * ARCHIVED: This was the old demo-only login handler (hardcoded
 * demo@cinebooking.com / demo123, no database). login/login.html now posts
 * to ../auth/login.php instead, which does real database authentication
 * (and the same demo credentials still work - they're seeded into the
 * database in database/database.sql). Kept here for reference only; not
 * linked from anywhere in the live site.
 */
session_start();

// Demo/testing credentials — no database required
$demoEmail = "demo@cinebooking.com";
$demoPassword = "demo123";

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === $demoEmail && $password === $demoPassword) {
    $_SESSION['user_id'] = 1;
    $_SESSION['name'] = "Demo User";
    $_SESSION['role'] = "customer";
    header("Location: ../home.php");
    exit;
} else {
    header("Location: login.html?error=1");
    exit;
}
