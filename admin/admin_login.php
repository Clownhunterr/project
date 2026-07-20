<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../database/db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['user_id'];
        $_SESSION['admin_name'] = $admin['name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
    <title>CineBooking | Admin Login</title>
</head>

<body class="admin-login-body">

    <div class="admin-login-box">
        <div class="admin-login-logo">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>CineBooking Admin</h1>
            <p>Staff access only</p>
        </div>

        <?php if ($error): ?>
            <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter admin username" required autofocus>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter admin password" required>
            </div>
            <button type="submit">Login to Dashboard</button>
        </form>

        <a href="../index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to site</a>
    </div>

</body>

</html>