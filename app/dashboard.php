<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../app/auth.php';
require_auth();

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../admin/header1.php';
$user = current_user();

// ============================
// FETCH STATS
// ============================
$totalLeads       = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$publishedPages   = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
$draftPages       = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='draft'")->fetchColumn();
$catalogCount     = $pdo->query("SELECT COUNT(*) FROM catalog_items")->fetchColumn();

// Latest 5 Leads
try {
    $stmt = $pdo->query("SELECT name, email, message, created_at, ip_address FROM leads ORDER BY id DESC LIMIT 5");
} catch (PDOException $e) {
    $stmt = $pdo->query("SELECT name, email, message, created_at FROM leads ORDER BY id DESC LIMIT 5");
}
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* ============================
    macOS PRO ADMIN UI
============================ */
body {
    background: #f2f3f5;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", Arial;
}

/* MAIN CONTAINER */
.mac-container {
    max-width: 1200px;
    margin: 50px auto;
    background: #ffffffdd;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    backdrop-filter: blur(10px);
}

/* Title */
.mac-title {
    font-size: 32px;
    font-weight: 700;
    color: #1c1c1e;
    text-align: center;
    margin-bottom: 30px;
}

/* ============================
   STAT CARDS
============================ */
.stats {
    display: flex;
    justify-content: space-between;
    gap: 22px;
    flex-wrap: wrap;
}

.stat-card {
    flex: 1;
    min-width: 240px;
    padding: 25px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    transition: 0.25s;
    border: 1px solid #e6e6e6;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.stat-title {
    color: #6a6a6c;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 32px;
    font-weight: 800;
    color: #007aff;
}

/* ============================
   LAST 5 LEADS TABLE
============================ */
.mac-subtitle {
    font-size: 22px;
    font-weight: 700;
    color: #1d2a44;
    margin-top: 35px;
    border-left: 5px solid #007aff;
    padding-left: 12px;
}

.mac-card-table {
    margin-top: 15px;
    background: #ffffffdd;
    padding: 0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.mac-table {
    width: 100%;
    border-collapse: collapse;
}

.mac-table th {
    background: #f6f7f8;
    padding: 14px;
    color: #555;
    text-align: left;
    font-size: 14px;
    border-bottom: 1px solid #e1e1e1;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.mac-table td {
    padding: 14px;
    font-size: 15px;
    border-bottom: 1px solid #f1f1f1;
}

.mac-table tr:hover {
    background: #f9f9fc;
}

.mac-empty {
    padding: 20px;
    text-align: center;
    color: #666;
}

@media(max-width: 768px) {
    .stats { flex-direction: column; }
}
</style>
</head>
<body>

<div class="mac-container">

    <h1 class="mac-title">Dashboard</h1>

    <!-- STAT CARDS -->
    <div class="stats">

        <div class="stat-card">
            <div class="stat-title">Total Leads</div>
            <div class="stat-number"><?= $totalLeads ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Published Pages</div>
            <div class="stat-number"><?= $publishedPages ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Draft Pages</div>
            <div class="stat-number"><?= $draftPages ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Catalog Items</div>
            <div class="stat-number"><?= $catalogCount ?></div>
        </div>

    </div>

    <!-- LEADS TABLE -->
    <h2 class="mac-subtitle">Last 5 Leads</h2>

    <div class="mac-card-table">
        <table class="mac-table">
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
            <?php if ($leads): ?>
                <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?= htmlspecialchars($lead['name']) ?></td>
                        <td><?= htmlspecialchars($lead['email']) ?></td>
                        <td><?= htmlspecialchars(substr($lead['message'], 0, 25)) ?><?= strlen($lead['message']) > 25 ? '…' : '' ?></td>
                        <td><?= htmlspecialchars($lead['created_at']) ?></td>
                        <td><?= htmlspecialchars($lead['ip_address'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="mac-empty">No leads found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
