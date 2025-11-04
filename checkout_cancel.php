<?php
require_once __DIR__ . '/app/db.php';

$orderRef = $_GET['order'] ?? '';

if ($orderRef) {
    // ❌ Update order status to failed
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE order_ref = ?");
    $stmt->execute([$orderRef]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Failed</title>
  <style>
    body {font-family: Poppins, sans-serif; text-align:center; padding:80px; background:#fff5f5;}
    .box {background:#fff; display:inline-block; padding:40px 50px; border-radius:14px; box-shadow:0 8px 25px rgba(0,0,0,0.1);}
    h1 {color:#e74c3c;}
  </style>
</head>
<body>
  <div class="box">
    <h1>❌ Payment Failed</h1>
    <p>Your order <strong><?= htmlspecialchars($orderRef) ?></strong> was not completed.</p>
    <a href="checkout.php">Try Again</a>
  </div>
</body>
</html>
