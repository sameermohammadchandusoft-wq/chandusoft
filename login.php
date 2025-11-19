<?php
session_start();

require_once __DIR__ . '/app/logger.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/auth.php';

setup_error_handling('development');

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = ['email' => '', 'password' => '', 'general' => ''];
$email = '';

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '') { $errors['email'] = "Email is required"; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = "Enter a valid email"; }

    if ($password === '') { $errors['password'] = "Password is required"; }

    if (!$errors['email'] && !$errors['password']) {
        $stmt = $pdo->prepare("SELECT id,name,email,password,role FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            login($user['id'], $user['name'], $user['role']);
            redirect('/app/dashboard.php');
        } else {
            $errors['general'] = "Invalid email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    font-family: "Inter", sans-serif;
    background: #f5f7fa;
    margin: 0;
}

.login-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 70px;
}

.login-card {
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

/* Headings */
.login-card h3 {
    margin: 5px 0 8px;
    font-size: 22px;
    color: #333;
}
.login-card p {
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

/* Password show icon */
.pass-wrapper {
    position: relative;
}
.pass-wrapper i {
    position: absolute;
    right: 8px;
    top: 32px;
    cursor: pointer;
    color: #777;
}

/* Remember + forgot */
.options {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 13px;
}
.options a {
    color: #2d82f7;
    text-decoration: none;
}

/* Login button */
.login-btn {
    width: 100%;
    padding: 12px;
    background: #2d82f7;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    margin-top: 18px;
    cursor: pointer;
}
.login-btn:hover {
    background: #1c6adb;
}

/* Divider */
.divider {
    margin: 25px 0;
    display: flex;
    align-items: center;
}
.divider span {
    flex: 1;
    height: 1px;
    background: #ddd;
}
.divider p {
    margin: 0 10px;
    font-size: 13px;
    color: #777;
}

/* Social buttons */
.social-btn {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.social-btn:hover {
    background: #f1f1f1;
}

/* Error message */
.error-msg {
    color: red;
    font-size: 13px;
    margin-top: 6px;
}

/* Bottom link */
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

<div class="login-wrapper">
    <div class="login-card">

        <div class="avatar"></div>

        <h3>Sign in</h3>
        <p>to continue to your account</p>

        <?php if (!empty($errors['general'])): ?>
            <p class="error-msg"><?= $errors['general'] ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Email -->
            <div class="input-box">
                <label>Email</label>
                <input type="text" name="email" value="<?= htmlspecialchars($email) ?>">
                <?php if ($errors['email']): ?>
                    <p class="error-msg"><?= $errors['email'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="input-box">
                <label>Password</label>
                <div class="pass-wrapper">
                    <input type="password" id="password" name="password">
                    <i class="fa fa-eye" onclick="togglePass()"></i>
                </div>
                <?php if ($errors['password']): ?>
                    <p class="error-msg"><?= $errors['password'] ?></p>
                <?php endif; ?>
            </div>

            <!-- Options -->
            <div class="options">
                <label><input type="checkbox"> Keep me signed in</label>
                <a href="#">Forgot password?</a>
            </div>

            <button class="login-btn">SIGN IN</button>
        </form>

        <div class="divider">
            <span></span><p>or</p><span></span>
        </div>

        <button class="social-btn">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="18">
            Continue with Google
        </button>

       
        

        <p class="bottom-text">Don't have an account?
            <a href="/register.php">Create account</a>
        </p>

    </div>
</div>

<script>
function togglePass() {
    let pass = document.getElementById("password");
    pass.type = pass.type === "password" ? "text" : "password";
}
</script>

</body>
</html>
