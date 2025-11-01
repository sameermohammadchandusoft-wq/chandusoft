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

// ---------------------------------------------
// Search by order_ref or customer_email
// ---------------------------------------------
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT id, order_ref, customer_name, customer_email, total, payment_status, gateway, created_at
    FROM orders
";
$params = [];

if ($search !== '') {
    $sql .= " WHERE order_ref LIKE ? OR customer_email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Orders - Chandusoft</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    border-radius: 8px;
    padding: 25px 35px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    margin-bottom: 25px;
    color: #222;
}
form {
    text-align: center;
    margin-bottom: 25px;
}
input[type="text"] {
    width: 320px;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
}
button {
    padding: 8px 14px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 5px;
}
button:hover {
    background: #0056b3;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    text-align: center;
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
}
th {
    background: #f9fafb;
    text-transform: uppercase;
    font-size: 13px;
    color: #666;
}
tr:hover {
    background: #f2f6ff;
}
.status {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    color: white;
    text-transform: capitalize;
}
.pending { background: #f39c12; }
.paid { background: #27ae60; }
.failed { background: #e74c3c; }
.gateway {
    text-transform: uppercase;
    font-weight: 600;
    color: #555;
}
.no-orders {
    text-align: center;
    color: #777;
    margin-top: 25px;
    font-size: 16px;
}
.back-link {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    color: #333;
}
.back-link:hover {
    text-decoration: underline;
}
.view-btn {
    background: #3498db;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
}
.view-btn:hover {
    background: #2c80b4;
}
</style>
</head>
<body>
<div class="container">
    <h1><i class="fa fa-box"></i> Admin Orders</h1>

    <form method="get">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Email or Order Reference">
        <button type="submit"><i class="fa fa-search"></i> Search</button>
    </form>

    <?php if (empty($orders)): ?>
        <p class="no-orders">No orders found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Order Ref</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Total (₹)</th>
                <th>Payment</th>
                <th>Gateway</th>
                <th>Date</th>
                <th>View</th>
            </tr>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['id']) ?></td>
                <td><?= htmlspecialchars($o['order_ref']) ?></td>
                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                <td><?= htmlspecialchars($o['customer_email']) ?></td>
                <td><?= number_format($o['total'], 2) ?></td>
                <td><span class="status <?= strtolower($o['payment_status']) ?>"><?= htmlspecialchars($o['payment_status']) ?></span></td>
                <td class="gateway"><?= htmlspecialchars($o['gateway']) ?></td>
                <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                <td><a href="order-view.php?id=<?= $o['id'] ?>" class="view-btn">View</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div style="text-align:center;">
        <a href="/app/dashboard.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>
</body>
</html>
