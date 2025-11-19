<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/auth.php'; // must contain redirect() & set_flash()

// ----------------------------
// Debug log
// ----------------------------
log_info("Register form accessed");

// ----------------------------
// Generate CSRF token (once)
// ----------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ----------------------------
// Initialize variables
// ----------------------------
$errors = [];
$name = $email = $password = '';

// ----------------------------
// Handle POST request
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrf     = $_POST['csrf_token'] ?? '';

    // ---------- CSRF Validation ----------
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $errors['general'] = "Invalid CSRF token.";
        log_error("CSRF token mismatch during registration.");
    }

    // ---------- Field Validation ----------
    if ($name === '') {
        $errors['name'] = "Name is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Valid email is required.";
    }
    if (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }

    // ---------- Check for existing email ----------
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['general'] = "This email is already registered.";
            log_error("Duplicate registration attempt for {$email}");
        }
    }

    // ---------- Insert new user ----------
    if (empty($errors)) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $role   = 'editor'; // default role

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $role]);

            log_info("User {$name} ({$email}) created successfully");

            set_flash('success', '🎉 Registration successful! Please log in.');
            redirect('/login.php');
            exit;

        } catch (PDOException $e) {
            log_error("DB Error during registration: " . $e->getMessage());
            $errors['general'] = "Error inserting user. Try again later.";
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
body {
    font-family: "Inter", sans-serif;
    background: #f5f7fa;
    margin: 0;
}

.register-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 70px;
}

.register-card {
    width: 380px;
    background: #fff;
    padding: 40px 35px;
    border-radius: 12px;
    box-shadow: 0px 6px 25px rgba(0,0,0,0.08);
    text-align: center;
}

/* Avatar circle */
.avatar {
    width: 65px;
    height: 65px;
    margin: auto;
    background: #2d82f7;
    border-radius: 50%;
    margin-bottom: 20px;
}

/* Titles */
.register-card h3 {
    margin: 5px 0 8px;
    font-size: 22px;
    color: #333;
}
.register-card p {
    color: #777;
    font-size: 14px;
}

/* Inputs */
.input-box {
    text-align: left;
    margin-top: 20px;
}
.input-box label {
    font-size: 14px;
    color: #444;
}
.input-box input {
    width: 100%;
    padding: 10px;
    border: none;
    border-bottom: 1px solid #ddd;
    outline: none;
    background: transparent;
    font-size: 14px;
}
.input-box input:focus {
    border-bottom-color: #2d82f7;
}

/* Error styling */
.error-msg {
    color: red;
    font-size: 13px;
    margin-top: 5px;
}
.success {
    background: #c7f5d4;
    color: #155724;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}

/* Button */
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
}
.register-btn:hover {
    background: #1c6adb;
}

/* Bottom text */
.bottom-text {
    margin-top: 15px;
    font-size: 14px;
}
.bottom-text a {
    color: #2d82f7;
}
</style>
</head>

<body>

<div class="register-wrapper">
    <div class="register-card">

        <div class="avatar"></div>

        <h3>Create account</h3>
        <p>Join us and start using your account</p>

        <!-- Flash message -->
        <?php if ($msg = get_flash('success')): ?>
            <div class="success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- General error -->
        <?php if (!empty($errors['general'])): ?>
            <p class="error-msg"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="POST" action="/register.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Name -->
            <div class="input-box">
                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>">
                <?php if (!empty($errors['name'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="input-box">
                <label>Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($email) ?>">
                <?php if (!empty($errors['email'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Password -->
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
