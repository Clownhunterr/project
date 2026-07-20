<?php
session_start();

if(isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit();
}

require '../database/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && $username && $email && $password) {
        $stmt = $pdo->prepare("SELECT user_id, email, username FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $existing = $stmt->fetch();

        if ($existing && $existing['email'] === $email) {
            $error = "Email already registered.";
        } elseif ($existing && $existing['username'] === $username) {
            $error = "Username already taken.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$name, $username, $email, $hashedPassword]);
            header("Location: ../login/login.php?registered=1");
            exit;
        }
    } else {
        $error = "All fields are required.";
    }
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBooking | Sign Up</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="signup.css">
</head>
<body>

<div class="signup-box">

    <div class="logo">
        <img src="../img/logo.png" alt="Logo">
        <h1>CineBooking</h1>
        <p>Create Your Account</p>
    </div>
    
    <?php if ($error): ?>
        <p style="color:red; text-align:center; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="" method="POST">

        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Enter your full name" required>
        </div>

        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Choose a username" required>
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" id="password" name="password" placeholder="Create password" required>
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" id="confirmPassword" placeholder="Confirm password" required>
        </div>

        <div class="show-password">
            <input type="checkbox" onclick="togglePassword()">
            <span>Show Password</span>
        </div>

        <button type="submit">Sign Up</button>

    </form>

    <div class="login-link">
        <p>Already have an account?
            <a href="../login/login.php">Login</a>
        </p>
    </div>

</div>

<script src="signup.js"></script>

</body>
</html>