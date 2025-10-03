<?php
session_start();
require 'db.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [
    'email' => '',
    'password' => '',
    'general' => ''
];



$email = ''; // keep value for sticky form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⛔ Security token invalid.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ... rest of your validation and login logic
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Email checks
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email";
    }

    // Password check
    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    // If no validation errors → check credentials
    if (empty($errors['email']) && empty($errors['password'])) {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = "Invalid email or password";
        } else {
            // Success → start session
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit;
        }
    }
}

// include the form file
include 'login_form.php';
