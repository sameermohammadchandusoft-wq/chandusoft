<?php
session_start();
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/auth.php';

$errors = ['email' => '', 'password' => '', 'general' => ''];
$email = '';

// ✅ Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⛔ Security token invalid.");
    }

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    // If no validation errors, proceed to authenticate user
    if (empty($errors['email']) && empty($errors['password'])) {
        try {
            // Check if the user exists in the database
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Log database query results
            log_info("Login query executed for email: $email");
            log_info("User fetched: " . print_r($user, true));  // Log user data (password will be hashed, don't worry)

            if (!$user || !password_verify($password, $user['password'])) {
                $errors['general'] = "Invalid email or password";
                log_login_attempt($email, false);  // Log failed login attempt
            } else {
                // Log successful login
                log_login_attempt($email, true);

                // ✅ Log in user
                login($user['id'], $user['name'], $user['role'] ?? 'user');

                // ✅ Set flash message
                set_flash('welcome', "Welcome back, {$user['name']}!");

                // Redirect to dashboard
                redirect('/app/dashboard.php');
            }
        } catch (PDOException $e) {
            // Log database error
            log_error("Login query failed: " . $e->getMessage());
            $errors['general'] = "Something went wrong. Please try again later.";
        } catch (Exception $e) {
            // Log any other errors
            log_error("General Error during login: " . $e->getMessage());
            $errors['general'] = "Unexpected error. Please contact support.";
        }
    }

    // Store errors temporarily and redirect to the login form
    if (!empty($errors['email']) || !empty($errors['password']) || !empty($errors['general'])) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_email'] = $email;  // Keep email value for the form
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// 🔁 On page load (GET), restore and clear errors
if (isset($_SESSION['form_errors'])) {
    $errors = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}
$email = $_SESSION['old_email'] ?? '';
unset($_SESSION['old_email']);
?>
<?php if (isset($_SESSION['logout_message'])): ?>
    <div class="notification" id="logoutMessage">
        <span><?= $_SESSION['logout_message']; ?></span>
        <button class="close-btn" id="closeNotification">&times;</button>
    </div>
<?php
    unset($_SESSION['logout_message']);  // Remove message after displaying
endif;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; background: #f9f9f9; }
    .container { max-width: 400px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .error { color: red; font-size: 0.9em; margin-top: 4px; }
    .input-group { margin-bottom: 15px; }
    input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    input.error-border { border-color: red; }
    button { width: 100%; padding: 10px; border: none; border-radius: 5px; background: #1690e8; color: #fff; font-size: 16px; cursor: pointer; }
    button:hover { background: #0f6dbf; }
    h2 { text-align: center; color: #333; }
    p { text-align: center; }
    a { color: #1690e8; text-decoration: none; }
  </style>
</head>
<body>
  <?php if ($msg = get_flash('welcome')): ?>
    <div style="background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin-bottom:15px; text-align:center; border:1px solid #c3e6cb;">
        <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="container">
    <h2>Login</h2>

    <?php if (!empty($errors['general'])): ?>
      <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="input-group">
        <label>Email</label><br>
        <input type="text" name="email" value="<?= htmlspecialchars($email ?? '') ?>" class="<?= !empty($errors['email']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['email'])): ?>
          <div class="error"><?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="input-group">
        <label>Password</label><br>
        <input type="password" name="password" class="<?= !empty($errors['password']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['password'])): ?>
          <div class="error"><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit">Login</button>
    </form>

    <p>Don’t have an account? <a href="register.php">Register</a></p>
  </div>
</body>
</html>