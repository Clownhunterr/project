<?php
require 'database/db.php';
try {
    $pdo->exec("ALTER TABLE movies CHANGE image_path poster_url VARCHAR(255)");
    echo "Renamed image_path to poster_url\n";
} catch (Exception $e) {
    echo "Error renaming image_path: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE movies ADD COLUMN backdrop_url VARCHAR(255) AFTER poster_url");
    echo "Added backdrop_url column\n";
} catch (Exception $e) {
    echo "Error adding backdrop_url: " . $e->getMessage() . "\n";
}
