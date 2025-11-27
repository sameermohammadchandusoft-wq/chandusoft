<?php
// app/auth.php

// ---------------------------------------------
// SECURE SESSION START
// ---------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', 1);       // HTTPS only
    ini_set('session.cookie_httponly', 1);     // No JS access
    ini_set('session.cookie_samesite', 'Lax'); // Protect from CSRF
    session_start();
}

require_once __DIR__ . '/db.php'; // $pdo connection


// ---------------------------------------------
// AUTO-LOGIN USING REMEMBER TOKEN
// ---------------------------------------------
if (empty($_SESSION['user']) && !empty($_COOKIE['remember_token'])) {

    $token = $_COOKIE['remember_token'];

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, role, remember_expiry 
            FROM users 
            WHERE remember_token = ? LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            // Check expiry
            if (!empty($user['remember_expiry']) && strtotime($user['remember_expiry']) > time()) {

                // Auto-login only if not logging out
                if (!isset($_GET['logout'])) {
                    login($user['id'], $user['name'], $user['role']);
                }

            } else {
                // Token expired → delete cookie
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            }
        }

    } catch (Exception $e) {
        error_log("Remember-me auto-login failed: " . $e->getMessage());
    }
}


// ---------------------------------------------
// LOGIN
// ---------------------------------------------
function login($id, $name, $role = 'user')
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'   => $id,
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'role' => $role
    ];
}


// ---------------------------------------------
// LOGOUT
// ---------------------------------------------
function logout()
{
    $_SESSION = [];

    // Destroy session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    // Delete remember me cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);

    session_destroy();
}


// ---------------------------------------------
// CURRENT USER
// ---------------------------------------------
function current_user()
{
    return $_SESSION['user'] ?? null;
}


// ---------------------------------------------
// AUTH CHECK
// ---------------------------------------------
function is_auth()
{
    return isset($_SESSION['user']);
}


// ---------------------------------------------
// REQUIRE LOGIN
// ---------------------------------------------
function require_auth()
{
    if (!is_auth()) {
        header("Location: /login");
        exit;
    }
}


// ---------------------------------------------
// ROLE CHECK
// ---------------------------------------------
function can($roles)
{
    if (!is_auth()) return false;

    $user_role = $_SESSION['user']['role'] ?? 'user';

    return is_array($roles)
        ? in_array($user_role, $roles)
        : $user_role === $roles;
}


// ---------------------------------------------
// REDIRECT
// ---------------------------------------------
function redirect($url)
{
    header("Location: $url");
    exit;
}


// ---------------------------------------------
// FLASH MESSAGES
// ---------------------------------------------
function set_flash($key, $message)
{
    $_SESSION['flash_messages'][$key] = $message;
}

function get_flash($key)
{
    if (!isset($_SESSION['flash_messages'][$key])) return null;

    $msg = $_SESSION['flash_messages'][$key];
    unset($_SESSION['flash_messages'][$key]);
    return $msg;
}


// ---------------------------------------------
// LOGIN LOGGING
// ---------------------------------------------
function log_login_attempt($email, $success)
{
    if (!$success) {
        log_error("Login FAILED for email: {$email}");
    }
}


// ---------------------------------------------
// LOGGING HELPERS
// ---------------------------------------------
if (!function_exists('log_info')) {
    function log_info($message)
    {
        $file = __DIR__ . '/../storage/logs/app.log';
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);

        $entry = "[" . date('Y-m-d H:i:s') . "] INFO: $message\n";
        file_put_contents($file, $entry, FILE_APPEND);
    }
}

if (!function_exists('log_error')) {
    function log_error($message)
    {
        $file = __DIR__ . '/../storage/logs/app.log';
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);

        $entry = "[" . date('Y-m-d H:i:s') . "] ERROR: $message\n";
        file_put_contents($file, $entry, FILE_APPEND);
    }
}


// ---------------------------------------------
// OAUTH: LOGIN OR REGISTER
// ---------------------------------------------
function login_or_register_oauth_user($name, $email)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        login($user['id'], $name, 'user');
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?, ?, '', 'user')");
        $stmt->execute([$name, $email]);
        login($pdo->lastInsertId(), $name, 'user');
    }
}

?>


