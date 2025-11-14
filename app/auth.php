<?php
// app/auth.php
 
// -----------------------------
// Secure session start
// -----------------------------

// -----------------------------
// Secure session start
// -----------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_secure', 1);       // HTTPS only
    ini_set('session.cookie_httponly', 1);     // No JS access
    ini_set('session.cookie_samesite', 'Lax'); // Prevent CSRF
      session_start();
}

 
 
require_once __DIR__ . '/db.php'; // ensure $pdo available for DB queries
 
// -----------------------------
// Auto-login via remember_token
// -----------------------------
if (empty($_SESSION['user']) && !empty($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
 
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, remember_expiry FROM users WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if ($user && strtotime($user['remember_expiry']) > time()) {
            // Token valid → restore session
            login($user['id'], $user['name'], $user['role']);
 
            // Extend expiry (30 more days)
            $newExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmt = $pdo->prepare("UPDATE users SET remember_expiry = ? WHERE id = ?");
            $stmt->execute([$newExpiry, $user['id']]);
 
            // Refresh cookie
            setcookie('remember_token', $token, [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        } else {
            // Invalid or expired token → clear it
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    } catch (Exception $e) {
        error_log("Remember-me auto-login failed: " . $e->getMessage());
    }
}
 
/**
 * ------------------------------------------------------
 * login($id, $name, $role)
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
 * ------------------------------------------------------
 */
function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    // Clear remember_token
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    session_destroy();
}
 
/**
 * ------------------------------------------------------
 * current_user()
 * ------------------------------------------------------
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}
 
/**
 * ------------------------------------------------------
 * is_auth()
 * ------------------------------------------------------
 */
function is_auth() {
    return isset($_SESSION['user']);
}
 
/**
 * ------------------------------------------------------
 * require_auth()
 * ------------------------------------------------------
 */
function require_auth() {
    if (!is_auth()) {
        header("Location: /login");
        exit;
    }
}
 
/**
 * ------------------------------------------------------
 * can($roles)
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
 * ------------------------------------------------------
 */
function redirect($url) {
    header("Location: $url");
    exit;
}
 
/**
 * Flash messages
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
 
/**
 * Log login attempts
 */
function log_login_attempt($email, $success) {
    if (!$success) { // only log failures
        log_error("Login FAILED for email: {$email}");
    }
}
 
// ------------------------------------------------------
// Logging Helpers
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