<?php
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
            header("Location: /login/login.html?registered=1");
            exit;
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Register</title></head>
<body>
    <h2>Register</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required><br><br>
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="/login/login.html">Login</a></p>
</body>
</html>
