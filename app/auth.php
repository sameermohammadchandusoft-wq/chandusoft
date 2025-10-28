<?php
// app/auth.php

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ------------------------------------------------------
 * login($id, $name, $role)
 * Stores user info in session and regenerates session ID.
 * ------------------------------------------------------
 */
function login($id, $name, $role = 'user') {
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'   => $id,
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'role' => $role
    ];
}

/**
 * ------------------------------------------------------
 * logout()
 * Clears session and destroys session data.
 * ------------------------------------------------------
 */
function logout() {
    // Clear session data
    $_SESSION = [];

    // Destroy session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    // Finally destroy session
    session_destroy();
}

/**
 * ------------------------------------------------------
 * current_user()
 * Returns the currently logged-in user or null.
 * ------------------------------------------------------
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * ------------------------------------------------------
 * is_auth()
 * Returns true if user is logged in.
 * ------------------------------------------------------
 */
function is_auth() {
    return isset($_SESSION['user']);
}

/**
 * ------------------------------------------------------
 * require_auth()
 * Protects pages that need authentication.
 * ------------------------------------------------------
 */
function require_auth() {
    if (!is_auth()) {
        // Redirect to admin login form
        header("Location: /login_form.php");
        exit;
    }
}

/**
 * ------------------------------------------------------
 * can($roles)
 * Role-based permission check.
 * Example:
 *   can('admin')
 *   can(['admin', 'editor'])
 * ------------------------------------------------------
 */
function can($roles) {
    if (!is_auth()) return false;
    $user_role = $_SESSION['user']['role'] ?? 'user';
    return is_array($roles)
        ? in_array($user_role, $roles)
        : $user_role === $roles;
}

/**
 * ------------------------------------------------------
 * redirect($url)
 * Simple helper for redirection.
 * ------------------------------------------------------
 */
function redirect($url) {
    header("Location: $url"); // ✅ Correct header function
    exit;
}


/**
 * Set a flash message
 */
function set_flash($key, $message) {
    $_SESSION['flash_messages'][$key] = $message;
}

function get_flash($key) {
    if (!isset($_SESSION['flash_messages'][$key])) return null;
    $msg = $_SESSION['flash_messages'][$key];
    unset($_SESSION['flash_messages'][$key]);
    return $msg;
}

// ✅ Log login attempts
/* function log_login_attempt($email, $success)
{
    $status = $success ? 'SUCCESS' : 'FAILED';
    log_error("Login {$status} for email: {$email}");
}*/
function log_login_attempt($email, $success)
{
    if (!$success) { // only log failures
        log_error("Login FAILED for email: {$email}");
    }
}

// ------------------------------------------------------
// Logging Helpers (used by register.php, login, etc.)
// ------------------------------------------------------
if (!function_exists('log_info')) {
    function log_info($message) {
        $file = __DIR__ . '/../storage/logs/app.log';
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $entry = sprintf("[%s] INFO: %s\n", date('Y-m-d H:i:s'), $message);
        file_put_contents($file, $entry, FILE_APPEND);
    }
}

if (!function_exists('log_error')) {
    function log_error($message) {
        $file = __DIR__ . '/../storage/logs/app.log';
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $entry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $message);
        file_put_contents($file, $entry, FILE_APPEND);
    }
}
?>
