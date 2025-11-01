<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();

// Only admins
if ($user['role'] !== 'admin') {
    die("Access denied.");
}


$success = "";
$error = "";

// ---------------------------
// Handle actions: archive
// ---------------------------
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'archive') {
        $stmt = $pdo->prepare("UPDATE catalog_items SET status='archived', updated_at=NOW() WHERE id=?");
        $stmt->execute([$id]);
        $success = "Catalog item archived successfully.";
    }
}

// ---------------------------
// Filter counts
// ---------------------------
$stmtCounts = $pdo->query("SELECT 
    COUNT(*) AS total, 
    SUM(status='published') AS published, 
    SUM(status='archived') AS archived 
    FROM catalog_items");
$counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

// ---------------------------
// Filter logic
// ---------------------------
$filter = $_GET['filter'] ?? 'all';
switch ($filter) {
    case 'published':
        $stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE status='published' ORDER BY updated_at DESC");
        break;
    case 'archived':
        $stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE status='archived' ORDER BY updated_at DESC");
        break;
    default:
        $stmt = $pdo->prepare("SELECT * FROM catalog_items ORDER BY updated_at DESC");
        break;
}
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../admin/header1.php';
?>

<div class="dashboard-container">
    <h1>Catalog Items</h1>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- ✅ Top Actions -->
    <div class="top-bar">
        <div class="filter-links">
            <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">
                All (<?= $counts['total'] ?>)
            </a>
            <a href="?filter=published" class="<?= $filter === 'published' ? 'active' : '' ?>">
                Published (<?= $counts['published'] ?>)
            </a>
            <a href="?filter=archived" class="<?= $filter === 'archived' ? 'active' : '' ?>">
                Archived (<?= $counts['archived'] ?>)
            </a>
        </div>
        <a href="/admin/catalog-create" class="btn-create">+ Create New Item</a>
    </div>

    <?php if (count($items) > 0): ?>
    <div class="table-wrapper">
        <table class="catalog-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td>
                            <?php
                            $imgPath = !empty($item['image_path']) ? $item['image_path'] : 'https://via.placeholder.com/80x80?text=No+Img';
                            ?>
                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="table-img">
                        </td>
                        <td><?= htmlspecialchars($item['title']) ?></td>
                        <td>₹<?= number_format($item['price'], 2) ?></td>
                        <td>
                            <span class="status <?= htmlspecialchars($item['status']) ?>">
                                <?= htmlspecialchars(ucfirst($item['status'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($item['updated_at']))) ?></td>
                        <td class="actions">
                            <a href="/admin/catalog-edit?id=<?= $item['id'] ?>" class="btn edit">Edit</a>
                            <a href="?action=archive&id=<?= $item['id'] ?>" class="btn archive"
                               onclick="return confirm('Are you sure you want to archive this item?');">Archive</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
        <p class="no-items">No items found for this filter.</p>
    <?php endif; ?>
</div>

<!-- ========== YOUR ORIGINAL CSS ========== -->
<style>
/* ---------- CONTAINER ---------- */
.dashboard-container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 30px 25px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    font-family: "Segoe UI", Arial, sans-serif;
}

/* ---------- HEADING ---------- */
.dashboard-container h1 {
    text-align: center;
    color: #22374c;
    margin-bottom: 30px;
    font-size: 28px;
}

/* ---------- TOP BAR ---------- */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

/* ---------- FILTER LINKS ---------- */
.filter-links a {
    margin-right: 12px;
    text-decoration: none;
    color: #555;
    font-weight: 600;
    font-size: 15px;
    padding: 6px 10px;
    border-radius: 6px;
    transition: 0.2s ease;
}

.filter-links a:hover {
    color: #1690e8;
    background: #eef6ff;
}

.filter-links a.active {
    background: #1690e8;
    color: #fff;
}

/* ---------- CREATE BUTTON ---------- */
.btn-create {
    display: inline-block;
    background: #1690e8;
    color: #fff;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: background 0.25s ease, transform 0.2s ease;
}
.btn-create:hover {
    background: #1175c2;
    transform: translateY(-2px);
}

/* ---------- TABLE ---------- */
.table-wrapper {
    overflow-x: auto;
}

.catalog-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
}

.catalog-table th, .catalog-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e0e0e0;
    text-align: left;
    vertical-align: middle;
}

.catalog-table th {
    background: #f4f8fb;
    color: #244157;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.3px;
}

.catalog-table tr:hover {
    background: #f9fcff;
}

.table-img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #ddd;
}

/* ---------- STATUS LABEL ---------- */
.status {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.status.published {
    background: #e6ffea;
    color: #168821;
    border: 1px solid #b1f0b9;
}

.status.draft {
    background: #fff4e0;
    color: #d17a00;
    border: 1px solid #ffd28a;
}

.status.archived {
    background: #f4f4f4;
    color: #777;
    border: 1px solid #ddd;
}

/* ---------- ACTION BUTTONS ---------- */
.btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: 0.2s ease;
    margin-right: 5px;
}

.btn.edit {
    background: #1690e8;
    color: #fff;
}

.btn.edit:hover {
    background: #1175c2;
}

.btn.archive {
    background: #f4f4f4;
    color: #777;
    border: 1px solid #ddd;
}

.btn.archive:hover {
    background: #e0e0e0;
}

.no-items {
    text-align: center;
    padding: 20px;
    color: #555;
}
</style>
