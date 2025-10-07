<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();

// Only admins can archive
if ($user['role'] !== 'admin') {
    die("Access denied.");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid page ID.");
}

$stmt = $pdo->prepare("UPDATE pages SET status='archived', updated_at=NOW() WHERE id=?");
$stmt->execute([$id]);

header("Location: pages.php");
exit;
