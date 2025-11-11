<?php
require_once __DIR__ . '/app/db.php';

$orderRef = $_GET['order'] ?? '';
if (!$orderRef) {
    die("<h3 style='text-align:center;margin-top:40px;'>Invalid order reference.</h3>");
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? LIMIT 1");
$stmt->execute([$orderRef]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("<h3 style='text-align:center;margin-top:40px;'>Order not found.</h3>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>UPI Payment - Chandusoft</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #111;
  color: #fff;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
}
.card {
  background: #1c1c1c;
  padding: 40px;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.5);
  text-align: center;
  max-width: 420px;
  width: 90%;
}
h2 {
  color: #00e676;
  margin-bottom: 15px;
}
button {
  margin-top: 25px;
  padding: 12px 20px;
  background: #00e676;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
button:hover {
  background: #00c853;
}
.qr {
  margin: 25px 0;
}
.qr img {
  width: 180px;
  height: 180px;
}
</style>
</head>
<body>
<div class="card">
  <h2>Pay via UPI</h2>
  <p>Scan this QR code or send payment to:</p>
  <div class="qr">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=youremail@upi&pn=Chandusoft&am=<?= htmlspecialchars($order['total']) ?>&cu=INR&tn=Order%20<?= htmlspecialchars($orderRef) ?>" alt="UPI QR">
  </div>
  <p><strong>UPI ID:</strong> youremail@upi</p>
  <p><strong>Amount:</strong> ₹<?= number_format($order['total'], 2) ?></p>
  <button onclick="window.location.href='checkout_success.php?order=<?= urlencode($orderRef) ?>&method=upi'">I've Paid</button>
</div>
</body>
</html>
