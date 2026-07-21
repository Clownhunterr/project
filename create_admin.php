<?php
require 'database/db.php';

try {
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'Admin User', 'admin@cinebooking.com', $hash, 'admin']);
    
    echo "Admin user created successfully!<br>";
    echo "Email (Username for login): admin@cinebooking.com<br>";
    echo "Password: admin123<br>";
    echo '<a href="admin/admin_login.php">Go to Admin Login</a>';
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation
        echo "Admin user already exists!<br>";
        echo "Email (Username for login): admin@cinebooking.com<br>";
        echo "Password: admin123<br>";
        echo '<a href="admin/admin_login.php">Go to Admin Login</a>';
    } else {
        echo "Error: " . $e->getMessage();
    }
}
