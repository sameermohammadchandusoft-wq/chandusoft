
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


$name = $email = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⛔ Security token invalid.");
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ... rest of validation/registration logic
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate name
    if (empty($name)) {
        $errors['name'] = "Name is required";
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email";
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters";
    }

    // If no validation errors → insert into DB
    if (empty($errors['name']) && empty($errors['email']) && empty($errors['password'])) {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors['general'] = "Email is already registered";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword]);

            // Auto-login new user
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .container { max-width: 400px; margin: auto; }
    .error { color: red; font-size: 0.9em; margin-top: 4px; }
    .input-group { margin-bottom: 15px; }
    input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    input.error-border { border-color: red; }
    button { padding: 10px 15px; border: none; border-radius: 5px; background: #28a745; color: #fff; cursor: pointer; }
    button:hover { background: #1e7e34; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Create Account</h2>

    <?php if (!empty($errors['general'])): ?>
      <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="input-group">
        <label>Name</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>"
               class="<?= !empty($errors['name']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['name'])): ?>
          <div class="error"><?= htmlspecialchars($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="input-group">
        <label>Email</label><br>
        <input type="text" name="email" value="<?= htmlspecialchars($email ?? '') ?>"
               class="<?= !empty($errors['email']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['email'])): ?>
          <div class="error"><?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="input-group">
        <label>Password</label><br>
        <input type="password" name="password"
               class="<?= !empty($errors['password']) ? 'error-border' : '' ?>">
        <?php if (!empty($errors['password'])): ?>
          <div class="error"><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
  </div>
</body>
</html>
