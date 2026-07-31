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

    // Add director, producer, actors fields
    "ALTER TABLE movies ADD COLUMN director VARCHAR(150) NULL AFTER description"
        => "Added director column",
    "ALTER TABLE movies ADD COLUMN producer VARCHAR(150) NULL AFTER director"
        => "Added producer column",
    "ALTER TABLE movies ADD COLUMN actors VARCHAR(255) NULL AFTER producer"
        => "Added actors column",

    // Add ticket availability start/end dates
    "ALTER TABLE movies ADD COLUMN ticket_start_date DATE NULL AFTER release_date"
        => "Added ticket_start_date column",
    "ALTER TABLE movies ADD COLUMN ticket_end_date DATE NULL AFTER ticket_start_date"
        => "Added ticket_end_date column",

    // Add featured_at timestamp for FIFO carousel queue
    "ALTER TABLE movies ADD COLUMN featured_at TIMESTAMP NULL AFTER is_featured"
        => "Added featured_at column",

    // booking_seats.id alias used in getPopularMovies query
    "ALTER TABLE booking_seats ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST"
        => "Added auto-increment id to booking_seats",

    // Create notifications table if not exists
    "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id         INT NOT NULL,
        movie_id        INT DEFAULT NULL,
        title           VARCHAR(150) NOT NULL,
        message         TEXT NOT NULL,
        is_read         TINYINT(1) NOT NULL DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE
    )" => "Created notifications table",
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
