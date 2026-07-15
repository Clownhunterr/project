<?php
/*
 * ARCHIVED: This was a placeholder that just faked a login session instead
 * of really registering anyone. signup/signup.html now posts directly to
 * ../auth/register.php, which does real database registration (with a
 * name + username + email + password, all validated and checked for
 * duplicates). Kept here for reference only; not linked from anywhere in
 * the live site.
 */

session_start();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email && $password) {
    $_SESSION['user_id'] = 1;
    $_SESSION['name'] = "Demo User";
    $_SESSION['role'] = "customer";
    header("Location: ../home.php");
    exit;
} else {
    header("Location: signup.html?error=1");
    exit;
}
