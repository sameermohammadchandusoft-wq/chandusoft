<?php
// /app/helpers.php

/**
 * ------------------------------------------------------
 * Chandusoft Helper Functions
 * ------------------------------------------------------
 * Keep this file for generic utilities only.
 * Do NOT redefine setup_error_handling() here
 * (it already exists in /app/logger.php)
 * ------------------------------------------------------
 */

/**
 * Log a custom error message (shortcut)
 * Uses the logger.php global log_error() function if available.
 */
if (!function_exists('log_error')) {
    function log_error($message) {
        $logDir  = __DIR__ . '/../storage/logs';
        $logFile = $logDir . '/app.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[$timestamp] [HELPER-ERROR] $message" . PHP_EOL;
        file_put_contents($logFile, $formatted, FILE_APPEND);
    }
}

/**
 * Example generic helper function (optional)
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
?>
