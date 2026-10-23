<?php

declare(strict_types=1);

const APP_NAME = 'Green Garden Farm';
const APP_TAGLINE = 'Healthy Poultry • Better Living';const BASE_URL = '/poultry_farm-_e-commerce/';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'golden_eggs';
const DB_USER = 'root';
const DB_PASS = '';
const LOW_STOCK_THRESHOLD = 5;
const DELIVERY_STANDARD_FEE = 250;
const FREE_DELIVERY_THRESHOLD = 5000;
const FARM_PHONE = '+254 712 345 678';
const FARM_EMAIL = 'hello@goldeneggs.farm';
const FARM_LOCATION = 'Limuru Road, Kiambu County';function rate_limit(string $key, int $limit = 8, int $window = 900): bool
{
    $now = time();
    $bucket = $_SESSION['rate_limits'][$key] ?? ['started' => $now, 'attempts' => 0];
    if ($now - $bucket['started'] >= $window) $bucket = ['started' => $now, 'attempts' => 0];
    $bucket['attempts']++;
    $_SESSION['rate_limits'][$key] = $bucket;
    return $bucket['attempts'] <= $limit;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

function cart_count(): int
{
    return array_sum($_SESSION['cart'] ?? []);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function log_admin_activity(string $action, string $entityType, ?int $entityId = null, string $details = ''): void
{
    if (!current_user() || current_user()['role'] !== 'admin') return;
    try {
        $stmt = db()->prepare('INSERT INTO admin_activity_log (admin_id, action, entity_type, entity_id, details) VALUES (?,?,?,?,?)');
        $stmt->execute([current_user()['id'], $action, $entityType, $entityId, $details]);
    } catch (Throwable $exception) {
        // Activity logging must not interrupt customer checkout or admin work.
    }
}
