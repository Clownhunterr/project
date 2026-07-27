<?php
// TEMPORARY: run this once to import database/database.sql, then DELETE this
// file and redeploy. Do not leave this on a public deployment.

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';

try {
    // Connect without selecting a database yet, since the .sql file creates it
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/database/database.sql');

    // Split on semicolons at end of statements (simple approach, fine for this file)
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    echo "<pre>";
    foreach ($statements as $stmt) {
        if ($stmt === '')
            continue;
        $pdo->exec($stmt);
        echo "OK: " . substr($stmt, 0, 60) . "...\n";
    }
    echo "\nMigration complete.</pre>";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}