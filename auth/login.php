<?php
// Real, database-backed login. This is the actual login processor now -
// the page the user sees is the styled one at /login/login.html, which
// POSTs here. If someone hits this file directly with GET (no form
// submission), we just send them to the styled page instead of showing a
// bare form.

session_start();
require '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // username OR email
    $password = $_POST['password'] ?? '';

    if ($identifier && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $next = $_GET['next'] ?? '';
            if ($next) {
                header("Location: ../" . ltrim($next, '/'));
            } else {
                header("Location: ../index.php");
            }
            exit;
        }
    }

    // Any failure (missing fields, no matching user, wrong password) sends
    // back to the login page with an error flag.
    header("Location: ../login/login.php?error=1");
    exit;
}

// Direct GET visit - there's no bare form here anymore, go to the real page.
header("Location: ../login/login.php");
exit;
