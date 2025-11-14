<?php
// ------------------------------------------------------------
// app/env.php — Safe & consistent .env loader
// ------------------------------------------------------------



if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

$envPath = APP_ROOT . '/.env';

if (!file_exists($envPath)) {
    throw new Exception('.env file not found: ' . $envPath);
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    // Skip comments and empty lines
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    // Skip malformed lines (no "=")
    if (!str_contains($line, '=')) {
        continue;
    }

    // Split into key=value (limit to 2 parts)
    [$key, $value] = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value, " \t\n\r\0\x0B\"'");

    if ($key === '') continue;

    // Store in global environments
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

/**
 * env($key, $default)
 * Safe helper to fetch environment variables.
 */
function env(string $key, $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($value !== false && $value !== null && $value !== '') ? $value : $default;
}


// ------------------------------------------------------------
// Force HTTPS if FORCE_HTTPS=true
// ------------------------------------------------------------
if (env('FORCE_HTTPS', 'false') === 'true') {

    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

    if (!$isHttps) {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $url  = 'https://' . $host . $uri;

        // Permanent redirect
        header('Location: ' . $url, true, 301);
        exit;
    }
}

