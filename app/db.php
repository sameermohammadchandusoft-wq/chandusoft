<?php
// /app/db.php

// Include the helpers file to access setup_error_handling() and log_error()
require_once __DIR__ . '/helpers.php';

// Set up error handling based on the environment
setup_error_handling('production'); // Change to 'development' in the development environment

// Database connection details
$host = 'localhost';
$db   = 'chandusoft';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DSN (Data Source Name) for the PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options for error handling and fetching
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Create a new PDO instance for database connection
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log error if database connection fails
    log_error("Database Connection Failed: " . $e->getMessage());

    // Display a user-friendly error message if in development mode
    if (ini_get('display_errors')) {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    } else {
        die("Service temporarily unavailable. Please try again later.");
    }
}
?>