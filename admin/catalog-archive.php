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
// Handle actions: restore / delete
// ---------------------------
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'restore') {
        $stmt = $pdo->prepare("UPDATE catalog_items SET status='published', updated_at=NOW() WHERE id=?");
        $stmt->execute([$id]);
        $success = "Catalog item restored successfully.";
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM catalog_items WHERE id=?");
        $stmt->execute([$id]);
        $success = "Catalog item permanently deleted.";
    }
}

// Fetch archived items
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE status='archived' ORDER BY updated_at DESC");
$stmt->execute();
$archived_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../admin/header1.php';
?>

<div class="dashboard-container">
    <h1>Archived Catalog Items</h1>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (count($archived_items) > 0): ?>
        <table class="catalog-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Price</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archived_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td><?= htmlspecialchars($item['title']) ?></td>
                        <td>₹<?= number_format($item['price'], 2) ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($item['updated_at']))) ?></td>
                        <td class="actions">
                            <a href="?action=restore&id=<?= $item['id'] ?>" class="btn restore"
                               onclick="return confirm('Restore this item?');">Restore</a>
                            <a href="?action=delete&id=<?= $item['id'] ?>" class="btn delete"
                               onclick="return confirm('Permanently delete this item?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No archived items found.</p>
    <?php endif; ?>
</div>

<style>
.dashboard-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 25px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}
.dashboard-container h1 {
    text-align: center;
    color: #244157;
    margin-bottom: 25px;
}
.catalog-table {
    width: 100%;
    border-collapse: collapse;
}
.catalog-table th, .catalog-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
}
.catalog-table th {
    background: #f1f1f1;
    text-align: left;
}
.actions {
    display: flex;
    gap: 6px;
}
.btn {
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    font-size: 13px;
    color: #fff;
}
.btn.restore {
    background: #28a745;
}
.btn.restore:hover {
    background: #218838;
}
.btn.delete {
    background: #dc3545;
}
.btn.delete:hover {
    background: #c82333;
}
.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: bold;
    text-align: center;
}
.message.error {
    background: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
}
.message.success {
    background: #e6ffea;
    color: #138d02;
    border: 1px solid #138d02;
}
</style>
