<?php
session_start();
require_once __DIR__ . '/app/db.php';

// Get order reference from URL
$orderRef = $_GET['order'] ?? null;

if (!$orderRef) {
    die("Invalid Request");
}

// Fetch order record
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? LIMIT 1");
$stmt->execute([$orderRef]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// If order is still pending, update to failed
if ($order['payment_status'] === 'pending') {
    $update = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE order_ref = ?");
    $update->execute([$orderRef]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Payment Cancelled</title>
</head>
<body style="font-family: Arial; text-align:center; padding:50px;">
    <h2>Payment Cancelled</h2>
    <p>Your payment was cancelled. You can try again anytime.</p>
    <a href="catalog.php">Return to Shop</a>
</body>
</html>
