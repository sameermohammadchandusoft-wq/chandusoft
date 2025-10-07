<?php
session_start();

// ✅ check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /admin/login.php");
    exit;
}

// Fetch user details
require __DIR__ . '/db.php';
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
    .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h2 { color: #1690e8; }
    .logout { display: inline-block; margin-top: 20px; padding: 10px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
    .logout:hover { background: #b52a36; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Welcome, <?= htmlspecialchars($user['name']) ?> 👋</h2>
    <p>Email: <?= htmlspecialchars($user['email']) ?></p>

    <a href="/admin/logout.php" class="logout">Logout</a>
  </div>
</body>
</html>
