-- CineBooking database schema
--
-- NOTE FOR SUMAN/SHATTERED: the old version of this file had two bugs that
-- would have broken the whole site:
--   1. It created a database called `movie_booking`, but database/db.php
--      actually connects to `movie_ticket_db`. Standardized on
--      `movie_ticket_db` here to match db.php.
--   2. The old `users` table only had (id, email, password, created_at), but
--      login.php / register.php / profile.php all query columns like
--      user_id, name, password_hash, role - none of which existed. Rebuilt
--      the table to match what the PHP code actually expects, plus a new
--      `username` column so login works with either username or email.
--
-- The other tables (movies, halls, seats, showtimes, bookings,
-- booking_seats, wishlist) didn't exist at all before - profile.php already
-- queries all of them. They're created below with no sample data (except one
-- demo user), so the profile page will just show its existing "empty state"
-- messages until real movies/bookings are added.

CREATE DATABASE IF NOT EXISTS movie_ticket_db;

USE movie_ticket_db;

-- ---------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Demo account so login works immediately, without registering first.
-- Username: demo | Email: demo@cinebooking.com | Password: demo123
-- (password_hash below is a real bcrypt hash of "demo123", verified to work
-- with PHP's password_verify())
INSERT INTO users (username, name, email, password_hash, role)
VALUES (
    'demo',
    'Demo User',
    'demo@cinebooking.com',
    '$2b$10$g4hcaPfO9diugnTHA89TMO3oIwLDAJneqwKg3z31O2d4oFTPTwwKy',
    'customer'
)
ON DUPLICATE KEY UPDATE username = username;

-- ---------------------------------------------------------------------
-- Movies
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movies (
    movie_id         INT AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(150) NOT NULL,
    genre             VARCHAR(50),
    duration_minutes  INT,
    description       TEXT,
    poster_image      VARCHAR(255),
    release_year      YEAR,
    age_rating        VARCHAR(10),
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------------
-- Halls
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS halls (
    hall_id     INT AUTO_INCREMENT PRIMARY KEY,
    hall_name   VARCHAR(50) NOT NULL,
    total_seats INT NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------------------
-- Seats (belong to a hall)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seats (
    seat_id     INT AUTO_INCREMENT PRIMARY KEY,
    hall_id     INT NOT NULL,
    seat_row    VARCHAR(5) NOT NULL,
    seat_number INT NOT NULL,
    seat_type   ENUM('regular', 'premium') NOT NULL DEFAULT 'regular',
    FOREIGN KEY (hall_id) REFERENCES halls(hall_id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat_per_hall (hall_id, seat_row, seat_number)
);

-- ---------------------------------------------------------------------
-- Showtimes (a movie playing in a hall at a specific date/time)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS showtimes (
    showtime_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id    INT NOT NULL,
    hall_id     INT NOT NULL,
    show_date   DATE NOT NULL,
    show_time   TIME NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES halls(hall_id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Bookings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    booking_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    showtime_id   INT NOT NULL,
    barcode_value VARCHAR(50) NOT NULL UNIQUE,
    total_amount  DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status        ENUM('confirmed', 'cancelled', 'pending') NOT NULL DEFAULT 'confirmed',
    checked_in    TINYINT(1) NOT NULL DEFAULT 0,
    booked_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(showtime_id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Booking <-> Seats (junction table - one booking can cover many seats)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_seats (
    booking_id INT NOT NULL,
    seat_id    INT NOT NULL,
    PRIMARY KEY (booking_id, seat_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(seat_id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Wishlist
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    movie_id    INT NOT NULL,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wish (user_id, movie_id)
);
