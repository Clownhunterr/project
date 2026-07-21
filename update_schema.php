<?php
require 'database/db.php';

$migrations = [
    // Rename image_path → poster_url (old migration)
    "ALTER TABLE movies CHANGE image_path poster_url VARCHAR(255)"
        => "Renamed image_path to poster_url",

    // Add columns that movie_functions.php expects
    "ALTER TABLE movies ADD COLUMN backdrop_url  VARCHAR(255) AFTER poster_url"
        => "Added backdrop_url",
    "ALTER TABLE movies ADD COLUMN title_img     VARCHAR(255) AFTER backdrop_url"
        => "Added title_img",
    "ALTER TABLE movies ADD COLUMN trailer_url   VARCHAR(255) AFTER title_img"
        => "Added trailer_url",
    "ALTER TABLE movies ADD COLUMN release_date  DATE         AFTER release_year"
        => "Added release_date",
    "ALTER TABLE movies ADD COLUMN status        ENUM('now_showing','coming_soon','archived') NOT NULL DEFAULT 'now_showing' AFTER release_date"
        => "Added status",
    "ALTER TABLE movies ADD COLUMN is_featured   TINYINT(1) NOT NULL DEFAULT 0 AFTER status"
        => "Added is_featured",
    "ALTER TABLE movies ADD COLUMN poster_url    VARCHAR(255) AFTER description"
        => "Added poster_url (if image_path rename above was skipped)",

    // booking_seats.id alias used in getPopularMovies query
    "ALTER TABLE booking_seats ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST"
        => "Added auto-increment id to booking_seats",
];

foreach ($migrations as $sql => $label) {
    try {
        $pdo->exec($sql);
        echo "✔ $label\n";
    } catch (PDOException $e) {
        // 1060 = duplicate column, 1054 = unknown column (rename already done), etc. — safe to ignore
        echo "– $label skipped: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Schema is up to date.\n";
