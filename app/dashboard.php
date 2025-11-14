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
/* ===============================
   MODERN PREMIUM DASHBOARD STYLE
   =============================== */

body {
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    background: linear-gradient(135deg, #eef2f7, #dfe7f1);
    margin: 0;
    padding: 0;
    color: #2c3e50;
}

/* Container */
.dashboard-container {
    max-width: 1100px;
    margin: 60px auto;
    padding: 40px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    animation: fadeIn 0.6s ease-in-out;
}

/* Heading */
.dashboard-container h1 {
    font-size: 32px;
    color: #1a3c6e;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 35px;
}

.dashboard-container h1::before {
    content: "📊";
    font-size: 34px;
}

/* STATS CARD SECTION */
.stats {
    display: flex;
    justify-content: space-between;
    gap: 25px;
    margin-bottom: 35px;
    flex-wrap: wrap;
}

.stat-card {
    flex: 1;
    padding: 22px;
    border-radius: 14px;
    background: linear-gradient(135deg, #ffffff, #f6f9fc);
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    text-align: center;
    transition: 0.3s ease;
    border: 1px solid #e8edf3;
    min-width: 260px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-title {
    font-size: 15px;
    color: #6c7c93;
    margin-bottom: 10px;
    font-weight: 600;
}

.stat-number {
    font-size: 30px;
    font-weight: 800;
    color: #0056d6;
}

/* LEADS TABLE TITLE */
.dashboard-container h3 {
    margin-top: 10px;
    margin-bottom: 15px;
    font-size: 22px;
    color: #12345a;
    font-weight: 700;
    border-left: 5px solid #007bff;
    padding-left: 10px;
}

/* TABLE STYLE */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
}

table tr {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(6px);
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
    transition: 0.3s ease;
}

table tr:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 15px rgba(0,0,0,0.09);
}

table th {
    background: #1f4f8f;
    color: #fff;
    font-weight: 600;
    padding: 15px;
    text-align: left;
    font-size: 14px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

table td {
    padding: 15px;
    font-size: 15px;
    color: #2c3e50;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
}

/* Empty message */
table td[colspan] {
    text-align: center;
    color: #777;
    background: #fafafa;
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
    .stats {
        flex-direction: column;
    }

    table td, table th {
        font-size: 14px;
        padding: 10px;
    }

    .dashboard-container {
        padding: 25px;
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
