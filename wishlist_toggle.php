<?php
session_start();
require 'database/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to use your wishlist.']);
    exit;
}

$userId = $_SESSION['user_id'];
$movieId = isset($_POST['movie_id']) ? (int) $_POST['movie_id'] : 0;

if ($movieId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid movie.']);
    exit;
}

$stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND movie_id = ?");
$stmt->execute([$userId, $movieId]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE wishlist_id = ?");
    $stmt->execute([$existing['wishlist_id']]);
    echo json_encode(['success' => true, 'inWishlist' => false]);
} else {
    $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, movie_id) VALUES (?, ?)");
    $stmt->execute([$userId, $movieId]);
    echo json_encode(['success' => true, 'inWishlist' => true]);
}
