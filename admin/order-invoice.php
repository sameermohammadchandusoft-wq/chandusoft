<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admins only.');
}

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    exit('Invalid order ID.');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    exit('Order not found.');
}

$items = [];
if (!empty($order['items_json'])) {
    $decoded = json_decode($order['items_json'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $items = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice - <?= htmlspecialchars($order['order_ref']) ?></title>
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #fff;
    color: #000;
    margin: 40px;
}
.invoice-container {
    max-width: 800px;
    margin: 0 auto;
}
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #000;
    padding-bottom: 15px;
    margin-bottom: 30px;
}
.company-info {
    line-height: 1.5;
}
.company-info img {
    height: 60px;
    margin-bottom: 10px;
}
.invoice-title {
    text-align: right;
}
.invoice-title h1 {
    margin: 0;
    font-size: 28px;
    text-transform: uppercase;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    border: 1px solid #ddd;
    padding: 8px;
}
th {
    background: #f5f5f5;
}
.summary {
    text-align: right;
}
.footer {
    margin-top: 30px;
    font-size: 13px;
    color: #555;
    text-align: center;
    border-top: 1px solid #ddd;
    padding-top: 10px;
}
.print-btn {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #007bff;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 5px;
    cursor: pointer;
}
@media print {
    .print-btn {
        display: none;
    }
}
</style>
</head>
<body>
<div class="invoice-container">

    <button class="print-btn" onclick="window.print()">
        🖨 Print
    </button>

    <div class="header">
        <div class="company-info">
            <img src="/chandusoft/assets/images/logo.png" alt="Chandusoft Logo">
            <strong>Chandusoft Pvt. Ltd.</strong><br>
            #123, Tech Park Road, Bengaluru, KA 560001<br>
            GSTIN: 29ABCDE1234F1Z5<br>
            Email: support@chandusoft.com<br>
            Phone: +91 98765 43210
        </div>
        <div class="invoice-title">
            <h1>Invoice</h1>
            <p>
                <strong>Order Ref:</strong> <?= htmlspecialchars($order['order_ref']) ?><br>
                <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?><br>
                <strong>Payment Status:</strong> <?= htmlspecialchars($order['payment_status']) ?><br>
                <strong>Gateway:</strong> <?= htmlspecialchars($order['gateway']) ?>
            </p>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <p><strong>Billed To:</strong><br>
        <?= htmlspecialchars($order['customer_name']) ?><br>
        <?= htmlspecialchars($order['customer_email']) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Product</th>
                <th>Qty</th>
                <th>Price (₹)</th>
                <th>Subtotal (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grandTotal = 0;
            foreach ($items as $it): 
                $title = htmlspecialchars($it['title'] ?? 'Unknown');
                $qty = (int)($it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                $subtotal = $qty * $price;
                $grandTotal += $subtotal;
            ?>
            <tr>
                <td><?= $title ?></td>
                <td><?= $qty ?></td>
                <td><?= number_format($price, 2) ?></td>
                <td><?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table>
        <tr>
            <td class="summary"><strong>Total Amount:</strong> ₹<?= number_format($grandTotal, 2) ?></td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for your purchase with <strong>Chandusoft</strong>.<br>
        This is a computer-generated invoice and does not require a signature.</p>
    </div>

</div>
</body>
</html>
