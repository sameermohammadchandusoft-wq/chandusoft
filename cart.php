<?php
session_start();
require __DIR__ . '/app/db.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $id  = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    // Fetch product from DB
    $stmt = $pdo->prepare("SELECT id, title, price, image_path FROM catalog_items WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Add or update cart item
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => $product['image_path'],
                'qty' => $qty
            ];
        }
    }

    // Redirect to avoid resubmission
    header("Location: cart.php");
    exit;
}

// Remove item
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$removeId]);
    header("Location: cart.php");
    exit;
}

// Prepare cart display
$items = [];
$total = 0.0;
foreach ($_SESSION['cart'] as $it) {
    if (!is_array($it)) continue;
    $subtotal = $it['price'] * $it['qty'];
    $items[] = [
        'id' => $it['id'],
        'title' => $it['title'],
        'price' => $it['price'],
        'qty' => $it['qty'],
        'subtotal' => $subtotal
    ];
    $total += $subtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 40px; }
    .container { max-width: 800px; margin: auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    h1 { text-align: center; margin-bottom: 25px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
    th { background: #f0f0f0; }
    a.remove { color: red; text-decoration: none; font-weight: bold; }
    a.remove:hover { color: darkred; }
    .total { text-align: right; margin-top: 15px; font-size: 18px; font-weight: bold; }
    .checkout-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; }
    .checkout-btn:hover { background: #1e8449; }
    .empty { text-align: center; font-size: 18px; color: #777; }
  </style>
</head>
<body>
<div class="container">
  <h1>Your Cart</h1>
  <?php if (empty($items)): ?>
    <p class="empty">🛒 Your cart is empty. <a href="catalog.php">Continue shopping</a></p>
  <?php else: ?>
    <table>
      <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['title']) ?></td>
        <td><?= $it['qty'] ?></td>
        <td>₹<?= number_format($it['price'], 2) ?></td>
        <td>₹<?= number_format($it['subtotal'], 2) ?></td>
        <td><a href="?remove=<?= $it['id'] ?>" class="remove">×</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p class="total">Total: ₹<?= number_format($total, 2) ?></p>
    <a href="checkout.php" class="checkout-btn">Proceed to Checkout →</a>
  <?php endif; ?>
</div>
</body>
</html>