<?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load DB (safe if used for dynamic site name later)
require_once __DIR__ . '/../app/db.php';

// Load current user if available
require_once __DIR__ . '/../app/auth.php';
$user = current_user();

// Optional: fetch site name from settings table
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'site_name' LIMIT 1");
    $siteName = $stmt->fetchColumn() ?: 'Chandusoft Admin';
} catch (Exception $e) {
    $siteName = 'Chandusoft Admin';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f1f3f6;
}

/* Navbar styling */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #244157ff;
    color: white;
    padding: 15px 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.navbar-left .brand {
    font-weight: bold;
    font-size: 22px;
}

.navbar-right a {
    color: white;
    text-decoration: none;
    margin-left: 25px;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
    transition: background 0.3s, opacity 0.3s;
}

.navbar-right a:hover {
    background: rgba(255,255,255,0.2);
    opacity: 0.9;
}
</style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <span class="brand"><?= htmlspecialchars($siteName) ?></span>
        </div>

        <div class="navbar-right">
            <?php if (!empty($user)): ?>
                <span style="margin-right:15px; font-weight:bold;">
                    Welcome <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['role']) ?>)
                </span>
            <?php endif; ?>

            <a href="/app/dashboard.php">Dashboard</a>
            <a href="/admin/leads.php">Leads</a>
            <a href="/admin/pages.php">Pages</a>
             <a href="/admin/catalog-list.php">Catalog</a>
            <a href="/logout.php">Logout</a>
           
        </div>
    </div>
</body>
</html>
