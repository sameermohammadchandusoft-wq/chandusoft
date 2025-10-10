<?php
require_once __DIR__ . '/logger.php';
setup_error_handling('production'); // Change to 'production' on live server
 
$host = 'localhost';
$db   = 'chandusoft';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
 
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
 
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
 
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    log_error("Database Connection Failed: " . $e->getMessage());
    if (ini_get('display_errors')) {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    } else {
        die("Service temporarily unavailable. Please try again later.");
    }
}
