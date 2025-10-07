<?php
// app/auth.php
 

/**
 * Login: store user session + regenerate session ID
 */
function login($id, $name, $role) {
    session_regenerate_id(true); // prevent fixation

    $_SESSION['user'] = [
        'id'   => $id,
        'name' => $name,
        'role' => $role
    ];
}

/**
 * Logout: destroy session
 */
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * current_user: return logged-in user info (or null)
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * is_auth: check if user is logged in
 */
function is_auth() {
    return isset($_SESSION['user']);
}

/**
 * require_auth: gatekeeper for protected pages
 */
function require_auth() {
    if (!is_auth()) {
        header("Location: /admin/login.php");
        exit;
    }
}

/**
 * Role-based permission check (with hierarchy)
 */
function can($role) {
    if (!isset($_SESSION['user'])) {
        return false;
    }

    $hierarchy = [
        'guest'  => 0,
        'user'   => 1,
        'editor' => 2,
        'admin'  => 3
    ];

    $userRole = $_SESSION['user']['role'] ?? 'guest';

    return $hierarchy[$userRole] >= $hierarchy[$role];
}
