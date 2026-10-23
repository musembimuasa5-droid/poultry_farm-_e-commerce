<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
$query = trim($_GET['q'] ?? '');
if ($query === '') { echo json_encode([]); exit; }
try {
    $stmt = db()->prepare('SELECT id, name, price, image FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY featured DESC LIMIT 6');
    $term = '%' . $query . '%';
    $stmt->execute([$term, $term]);
    echo json_encode($stmt->fetchAll());
} catch (Throwable $exception) { echo json_encode([]); }
