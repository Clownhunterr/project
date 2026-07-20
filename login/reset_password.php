<?php
session_start();
require '../database/db.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;
$validToken = false;
$user = null;

if ($token) {
    $stmt = $pdo->prepare("SELECT user_id, reset_token_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user && strtotime($user['reset_token_expires']) > time()) {
        $validToken = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?");
        $stmt->execute([$hashed, $user['user_id']]);
        $success = true;
        $validToken = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBooking | Reset Password</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="login-box">

        <div class="logo">
            <img src="/img/logo.png" alt="Logo">
            <h1>CineBooking</h1>
            <p>Set a new password</p>
        </div>

        <?php if ($success): ?>
            <div class="demo-credentials">
                <p>Your password has been updated. You can now log in with your new password.</p>
            </div>
            <div class="signup">
                <a href="login.html">Go to Login</a>
            </div>

        <?php elseif (!$token || !$user): ?>
            <div class="demo-credentials">
                <p>This reset link is invalid. Please request a new one.</p>
            </div>
            <div class="signup">
                <a href="forgot_password.php">Request New Link</a>
            </div>

        <?php elseif (!$validToken): ?>
            <div class="demo-credentials">
                <p>This reset link has expired. Please request a new one.</p>
            </div>
            <div class="signup">
                <a href="forgot_password.php">Request New Link</a>
            </div>

        <?php else: ?>
            <?php if ($error): ?>
                <div class="demo-credentials">
                    <p style="color:#ff6b6b;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <label>New Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password" required
                        minlength="6">
                    <div class="show-password">
                        <input type="checkbox" onclick="showPassword()">
                        <span>Show Password</span>
                    </div>
                </div>

                <div class="input-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required
                        minlength="6">
                </div>

                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

    </div>

    <script src="script.js"></script>

</body>

</html>