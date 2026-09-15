<?php

declare(strict_types=1);

const APP_NAME = 'Golden Eggs & Chicks Farm';
const APP_TAGLINE = 'Fresh farm eggs & healthy chicks delivered to your door.';
const BASE_URL = '/poultry_farm-_e-commerce/';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'golden_eggs';
const DB_USER = 'root';
const DB_PASS = '';
const LOW_STOCK_THRESHOLD = 5;

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $exception) {
            if ((int) $exception->errorInfo[1] !== 1049) {
                throw new RuntimeException(
                    'Unable to connect to MySQL. Check DB_HOST, DB_USER and DB_PASS in config.php.',
                    0,
                    $exception
                );
            }

            try {
                $server = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $options);
                $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    $options
                );

                $schemaPath = __DIR__ . '/database/schema.sql';
                if (is_readable($schemaPath)) {
                    $schema = file_get_contents($schemaPath);
                    $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema ?: '');
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if ($statement !== '' && stripos($statement, 'CREATE DATABASE') !== 0 && stripos($statement, 'USE ') !== 0) {
                            $pdo->exec($statement);
                        }
                    }
                }
            } catch (PDOException $bootstrapException) {
                throw new RuntimeException(
                    'The golden_eggs database does not exist and could not be created. Import database/schema.sql in phpMyAdmin, then reload this page.',
                    0,
                    $bootstrapException
                );
            }
        }
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $amount): string
{
    return 'KSh ' . number_format((float) $amount, 2);
}

function stock_message(int $stock): string
{
    if ($stock <= 0) return 'Currently unavailable';
    if ($stock <= LOW_STOCK_THRESHOLD) return 'Only ' . $stock . ' left';
    return 'In stock';
}

function rate_limit(string $key, int $limit = 8, int $window = 900): bool
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
