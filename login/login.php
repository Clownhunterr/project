<?php
session_start();

if(isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit();
}

require '../database/db.php';
$error = isset($_GET['error']) ? 1 : 0;
$errorMessage = '';

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
    
    // Any failure
    $error = 1;
    $errorMessage = 'Invalid username or password.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBooking | Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="login-box">

        <div class="logo">
            <img src="../img/logo.png" alt="Logo">
            <h1>CineBooking</h1>
            <p>Book Movies Anytime, Anywhere</p>
        </div>
        
        <?php if ($error): ?>
            <p style="color:red; text-align:center; margin-bottom: 10px;"><?php echo $errorMessage ?: 'Invalid login credentials.'; ?></p>
        <?php endif; ?>

        <form action="" method="POST">

            <div class="input-group">
                <label>Username or Email</label>
                <input type="text" name="identifier" placeholder="Enter your username or email" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>

                <div class="show-password">
                    <input type="checkbox" onclick="showPassword()">
                    <span>Show Password</span>
                </div>
            </div>

            <div class="options">

                <label>
                    <input type="checkbox">
                    Remember Me
                </label>

                <a href="/login/forgot_password.php">Forgot Password?</a>

            </div>

            <button type="submit">Login</button>

        </form>


        <div class="demo-credentials">
            <p><strong>Testing credentials</strong></p>
            <p>Username: <code>demo</code> (or Email: <code>demo@cinebooking.com</code>)</p>
            <p>Password: <code>demo123</code></p>
        </div>


        <div class="signup">
            Don't have an account?
            <a href="../signup/signup.php">Sign Up</a>
        </div>

    </div>

    <script src="script.js"></script>

</body>

</html>