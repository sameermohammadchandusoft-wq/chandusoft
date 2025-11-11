<?php
session_start();
require __DIR__ . '/app/db.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// 🟩 Update Quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    foreach ($_POST['qty'] as $id => $qty) {
        $id = (int)$id;
        $qty = max(1, (int)$qty);
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] = $qty;
        }
    }
    header("Location: cart.php");
    exit;
}

// ❌ Remove / Empty
if (isset($_GET['remove'])) { unset($_SESSION['cart'][(int)$_GET['remove']]); header("Location: cart.php"); exit; }
if (isset($_GET['empty'])) { $_SESSION['cart'] = []; header("Location: cart.php"); exit; }

// 🧮 Prepare Data
$items = [];
$total = 0;
foreach ($_SESSION['cart'] as $it) {
    $subtotal = $it['price'] * $it['qty'];
    $items[] = [...$it, 'subtotal' => $subtotal];
    $total += $subtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart - Chandusoft</title>

<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #ffffff;
  margin: 0;
  padding: 40px;
  color: #111;
}

.cart-wrapper {
  max-width: 1200px;
  margin: auto;
  display: flex;
  gap: 40px;
}

.cart-left {
  flex: 2;
}

.cart-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 25px;
}

.back-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 6px;
  background: #f6f6f6;
  color: #111;
  text-decoration: none;
  transition: 0.25s;
}

.back-arrow:hover {
  background: #e9e9e9;
  transform: translateX(-3px);
}


.cart-item {
  display: flex;
  padding: 18px 0;
  border-bottom: 1px solid #eee;
  align-items: center;
  justify-content: space-between;
}

.product-info {
  display: flex;
  align-items: center;
  gap: 16px;
}

.product-thumb {
  width: 75px;
  height: 75px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #ddd;
}

.product-title {
  font-size: 15px;
  color: #111;
  text-decoration: none;
  font-weight: 500;
}

.qty-controls {
  display: flex;
  align-items: center;
  gap: 6px;
}

.qty-btn {
  width: 26px;
  height: 26px;
  border: 1px solid #ccc;
  background: #f7f7f7;
  cursor: pointer;
  border-radius: 4px;
}

.qty-input {
  width: 48px;
  text-align: center;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.remove-btn {
  font-size: 18px;
  color: #d70000;
  text-decoration: none;
  margin-left: 8px;
}

.cart-right {
  flex: 1;
  border: 1px solid #eee;
  padding: 22px;
  border-radius: 8px;
  height: fit-content;
}

.summary-title {
  font-weight: 600;
  font-size: 18px;
  margin-bottom: 14px;
}

.promo-box {
  display: flex;
  margin-bottom: 18px;
}

.promo-box input {
  flex: 1;
  padding: 11px;
  border: 1px solid #ccc;
  border-radius: 6px 0 0 6px;
}

.promo-box button {
  background: #000;
  color: #fff;
  border: none;
  padding: 0 18px;
  border-radius: 0 6px 6px 0;
  cursor: pointer;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  margin: 8px 0;
  font-size: 14px;
}

.checkout-btn {
  display: block;
  background: #ffdd00;
  border: none;
  width: 100%;
  padding: 14px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 18px;
  font-size: 16px;
}

.empty-msg { text-align:center; font-size:18px; padding:40px; }
</style>

<script>
function adjustQty(id, delta) {
  let input = document.getElementById('qty-' + id);
  input.value = Math.max(1, parseInt(input.value) + delta);
  updateSubtotal(id);
}
function updateSubtotal(id) {
  const price = parseFloat(document.getElementById('price-' + id).dataset.price);
  const qty = parseInt(document.getElementById('qty-' + id).value);
  document.getElementById('subtotal-' + id).innerText = '₹' + (price * qty).toFixed(2);
  updateTotal();
}
function updateTotal() {
  let total = 0;
  document.querySelectorAll('[id^="subtotal-"]').forEach(el => {
    total += parseFloat(el.innerText.replace(/[₹,]/g,'')) || 0;
  });
  document.getElementById('total-amount').innerText = '₹' + total.toFixed(2);
}
</script>
</head>
<body>

<div class="cart-header">
  <a href="catalog.php" class="back-arrow">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
      <path d="M15 6L9 12L15 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </a>
  <h1>My Cart</h1>
</div>



<?php if (empty($items)): ?>
  <p class="empty-msg">Your cart is empty. <a href="catalog.php">Continue shopping</a></p>
<?php else: ?>

<form method="POST">
<input type="hidden" name="action" value="update">

<div class="cart-wrapper">

  <div class="cart-left">
    <?php foreach ($items as $it): ?>
    <div class="cart-item">

   <?php $domain = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST']; ?>
<div class="product-info">
  <img src="<?= htmlspecialchars($it['image'] ?? 'https://via.placeholder.com/75') ?>" class="product-thumb">
  
  <a href="catalog-item.php?slug=<?= urlencode($it['slug'] ?? '') ?>" class="product-title">
    <?= htmlspecialchars($it['title'] ?? '') ?>
  </a>
</div>



      <div class="qty-controls">
        <button type="button" class="qty-btn" onclick="adjustQty(<?= $it['id'] ?>,-1)">−</button>
        <input type="number" id="qty-<?= $it['id'] ?>" name="qty[<?= $it['id'] ?>]" value="<?= $it['qty'] ?>" class="qty-input" onchange="updateSubtotal(<?= $it['id'] ?>)">
        <button type="button" class="qty-btn" onclick="adjustQty(<?= $it['id'] ?>,1)">+</button>
      </div>

      <div id="price-<?= $it['id'] ?>" data-price="<?= $it['price'] ?>">₹<?= number_format($it['price'],2) ?></div>
      <div id="subtotal-<?= $it['id'] ?>">₹<?= number_format($it['subtotal'],2) ?></div>

      <a href="?remove=<?= $it['id'] ?>" class="remove-btn">×</a>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="cart-right">
    <div class="summary-title">Order Summary</div>

    <div class="promo-box">
      <input type="text" placeholder="Promo code">
      <button>Apply</button>
    </div>

    <div class="summary-line"><span>Subtotal</span> <span>₹<?= number_format($total,2) ?></span></div>
    <div class="summary-line"><span>Shipping</span> <span>₹0.00</span></div>

    <hr>

    <div class="summary-line" style="font-weight:600;">
      <span>Total</span> <span id="total-amount">₹<?= number_format($total,2) ?></span>
    </div>

<a href="checkout.php" class="checkout-btn" style="text-decoration:none;display:block;text-align:center;">
  Proceed to Checkout
</a>
  </div>

</div>
</form>

<?php endif; ?>
</body>
</html>
