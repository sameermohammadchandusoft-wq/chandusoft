<?php
// /app/helpers.php

/**
 * ------------------------------------------------------
 * setup_error_handling($env)
 * Configures error handling based on the environment.
 * ------------------------------------------------------
 * @param string $env: The environment ('development' or 'production')
 * ------------------------------------------------------
 */
function setup_error_handling($env) {
    if ($env === 'development') {
        // Display errors to the browser in development
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
    } elseif ($env === 'production') {
        // Do not display errors to the browser in production
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        // Log errors to a file (you can customize this as needed)
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/../storage/logs/app.log');
    }
}

/**
 * ------------------------------------------------------
 * log_error($message)
 * Logs errors to a specific log file.
 * ------------------------------------------------------
 * @param string $message: The error message to log
 * ------------------------------------------------------
 */


?>