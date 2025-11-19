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

// -------------------------
// Validate and get order
// -------------------------
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

// Decode items JSON safely
$items = [];
if (!empty($order['items_json'])) {
    $decoded = json_decode($order['items_json'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $items = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Details - <?= htmlspecialchars($order['order_ref']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}
h1 {
    text-align: center;
    color: #222;
    margin-bottom: 20px;
}
.section {
    margin-bottom: 30px;
}
.section h2 {
    font-size: 18px;
    color: #444;
    border-bottom: 2px solid #eee;
    padding-bottom: 6px;
    margin-bottom: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    text-align: left;
    padding: 10px;
    border-bottom: 1px solid #eee;
}
th {
    background: #f9fafb;
    text-transform: uppercase;
    font-size: 13px;
    color: #666;
}
.status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 5px;
    color: #fff;
    font-size: 13px;
    text-transform: capitalize;
}
.status-dropdown {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.save-btn {
    padding: 6px 12px;
    margin-left: 8px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.save-btn:hover {
    background: #005ec5;
}

.pending { background: #f39c12; }
.paid { background: #27ae60; }
.failed { background: #e74c3c; }
.gateway {
    font-weight: 600;
    text-transform: uppercase;
}
.back-link {
    display: inline-block;
    text-decoration: none;
    color: #007bff;
    margin-top: 20px;
}
.back-link:hover { text-decoration: underline; }
.product-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ddd;
}
.summary-table td {
    padding: 6px 10px;
}
</style>
</head>
<body>
<div class="container">
    <h1><i class="fa fa-file-invoice"></i> Order Details</h1>

    <!-- ORDER INFO -->
    <div class="section">
        <h2>Order Summary</h2>
        <table class="summary-table">
            <tr><td><strong>Order Ref:</strong></td><td><?= htmlspecialchars($order['order_ref']) ?></td></tr>
            <tr>
    <td><strong>Status:</strong></td>
    <td>

        <?php if (strtolower($order['payment_status']) === 'paid'): ?>

            <!-- Show PAID as fixed text, no dropdown -->
            <span class="status paid">Paid</span>

        <?php else: ?>

            <form id="statusForm">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                <select name="status" id="statusSelect" class="status-dropdown">
                    <?php 
                    // Allowed statuses (paid removed)
                    $statuses = ['paid','pending', 'failed', 'awaiting_upi', 'cod_confirmed'];

                    foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" 
                            <?= strtolower($order['payment_status']) === $s ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="save-btn">Save</button>
                <span id="statusMsg" style="margin-left:10px;font-size:14px;"></span>
            </form>

        <?php endif; ?>

    </td>
</tr>


            <tr><td><strong>Total:</strong></td><td>₹<?= number_format($order['total'], 2) ?></td></tr>
            <tr><td><strong>Payment Gateway:</strong></td><td class="gateway"><?= htmlspecialchars($order['gateway']) ?></td></tr>
            <tr><td><strong>Date:</strong></td><td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td></tr>
        </table>
    </div>

    <!-- CUSTOMER INFO -->
    <div class="section">
        <h2>Customer Details</h2>
        <table class="summary-table">
            <tr><td><strong>Name:</strong></td><td><?= htmlspecialchars($order['customer_name']) ?></td></tr>
            <tr><td><strong>Email:</strong></td><td><?= htmlspecialchars($order['customer_email']) ?></td></tr>
        </table>
    </div>

    <!-- ORDER ITEMS -->
    <div class="section">
        <h2>Items Ordered</h2>
        <?php if (empty($items)): ?>
            <p>No items found in this order.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price (₹)</th>
                    <th>Subtotal (₹)</th>
                </tr>
                <?php foreach ($items as $it): 
                    $title = htmlspecialchars($it['title'] ?? 'Unknown');
                    $qty = (int)($it['qty'] ?? 1);
                    $price = (float)($it['price'] ?? 0);
                ?>
                <tr>
                    <td><?= $title ?></td>
                    <td><?= $qty ?></td>
                    <td><?= number_format($price, 2) ?></td>
                    <td><?= number_format($qty * $price, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <a href="orders.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Orders</a>
    <a href="order-invoice.php?id=<?= $order['id'] ?>" class="back-link" target="_blank">
    <i class="fa fa-print"></i> Print Invoice
</a>

</div>
<script>
document.getElementById("statusForm").addEventListener("submit", function(e){
    e.preventDefault();

    const msg = document.getElementById("statusMsg");
    msg.style.color = "black";
    msg.textContent = "Updating...";

    fetch("update-order-status.php", {
        method: "POST",
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            msg.style.color = "green";
            msg.textContent = "✓ Updated";
        } else {
            msg.style.color = "red";
            msg.textContent = "✗ " + data.error;
        }
    })
    .catch(err => {
        msg.style.color = "red";
        msg.textContent = "✗ Network error";
        console.error(err);
    });
});
</script>

</body>
</html>

