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

// Fetch all non-archived items (status != archived)
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE status!='archived' ORDER BY updated_at DESC");
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

    <a href="catalog-create.php" style="display:inline-block; margin-bottom: 15px; background:#1690e8; color:#fff; padding:10px 15px; border-radius:6px; text-decoration:none;">
        + Create New Item
    </a>

    <?php if (count($items) > 0): ?>
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
                        // Show uploaded image if exists, else placeholder
                        $imgPath = !empty($item['image_path']) ? $item['image_path'] : '/path/to/default-placeholder.png';
                        ?>
                        <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="max-width:80px; max-height:80px; border-radius:4px;">
                    </td>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td><?= htmlspecialchars($item['status']) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($item['updated_at']))) ?></td>
                    <td class="actions">
                        <a href="catalog-edit.php?id=<?= $item['id'] ?>" class="btn edit">Edit</a>
                        <a href="?action=archive&id=<?= $item['id'] ?>" class="btn archive" 
                           onclick="return confirm('Are you sure you want to archive this item?');">
                           Archive
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php else: ?>
        <p>No active items found. All items might be archived.</p>
    <?php endif; ?>
</div>

<style>
/* same styles as before */
.dashboard-container { max-width:1000px; margin:40px auto; padding:25px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); font-family:Arial,sans-serif; }
.dashboard-container h1 { text-align:center; color:#244157; margin-bottom:25px; }
.catalog-table { width:100%; border-collapse:collapse; }
.catalog-table th, .catalog-table td { padding:12px 10px; border-bottom:1px solid #ddd; vertical-align:middle; }
.catalog-table th { background:#f1f1f1; text-align:left; }
.actions { display:flex; gap:6px; }
.btn { padding:6px 12px; border-radius:4px; text-decoration:none; font-weight:bold; font-size:13px; color:#fff; }
.btn.edit { background:#1690e8; } .btn.edit:hover { background:#0f6dbf; }
.btn.archive { background:#ffc107; color:#000; } .btn.archive:hover { background:#e0a800; }
.message { padding:12px; border-radius:6px; margin-bottom:15px; font-weight:bold; text-align:center; }
.message.error { background:#ffe6e6; color:#d8000c; border:1px solid #d8000c; }
.message.success { background:#e6ffea; color:#138d02; border:1px solid #138d02; }
</style>
