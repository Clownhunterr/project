<?php
session_start();
require 'database/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to get notified.']);
    exit;
}

$userId = $_SESSION['user_id'];
$movieId = isset($_POST['movie_id']) ? (int) $_POST['movie_id'] : 0;

if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid movie.']);
    exit;
}

$stmt = $pdo->prepare("SELECT notification_id FROM notifications WHERE user_id = ? AND movie_id = ?");
$stmt->execute([$userId, $movieId]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ?");
    $stmt->execute([$existing['notification_id']]);
    echo json_encode(['success' => true, 'inWishlist' => false]);
} else {
    $stmt = $pdo->prepare("SELECT title FROM movies WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    $title = $stmt->fetchColumn() ?: 'This movie';

    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, movie_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $movieId, "$title is coming soon — we'll notify you closer to release."]);
    echo json_encode(['success' => true, 'inWishlist' => true]);
}