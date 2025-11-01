<?php
/**
 * ------------------------------------------------------
 * Chandusoft Global Logger Utility (Safe Version)
 * ------------------------------------------------------
 * - Logs to /storage/logs/app.log
 * - Sends logs to Mailpit inbox (Laragon)
 * - Handles errors, exceptions, and fatal shutdowns
 * - ✅ Safe to include multiple times (no redeclare errors)
 * ------------------------------------------------------
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * ------------------------------------------------------
 * Core Logger Function
 * ------------------------------------------------------
 */
if (!function_exists('log_message')) {
    function log_message($type, $message)
    {
        $logDir  = __DIR__ . '/../storage/logs';
        $logFile = $logDir . '/app.log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $script    = $_SERVER['SCRIPT_NAME'] ?? 'CLI';
        $uri       = $_SERVER['REQUEST_URI'] ?? '';
        $host      = $_SERVER['HTTP_HOST'] ?? php_uname('n');

        $formatted = "[$timestamp] [$type] [$host] [$script] $message" . PHP_EOL;

        // ✅ Write to local log file
        file_put_contents($logFile, $formatted, FILE_APPEND);

        // ✅ Send same log to Mailpit inbox (if available)
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = '127.0.0.1';   // Mailpit SMTP host
            $mail->Port = 1025;          // Mailpit SMTP port
            $mail->SMTPAuth = false;

            $mail->setFrom('logger@chandusoft.test', 'Chandusoft Logger');
            $mail->addAddress('admin@chandusoft.test'); // Mailpit inbox

            $mail->Subject = "LOG [$type] - Chandusoft";
            $mail->isHTML(true);
            $mail->Body = "
                <div style='font-family: monospace; background:#f7f7f7; padding:10px; border:1px solid #ccc;'>
                    <strong>Timestamp:</strong> {$timestamp}<br>
                    <strong>Type:</strong> {$type}<br>
                    <strong>Host:</strong> {$host}<br>
                    <strong>Script:</strong> {$script}<br>
                    <strong>URI:</strong> {$uri}<br><br>
                    <strong>Message:</strong><br>
                    <pre style='background:#fff; border:1px solid #ddd; padding:8px; border-radius:4px;'>{$message}</pre>
                </div>
            ";
            $mail->AltBody = $formatted;
            $mail->send();
        } catch (Exception $e) {
            // Mailpit or SMTP failed — log locally
            $failMsg = "MAIL-FAIL: " . $e->getMessage();
            file_put_contents($logFile, "[$timestamp] [MAIL] $failMsg" . PHP_EOL, FILE_APPEND);
        }
    }
}

/**
 * ------------------------------------------------------
 * Shortcut Wrappers
 * ------------------------------------------------------
 */
if (!function_exists('log_error')) {
    function log_error($message) { log_message('ERROR', $message); }
}
if (!function_exists('log_info')) {
    function log_info($message) { log_message('INFO', $message); }
}
if (!function_exists('log_warning')) {
    function log_warning($message) { log_message('WARNING', $message); }
}

/**
 * ------------------------------------------------------
 * Error / Exception Handling
 * ------------------------------------------------------
 */
if (!function_exists('setup_error_handling')) {
    function setup_error_handling($environment = 'production')
    {
        $logFile = __DIR__ . '/../storage/logs/app.log';

        if ($environment === 'production') {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', $logFile);
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        } else {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            ini_set('log_errors', 1);
            ini_set('error_log', $logFile);
            error_reporting(E_ALL);
        }

        // ✅ Handle uncaught exceptions
        set_exception_handler(function ($e) {
            $msg = "Uncaught Exception: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}";
            log_error($msg);

            if (ini_get('display_errors')) {
                echo "<pre><strong>Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</pre>";
            } else {
                echo "Service temporarily unavailable. Please try again later.";
            }
        });

        // ✅ Handle fatal shutdown errors
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                $msg = "Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}";
                log_error($msg);
            }
        });
    }
}
?>
