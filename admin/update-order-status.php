<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

header('Content-Type: application/json');

// DEBUG
file_put_contents(__DIR__ . '/debug.txt', "STATUS=" . ($_POST['status'] ?? 'NULL'));

$user = current_user();
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$status   = strtolower(trim($_POST['status'] ?? ''));

$allowed = ['pending', 'failed', 'awaiting_upi', 'cod_confirmed'];

if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

$stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
$ok = $stmt->execute([$status, $order_id]);

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB update failed']);
}
