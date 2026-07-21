<?php
require 'database/db.php';
$stmt = $pdo->query('SELECT * FROM movies');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
