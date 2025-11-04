<?php
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require_auth();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE pages SET status = 'draft', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: pages.php");
exit;
