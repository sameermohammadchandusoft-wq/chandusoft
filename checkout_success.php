<?php
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/vendor/autoload.php';

$orderRef = $_GET['order'] ?? '';
$method   = $_GET['method'] ?? '';
$payer    = $_GET['payer'] ?? '';

if (!$orderRef) {
    die("<h2 style='text-align:center;margin-top:40px;'>Invalid order reference.</h2>");
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? LIMIT 1");
$stmt->execute([$orderRef]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h2 style='text-align:center;margin-top:40px;'>Order not found.</h2>");
}

// If payment status is still pending, display "Payment Pending"
$status = ucfirst($order['payment_status']); // pending / successful / failed
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Status — <?= htmlspecialchars($orderRef) ?></title>
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: #f7f9fc;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      padding: 40px 50px;
      text-align: center;
      max-width: 520px;
    }
    h1 {
      font-size: 26px;
      color: #333;
      margin-bottom: 10px;
    }
    p {
      color: #555;
      margin-bottom: 20px;
      font-size: 16px;
    }
    .status {
      display: inline-block;
      padding: 8px 18px;
      border-radius: 20px;
      font-weight: 500;
      color: #fff;
      background: <?= ($order['payment_status'] === 'paid') ? '#28a745' : (($order['payment_status'] === 'failed') ? '#dc3545' : '#ffc107') ?>;
    }
    a.button {
      display: inline-block;
      padding: 10px 18px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 500;
      transition: background 0.3s;
    }
    a.button:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Order Status</h1>
    <p><strong>Order Reference:</strong> <?= htmlspecialchars($orderRef) ?></p>
    <p><strong>Payment Method:</strong> <?= htmlspecialchars(ucfirst($method)) ?></p>
    <p><strong>Status:</strong> <span class="status"><?= htmlspecialchars($status) ?></span></p>
    <?php if ($payer): ?>
      <p><strong>Payer:</strong> <?= htmlspecialchars($payer) ?></p>
    <?php endif; ?>

    <?php if ($order['payment_status'] === 'pending'): ?>
      <p>Your payment is being processed. Please check back later.</p>
    <?php elseif ($order['payment_status'] === 'paid'): ?>
      <p>Thank you! Your payment was successful.</p>
    <?php else: ?>
      <p>Unfortunately, your payment failed or was canceled.</p>
    <?php endif; ?>

    <br>
    <a href="catalog.php" class="button">← Back to Catalog</a>
  </div>
</body>
</html>
