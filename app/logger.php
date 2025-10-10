<?php
/**
 * ------------------------------------------------------
 * Global Logger Utility
 * ------------------------------------------------------
 * Handles application-wide error, mail, and event logging.
 * Works safely in both development and production environments.
 */

function log_message($type, $message)
{
    $logDir = __DIR__ . '/../storage/logs';
    $logFile = $logDir . '/app.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] [$type] $message" . PHP_EOL;

    file_put_contents($logFile, $formatted, FILE_APPEND);
}

/**
 * Shortcut functions for common log types
 */
function log_error($message) {
    log_message('ERROR', $message);
}

function log_info($message) {
    log_message('INFO', $message);
}

function log_mail_failure($message) {
    log_message('MAIL', $message);
}

/**
 * ------------------------------------------------------
 * Configure PHP error behavior based on environment
 * ------------------------------------------------------
 * @param string $environment 'development' | 'production'
 */
function setup_error_handling($environment = 'production')
{
    $logFile = __DIR__ . '/../storage/logs/app.log';

    if ($environment === 'production') {
        // ✅ Hide errors from users but log internally
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', $logFile);
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    } else {
        // ✅ Show everything during development
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        ini_set('log_errors', 1);
        ini_set('error_log', $logFile);
        error_reporting(E_ALL);
    }

    // ✅ Convert uncaught exceptions into logs
    set_exception_handler(function ($e) {
        log_error("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        if (ini_get('display_errors')) {
            echo "<pre><strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</pre>";
        } else {
            echo "Service temporarily unavailable. Please try again later.";
        }
    });

    // ✅ Capture fatal errors too
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            log_error("Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}");
        }
    });
}
?>