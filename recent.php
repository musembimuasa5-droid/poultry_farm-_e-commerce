<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
$ids = array_values(array_filter(array_map('intval', explode(',', $_GET['ids'] ?? ''))));
if (!$ids) { echo json_encode([]); exit; }
$marks = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT id,name,price,image FROM products WHERE id IN ($marks)");
$stmt->execute($ids);
$rows = $stmt->fetchAll();
$byId = []; foreach ($rows as $row) $byId[$row['id']] = $row;
$result = []; foreach ($ids as $id) if (isset($byId[$id])) $result[] = $byId[$id];
echo json_encode($result);
