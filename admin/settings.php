<?php
require __DIR__ . '/../app/db.php';

// Update site name
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['site_name'] ?? '');
    if ($newName !== '') {
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'site_name'");
        $stmt->execute([$newName]);
        $message = "✅ Site name updated successfully!";
    }
}

// Load current name
$currentName = $pdo->query("SELECT value FROM settings WHERE name='site_name'")->fetchColumn() ?? '';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Settings</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="container">
    <h1>Site Settings</h1>
    <?php if (!empty($message)): ?>
      <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST">
      <label for="site_name">Site Name:</label>
      <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($currentName) ?>" required>
      <button type="submit">Save</button>
    </form>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>
