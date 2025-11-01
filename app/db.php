<?php
// =============================================================
// app/db.php — Secure database connector using .env variables
// =============================================================

require_once __DIR__ . '/env.php';      // ✅ Loads .env variables
require_once __DIR__ . '/logger.php';   // ✅ For setup_error_handling(), log_error(), etc.
require_once __DIR__ . '/helpers.php';  // ✅ Utility functions

// ------------------------------------------------------
// Error Handling Mode (auto switch by ENV)
// ------------------------------------------------------
$envMode = $_ENV['APP_ENV'] ?? 'development';
setup_error_handling($envMode);

// ------------------------------------------------------
// Database Configuration from .env (with fallbacks)
// ------------------------------------------------------
$host    = $_ENV['DB_HOST'] ?? 'localhost';
$db      = $_ENV['DB_NAME'] ?? 'chandusoft';
$user    = $_ENV['DB_USER'] ?? 'root';
$pass    = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';

// ------------------------------------------------------
// PDO DSN and Options
// ------------------------------------------------------
$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// ------------------------------------------------------
// Try to connect to database
// ------------------------------------------------------
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Log success in dev mode
    if ($envMode === 'development') {
        log_info("✅ Connected to database '{$db}' at host '{$host}'");
    }

} catch (PDOException $e) {
    // Log the error
    log_error("❌ Database Connection Failed: " . $e->getMessage());

    // Show safe message based on environment
    if ($envMode === 'development') {
        die("<strong>Database connection failed:</strong> " . htmlspecialchars($e->getMessage()));
    } else {
        http_response_code(500);
        die("Service temporarily unavailable. Please try again later.");
    }
}
