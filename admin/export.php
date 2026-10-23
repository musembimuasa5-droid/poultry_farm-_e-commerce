<?php
require_once '../config.php';
if (!current_user() || current_user()['role'] !== 'admin') redirect('../login.php');
$type = $_GET['type'] ?? 'orders';
$filename = $type === 'customers' ? 'customers.csv' : 'orders.csv';
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="' . $filename . '"');
$out = fopen('php://output', 'w');
if ($type === 'customers') {
    fputcsv($out, ['Name', 'Email', 'Phone', 'Status', 'Joined']);
    $rows = db()->query("SELECT full_name,email,phone,status,created_at FROM users WHERE role='customer' ORDER BY created_at DESC");
} else {
    fputcsv($out, ['Order', 'Customer', 'Total', 'Payment', 'Status', 'Created']);
    $rows = db()->query('SELECT o.id,u.full_name,o.total,o.payment_method,o.status,o.created_at FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC');
}
while ($row = $rows->fetch(PDO::FETCH_NUM)) fputcsv($out, $row);
fclose($out); exit;
