<?php
session_start();
require 'database/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage notifications.']);
    exit;
}

$userId = $_SESSION['user_id'];
$movieId = isset($_POST['movie_id']) ? (int) $_POST['movie_id'] : 0;

if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid movie.']);
    exit;
}

try {
    // Check if notification request exists (using the notifications table as a subscription placeholder, 
    // or we use wishlist as subscription. Let's just create a generic 'notification' record if not exists)
    $stmt = $pdo->prepare("SELECT notification_id FROM notifications WHERE user_id = ? AND movie_id = ? AND is_read = 0");
    $stmt->execute([$userId, $movieId]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
        $stmt->execute([$existing['notification_id']]);
        echo json_encode(['success' => true, 'inWishlist' => false]);
    } else {
        $msg = "You are now subscribed to notifications for this movie!";
        $title = "Movie Notification";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, movie_id, title, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $movieId, $title, $msg]);
        echo json_encode(['success' => true, 'inWishlist' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
