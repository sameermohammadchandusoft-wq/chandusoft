<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}


/**
 * Get a setting from the database.
 * Falls back to .env if not found in DB.
 */
function get_setting($key, $default = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        // If found in DB, return that; otherwise, check .env
        if ($value !== false) {
            return $value;
        }

        // Check if it exists in .env
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        return $default;
    } catch (Exception $e) {
        // error_log("get_setting error: " . $e->getMessage());
        return $default;
    }
}
