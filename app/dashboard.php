<?php
require __DIR__ . '/auth.php';
require_auth();

$user = current_user();

// -----------------------
// Database connection
// -----------------------
$host = '127.0.0.1';
$db   = 'chandusoft';    // Replace with your DB name
$dbUser = 'root';         // Replace with your DB username
$dbPass = '';             // Replace with your DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// -----------------------
// Fetch stats
// -----------------------
$total_leads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$pages_published = $pdo->query("SELECT COUNT(*) FROM pages WHERE status = 'published'")->fetchColumn();
$pages_draft = $pdo->query("SELECT COUNT(*) FROM pages WHERE status = 'draft'")->fetchColumn();

// Fetch last 5 leads
$stmt = $pdo->prepare("SELECT name, email, message, created_at AS created, ip FROM leads ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$last_leads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
    <div id="header1"></div>
  <?php include __DIR__ . '/../admin/header1.php';
?>


    <div class="dashboard-container">
    <h1>Dashboard</h1>

    <div class="stats">
        <ul>
            <li>Total Leads: <?= htmlspecialchars($total_leads) ?></li>
            <li>Pages Published: <?= htmlspecialchars($pages_published) ?></li>
            <li>Pages Draft: <?= htmlspecialchars($pages_draft) ?></li>
        </ul>
    </div>

        <div class="table-section">
            <h3>Last 5 Leads</h3>
            <?php if(count($last_leads) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Created</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($last_leads as $lead): ?>
                    <tr>
                        <td><?= htmlspecialchars($lead['name']) ?></td>
                        <td><?= htmlspecialchars($lead['email']) ?></td>
                        <td><?= htmlspecialchars($lead['message']) ?></td>
                        <td><?= htmlspecialchars($lead['created']) ?></td>
                        <td><?= htmlspecialchars($lead['ip'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No leads found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
