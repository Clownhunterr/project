<?php
session_start();
require '../database/db.php';

$resetLink = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        $message = "If that account exists, a password reset link has been generated below.";

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?");
            $stmt->execute([$token, $expires, $user['user_id']]);

            $resetLink = "reset_password.php?token=" . urlencode($token);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBooking | Forgot Password</title>

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
            <p>Reset your password</p>
        </div>

        <?php if ($message): ?>
            <div class="demo-credentials">
                <p><?php echo htmlspecialchars($message); ?></p>
                <?php if ($resetLink): ?>
                    <p><a href="<?php echo htmlspecialchars($resetLink); ?>" style="color:#ff3700;">Click here to reset your
                            password</a></p>
                    <p style="font-size:12px; opacity:0.7;">(Shown directly since this project has no live mail server. This
                        link expires in 1 hour.)</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username or Email</label>
                <input type="text" name="identifier" placeholder="Enter your username or email" required>
            </div>

            <button type="submit">Send Reset Link</button>
        </form>

        <div class="signup">
            Remembered your password?
            <a href="login.html">Back to Login</a>
        </div>

    </div>

</body>

</html>