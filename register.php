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
<title>Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="Style.css" />
<style>
.container { max-width: 500px; margin: auto; background: #fff; padding: 10px 40px; border-radius: 10px; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
h2 { text-align: center; color: #333; margin-bottom: 20px; }
.error { color: red; font-size: 0.9em; margin-top: 4px; }
input { width: 95%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 10px; }
input.error-border { border-color: red; }
button { width: 100%; padding: 10px; border: none; border-radius: 5px; background: #28a745; color: #fff; font-size: 16px; cursor: pointer; }
button:hover { background: #218838; }
p { text-align: center; margin-top: 10px; }
a { color: #1690e8; text-decoration: none; }
.success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
</style>
</head>
<body>
    <div id="header"></div>
  <?php include("header.php"); ?>
<div class="container">
<h2>Create Account</h2>

<!-- Flash success -->
<?php if ($msg = get_flash('success')): ?>
    <div class="success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- General errors -->
<?php if (!empty($errors['general'])): ?>
    <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
<?php endif; ?>

<form method="POST" action="/register">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" class="<?= !empty($errors['name']) ? 'error-border' : '' ?>">
    <?php if (!empty($errors['name'])): ?><div class="error"><?= htmlspecialchars($errors['name']) ?></div><?php endif; ?>

    <label>Email</label>
    <input type="text" name="email" value="<?= htmlspecialchars($email) ?>" class="<?= !empty($errors['email']) ? 'error-border' : '' ?>">
    <?php if (!empty($errors['email'])): ?><div class="error"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>

    <label>Password</label>
    <input type="password" name="password" class="<?= !empty($errors['password']) ? 'error-border' : '' ?>">
    <?php if (!empty($errors['password'])): ?><div class="error"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="/login.php">Login here</a></p>
</div>
<div id="footer"></div>
  <?php include("footer.php"); ?>
</body>
</html>