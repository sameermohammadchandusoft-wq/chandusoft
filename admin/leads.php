<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../admin/header1.php';

$user = current_user();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ----------------------------
// Search
// ----------------------------
$search = $_GET['search'] ?? '';
$search_sql = "";
$params = [];

if (!empty($search)) {
    $search_sql = "WHERE Name LIKE ? OR Email LIKE ? OR Message LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

// ----------------------------
// Pagination
// ----------------------------
$limit = 12;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Count total leads
if (!empty($search_sql)) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads $search_sql");
    $countStmt->execute($params);
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM leads");
}
$totalLeads = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalLeads / $limit));

// Fetch Leads with Pagination
if (!empty($search_sql)) {
    $stmt = $pdo->prepare("SELECT * FROM leads $search_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
} else {
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
}

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Leads - Admin Panel</title>

<style>
/* macOS PRO ADMIN STYLE */
body {
    background:#f2f3f5;
    margin:0;
    font-family:-apple-system, BlinkMacSystemFont, "SF Pro Text", Arial;
}

.mac-container {
    max-width:1200px;
    margin:40px auto;
    background:white;
    padding:35px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,0.07);
}

.mac-title {
    text-align:center;
    font-size:32px;
    font-weight:700;
    color:#1c1c1e;
    margin-bottom:25px;
}

/* Search Bar */
.mac-search {
    text-align:center;
    margin-bottom:20px;
}
.mac-search input {
    padding:10px 14px;
    width:260px;
    border-radius:10px;
    border:1px solid #ccc;
}
.mac-search button {
    padding:10px 16px;
    border-radius:10px;
    border:none;
    background:#007aff;
    color:white;
    font-weight:600;
    cursor:pointer;
}
.mac-search button:hover {
    background:#0062cc;
}

/* Table */
.mac-card {
    margin-top:20px;
    background:white;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}

.mac-table {
    width:100%;
    border-collapse:collapse;
}
.mac-table th {
    background:#f6f7f8;
    padding:14px;
    font-size:14px;
    border-bottom:1px solid #e2e2e2;
}
.mac-table td {
    padding:14px;
    font-size:15px;
    border-bottom:1px solid #f1f1f1;
}

.mac-table tr:hover {
    background:#f9f9fc;
}

.mac-empty {
    text-align:center;
    padding:20px;
    color:#666;
}

/* Pagination */
.pagination {
    text-align:center;
    margin-top:20px;
}
.page-btn {
    padding:8px 14px;
    margin:0 4px;
    background:white;
    border:1px solid #ccc;
    border-radius:10px;
    text-decoration:none;
    color:#333;
    font-size:14px;
}
.page-btn:hover {
    background:#007aff;
    color:white;
}
.page-btn.active {
    background:#007aff;
    color:white;
    border-color:#007aff;
}

</style>
</head>

<body>
<div class="mac-container">

    <h1 class="mac-title">Leads</h1>

    <!-- Search -->
    <div class="mac-search">
        <form method="GET">
            <input type="text" name="search" placeholder="Search leads..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="mac-card">
        <table class="mac-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Submitted</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($leads): ?>
                <?php foreach ($leads as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['Name']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars(substr($row['Message'], 0, 30)) ?><?= strlen($row['Message']) > 30 ? '…' : '' ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="mac-empty">No leads found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="page-btn" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">‹ Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-btn <?= $i == $page ? 'active' : '' ?>"
                   href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-btn" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next ›</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
