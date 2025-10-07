<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; }
    .container { max-width: 400px; margin: auto; }
    .error { color: red; font-size: 0.9em; margin-top: 4px; }
    .input-group { margin-bottom: 15px; }
    input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    input.error-border { border-color: red; }
    button { padding: 10px 15px; border: none; border-radius: 5px; background: #1690e8; color: #fff; cursor: pointer; }
    button:hover { background: #0f6dbf; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Login</h2>

    <!-- General error (credentials mismatch) -->
    <?php if (!empty($errors['general'])): ?>
      <p class="error"><?= htmlspecialchars($errors['general']) ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
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
