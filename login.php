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

    // REMEMBER ME UNTIL LOGOUT
if (!empty($_POST['remember'])) {

    $token = bin2hex(random_bytes(32));

    // Save token in DB
    $stmt = $pdo->prepare("UPDATE users SET remember_token=? WHERE id=?");
    $stmt->execute([$token, $user['id']]);

    // Cookie valid for 1 year
    setcookie('remember_token', $token, [
        'expires' => time() + (86400 * 365),  // 1 year
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}


    redirect('/app/dashboard.php');
}

        } else {
            $errors['general'] = "Invalid email or password";
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
/* ------------------------------
   GLOBAL + ANIMATED BACKGROUND
------------------------------ */

/* Full animated BG */
/* ------------------------------
   GLOBAL + ANIMATED BACKGROUND
------------------------------ */

/* Full animated BG */
body {
    margin: 0;
    padding: 0;
    font-family: "Inter", sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

    /* Animated gradient */
    background: linear-gradient(135deg, #0a0f24, #14213d, #1e3a8a);
    background-size: 400% 400%;
    animation: gradientMove 12s ease infinite;
    overflow: hidden;
    position: relative;
}

/* Floating particle dots */
body::before {
    content: "";
    position: absolute;
    width: 200%;
    height: 200%;
    background-image: radial-gradient(rgba(255,255,255,0.15) 2px, transparent 2px);
    background-size: 50px 50px;
    animation: floatDots 30s linear infinite;
}

/* Moving tech-lines */
body::after {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background-image:
        linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px),
        linear-gradient(180deg, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 120px 120px;
    animation: techLines 18s linear infinite;
}

/* Wrap the card */
.login-wrapper {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 380px;
    padding: 40px 15px;
}

/* ------------------------------
   LOGIN CARD
------------------------------ */
.login-card {
    background: rgba(255, 255, 255, 0.15);
    width: 350px;               /* Reduced width */
    padding: 30px 28px;         /* Reduced padding */
    border-radius: 18px;        /* Slightly smaller radius */
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    box-shadow: 0 10px 35px rgba(0,0,0,0.30);
    text-align: center;
    animation: fadeIn 0.4s ease-out;
}

/* Avatar */
.avatar {
    width: 80px;
    height: 80px;
    margin: auto;
    border-radius: 50%;
    overflow: hidden;
    margin-bottom: 22px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Logo inside avatar */
.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* or contain if your logo is transparent */
}


/* Headings */
.login-card h3 {
    margin: 5px 0 8px;
    font-size: 23px;
    font-weight: 600;
    color: #fff;
}
.login-card p {
    color: #cbd5e1;
    font-size: 14px;
}

/* Inputs */
.input-box {
    text-align: left;
    margin-top: 22px;
}
.input-box label {
    font-size: 14px;
    color: #e2e8f0;
}
.input-box input {
    width: 100%;
    padding: 11px 3px;
    border: none;
    border-bottom: 2px solid rgba(255,255,255,0.3);
    background: transparent;
    font-size: 14px;
    color: #fff;
    transition: 0.25s ease;
}
.input-box input:focus {
    border-bottom-color: #60a5fa;
    box-shadow: 0 6px 12px rgba(96,165,250,0.25);
}

/* Password icon */
.pass-wrapper {
    position: relative;
}
.pass-wrapper i {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #ccc;
    transition: 0.2s ease;
}
.pass-wrapper i:hover {
    color: #60a5fa;
}

/* Options */
.options {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 13px;
    color: #cbd5e1;
}
.options a {
    color: #60a5fa;
    text-decoration: none;
}

/* Login button */
.login-btn {
    width: 100%;
    padding: 13px;
    background: #2d82f7;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    margin-top: 22px;
    cursor: pointer;
    transition: 0.25s ease;
}
.login-btn:hover {
    background: #1d64c4;
    box-shadow: 0 6px 18px rgba(45,130,247,0.4);
}

/* Divider */
.divider {
    margin: 28px 0;
    display: flex;
    align-items: center;
}
.divider span {
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.25);
}
.divider p {
    margin: 0 12px;
    font-size: 13px;
    color: #cbd5e1;
}

/* Social buttons */
.social-btn {
    width: 100%;
    padding: 11px;
    border: 1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #fff;
    transition: 0.25s ease;
}
.social-btn:hover {
    background: rgba(255,255,255,0.15);
}

/* Error */
.error-msg {
    color: #ff6b6b;
    font-size: 13px;
}

/* Bottom */
.bottom-text {
    margin-top: 20px;
    font-size: 14px;
    color: #e2e8f0;
}
.bottom-text a {
    color: #60a5fa;
}

/* ------------------------------
   ANIMATIONS
------------------------------ */
@keyframes gradientMove {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

@keyframes floatDots {
    0% { transform: translateY(0); }
    100% { transform: translateY(-200px); }
}

@keyframes techLines {
    0% { transform: translateY(0); }
    100% { transform: translateY(200px); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Mobile */
@media(max-width: 430px) {
    .login-card {
        padding: 35px 25px;
    }
}


</style>
</head>

<body>

<div class="login-wrapper">
    <div class="login-card">

        <div class="avatar">
            <img src="images/Untitled.jpg" alt="Logo">
        </div>

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
                <label><input type="checkbox" name="remember" value="1"> Keep me signed in</label>

                <a href="/app/forgot_password.php">Forgot password?</a>

            </div>

            <button class="login-btn">SIGN IN</button>
        </form>

        <div class="divider">
            <span></span><p>or</p><span></span>
        </div>

        <!-- Google -->
        <button class="social-btn" onclick="window.location='/app/google.php'">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="18">
            Continue with Google
        </button>

        <!-- LinkedIn -->
        <button class="social-btn" onclick="window.location='/app/linkedin.php'">
            <img src="https://upload.wikimedia.org/wikipedia/commons/c/ca/LinkedIn_logo_initials.png" width="18">
            Continue with LinkedIn
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
