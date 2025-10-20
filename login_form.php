<?php
// login_form.php

// Start session safely if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a CSRF token if it's not already set in the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; background: #f9f9f9; }
    .container { max-width: 400px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .error { color: red; font-size: 0.9em; margin-top: 4px; }
    .input-group { margin-bottom: 15px; }
    input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    input.error-border { border-color: red; }
    button { width: 100%; padding: 10px; background: #1690e8; border: none; color: #fff; border-radius: 5px; cursor: pointer; }
    button:hover { background: #0f6dbf; }
    h2 { text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Admin Login</h2>

    <!-- Display general errors if any -->
    <?php if (!empty($errors['general'])): ?>
      <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <!-- CSRF Token hidden input -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <div class="input-group">
        <label>Email</label><br>
        <input type="text" name="email"
               value="<?= htmlspecialchars($email ?? '') ?>"
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

      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>