<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/auth.php'; // redirect(), set_flash(), get_flash()
require_once __DIR__ . '/app/logger.php';

// -----------------------------------------------------------
// Generate CSRF token
// -----------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$name = $email = $password = '';

// -----------------------------------------------------------
// Handle POST Request
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrf     = $_POST['csrf_token'] ?? '';

    // CSRF Validation
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $errors['general'] = "Security token mismatch. Please refresh the page.";
        log_error("CSRF Error on Registration.");
    }

    // Field validation
    if ($name === '') {
        $errors['name'] = "Name is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Valid email is required.";
    }
    if (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }

    // Check if email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['general'] = "This email is already registered.";
            log_error("Duplicate registration attempt: {$email}");
        }
    }

    // Insert new user
    if (empty($errors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role = 'editor'; // default role

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);

            log_info("User registered - {$name} ({$email})");

            set_flash('success', '🎉 Registration successful! Please log in.');
            redirect('/login.php');
            exit;
        } catch (PDOException $e) {
            log_error("DB Error: " . $e->getMessage());
            $errors['general'] = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Account</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ---------------------------------------------------------
   FUTURISTIC TECH ANIMATED BACKGROUND (same as Login Page)
--------------------------------------------------------- */

body {
    margin: 0;
    padding: 0;
    font-family: "Inter", sans-serif;

    /* Dark blue nebula feel */
    background: radial-gradient(circle at center, #0a0f24, #000);
    background-size: cover;

    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

    overflow: hidden;
    position: relative;
}

/* Floating glowing particles */
body::before {
    content: "";
    position: absolute;
    width: 180%;
    height: 180%;
    background-image: radial-gradient(rgba(0,180,255,0.4) 2px, transparent 2px);
    background-size: 50px 50px;
    animation: particleFloat 18s linear infinite;
}

/* Neon tech grid lines moving downward */
body::after {
    content: "";
    position: absolute;
    width: 200%;
    height: 200%;
    background-image:
        linear-gradient(90deg, rgba(0,150,255,0.08) 1px, transparent 1px),
        linear-gradient(0deg, rgba(0,150,255,0.08) 1px, transparent 1px);
    background-size: 90px 90px;
    animation: gridMove 10s linear infinite;
    opacity: 0.45;
}

/* Background Animations */
@keyframes particleFloat {
    from { transform: translateY(0); }
    to   { transform: translateY(-200px); }
}

@keyframes gridMove {
    from { transform: translateY(0); }
    to   { transform: translateY(120px); }
}


/* ---------------------------------------------------------
   EXISTING REGISTER FORM STYLING (kept same)
--------------------------------------------------------- */

.register-wrapper {
    display: flex;
    justify-content: center;
    width: 100%;
    position: relative;
    z-index: 10; /* Keep form above animation */
}

.register-card {
    width: 380px;
    background: rgba(255, 255, 255, 0.15);
    padding: 40px 35px;
    border-radius: 20px;

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow: 0px 10px 30px rgba(0,0,0,0.3);
    text-align: center;
}

.avatar {
    width: 65px;
    height: 65px;
    margin: auto;
    background: #2d82f7;
    border-radius: 50%;
    margin-bottom: 20px;
}

.register-card h3 {
    margin: 5px 0 8px;
    font-size: 22px;
    font-weight: 600;
    color: #fff;
}

.register-card p {
    color: #d1d5db;
    font-size: 14px;
}

.input-box {
    text-align: left;
    margin-top: 20px;
}

.input-box label {
    font-size: 14px;
    color: #e5e7eb;
}

.input-box input {
    width: 100%;
    padding: 10px;
    border: none;
    border-bottom: 2px solid rgba(255,255,255,0.3);
    outline: none;
    background: transparent;
    font-size: 14px;
    color: #fff;
}

.input-box input::placeholder {
    color: #ccc;
}

.input-box input:focus {
    border-bottom-color: #60a5fa;
}

.error-msg {
    color: #ff6b6b;
    font-size: 13px;
    margin-top: 5px;
}

.success {
    background: rgba(199, 245, 212, 0.3);
    color: #b6ffcd;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.register-btn {
    width: 100%;
    padding: 12px;
    background: #2d82f7;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    margin-top: 20px;
    cursor: pointer;
    transition: 0.25s ease;
}

.register-btn:hover {
    background: #1c6adb;
    box-shadow: 0 6px 12px rgba(45,130,247,0.4);
}

.bottom-text {
    margin-top: 15px;
    font-size: 14px;
    color: #e5e7eb;
}

.bottom-text a {
    color: #60a5fa;
}

</style>
</head>

<body>

<div class="register-wrapper">
    <div class="register-card">

        <div class="avatar"></div>

        <h3>Create account</h3>
        <p>Join us and start using your account</p>

        <!-- Success Message -->
        <?php if ($msg = get_flash('success')): ?>
            <div class="success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- General Error -->
        <?php if (!empty($errors['general'])): ?>
            <p class="error-msg"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="input-box">
                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
                <?php if (!empty($errors['name'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="input-box">
                <label>Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($email) ?>">
                <?php if (!empty($errors['email'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="input-box">
                <label>Password</label>
                <input type="password" name="password">
                <?php if (!empty($errors['password'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button class="register-btn">Create account</button>
        </form>

        <p class="bottom-text">
            Already have an account?
            <a href="/login.php">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>
