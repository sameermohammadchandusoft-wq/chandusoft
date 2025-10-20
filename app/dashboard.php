<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../app/auth.php';
require_auth();

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../admin/header1.php'; // ✅ Navbar with Welcome + role
$user = current_user(); // ✅ logged-in user

// -----------------------------
// Fetch dashboard stats
// -----------------------------
$totalLeads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$publishedPages = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
$draftPages = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='draft'")->fetchColumn();

// ✅ Fetch latest 5 leads (safe fallback)
try {
    $stmt = $pdo->query("SELECT name, email, message, created_at, ip_address FROM leads ORDER BY id DESC LIMIT 5");
} catch (PDOException $e) {
    $stmt = $pdo->query("SELECT name, email, message, created_at FROM leads ORDER BY id DESC LIMIT 5");
}
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>

<style>
/* ======== DASHBOARD STYLES ======== */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f4f6f8;
  margin: 0;
  padding: 0;
  color: #333;
}

.dashboard-container {
  max-width: 1000px;
  margin: 60px auto;
  padding: 30px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.dashboard-container h1 {
  font-size: 28px;
  margin-bottom: 25px;
  border-bottom: 3px solid #007bff;
  display: inline-block;
  padding-bottom: 6px;
}

.stats {
  background: #f9fafc;
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 30px;
}

.stats ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  gap: 30px;
}

.stats li {
  font-size: 16px;
  background: #fff;
  padding: 10px 20px;
  border-radius: 8px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  border-left: 4px solid #007bff;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
}

table th, table td {
  padding: 12px 15px;
  text-align: left;
}

table th {
  background: #007bff;
  color: #fff;
  font-weight: 600;
}

table tr:nth-child(even) {
  background: #f2f5f9;
}

table tr:hover {
  background: #e8f0fe;
  transition: background 0.3s;
}

table td {
  font-size: 15px;
}

@media (max-width: 768px) {
  .stats ul {
    flex-direction: column;
    gap: 15px;
  }
  table th, table td {
    font-size: 14px;
  }
}
</style>
</head>
<body>

<div class="dashboard-container">
    <h1>Dashboard</h1>

    <div class="stats">
        <ul>
            <li>Total Leads: <?= $totalLeads ?></li>
            <li>Pages Published: <?= $publishedPages ?></li>
            <li>Pages Draft: <?= $draftPages ?></li>
        </ul>
    </div>

    <h3>Last 5 Leads</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Message</th>
            <th>Created</th>
            <th>IP</th>
        </tr>

        <?php if ($leads): ?>
            <?php foreach ($leads as $lead): ?>
                <tr>
                    <td><?= htmlspecialchars($lead['name']) ?></td>
                    <td><?= htmlspecialchars($lead['email']) ?></td>
                    <td><?= htmlspecialchars(substr($lead['message'], 0, 25)) ?><?= strlen($lead['message']) > 25 ? '...' : '' ?></td>
                    <td><?= htmlspecialchars($lead['created_at']) ?></td>
                    <td><?= htmlspecialchars($lead['ip_address'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No leads found.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
