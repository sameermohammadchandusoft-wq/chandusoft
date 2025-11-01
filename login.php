<?php
session_start();

// ✅ Core includes (correct paths)
require_once __DIR__ . '/app/logger.php';   // logger first
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/auth.php';

// ✅ Environment: 'development' shows errors, 'production' hides them
setup_error_handling('development');

// ------------------------
// CSRF token setup
// ------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ------------------------
// Initialize vars
// ------------------------
$errors = ['email' => '', 'password' => '', 'general' => ''];
$email = '';

// ------------------------
// Handle form submit
// ------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("⛔ Invalid CSRF token.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if ($email === '') {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email";
    }

    if ($password === '') {
        $errors['password'] = "Password is required";
    }

    // Only proceed if no validation errors
    if (empty($errors['email']) && empty($errors['password'])) {
        try {
            // Fetch user by email
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $errors['general'] = "Invalid email or password";
                log_info("Failed login attempt for non-existing email: $email");
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    log_info("User logged in: {$user['email']}");
                    login($user['id'], $user['name'], $user['role'] ?? 'user');
                    set_flash('welcome', "Welcome back, {$user['name']}!");
                    redirect('/app/dashboard.php'); // ✅ Redirect after successful login
                } else {
                    $errors['general'] = "Invalid email or password";
                    log_info("Invalid password attempt for {$user['email']}");
                }
            }
        } catch (PDOException $e) {
            log_error("Login query failed: " . $e->getMessage());
            $errors['general'] = "Database error. Please try again later.";
        } catch (Exception $e) {
            log_error("Login error: " . $e->getMessage());
            $errors['general'] = "Unexpected error occurred.";
        }
    }

    // Store errors for next page load (optional)
    if (!empty($errors['email']) || !empty($errors['password']) || !empty($errors['general'])) {
        $_SESSION['form_errors'] = $errors;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Restore errors if redirected
if (isset($_SESSION['form_errors'])) {
    $errors = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="Style.css" />
<style>
.container { width:500px; margin: auto; background:#fff; padding: 10px 40px; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
.error { color:red; font-size:0.9em; margin-top:4px; }
.input-group { margin-bottom:15px; }
input { width:95%; padding:10px; border:1px solid #ccc; border-radius:5px; }
input.error-border { border-color:red; }
button { width:100%; padding:10px; border:none; border-radius:5px; background:#28a745; color:#fff; font-size:16px; cursor:pointer; }
button:hover { background:#1e7e34; }
h2{text-align:center;color:#333;}
p{text-align:center;}
a{color:#1690e8;text-decoration:none;}
</style>
</head>
<body>
<div id="header"></div>
<?php include("header.php"); ?>
<div class="container">
<h2>Login</h2>

<?php if (!empty($errors['general'])): ?>
    <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
<?php endif; ?>

<form method="POST" action="/login">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div class="input-group">
        <label>Email</label><br>
        <input type="text" name="email" value="<?= htmlspecialchars($email) ?>" class="<?= !empty($errors['email']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['email'])): ?><div class="error"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
    </div>

    <div class="input-group">
        <label>Password</label><br>
        <input type="password" name="password" class="<?= !empty($errors['password']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['password'])): ?><div class="error"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
    </div>

    <button type="submit">Login</button>
</form>

<p>Don't have an account? <a href="/register.php">Register here</a></p>
</div>
<div id="footer"></div>
<?php include("footer.php"); ?>
</body>
</html>
