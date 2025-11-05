<?php
session_start();
require __DIR__ . '/app/db.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// 🟩 Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $id  = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    $stmt = $pdo->prepare("SELECT id, title, price, image_path, slug FROM catalog_items WHERE id = ?");
    $stmt->execute([$id]);
    if ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => $product['image_path'],
                'slug'  => $product['slug'],
                'qty'   => $qty
            ];
        }
    }
    header("Location: cart.php");
    exit;
}

// 🔁 Update Quantities
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

// 🧮 Prepare display data
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
<title>Your Cart - Chandusoft</title>
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f5f6fa;
    margin: 0;
    padding: 40px;
}
.container {
    max-width: 900px;
    margin: auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    margin-bottom: 25px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
    vertical-align: middle;
}
th {
    background: #1690e8;
    color: #fff;
}
tr:nth-child(even) {
    background: #f9f9f9;
}
.product-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    text-align: left;
}
.product-thumb {
    width: 45px;
    height: 45px;
    border-radius: 5px;
    object-fit: cover;
    border: 1px solid #ddd;
    transition: transform 0.2s ease;
}
.product-thumb:hover {
    transform: scale(1.05);
}
.product-link {
    color: #333;
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}
.product-link:hover {
    color: #1690e8;
    text-decoration: underline;
}
.qty-box {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 4px;
}
.qty-btn {
    background: #ddd;
    border: none;
    width: 28px;
    height: 28px;
    font-weight: bold;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.2s;
}
.qty-btn:hover {
    background: #ccc;
}
input.qty-input {
    width: 50px;
    text-align: center;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
a.remove {
    color: #e74c3c;
    text-decoration: none;
    font-weight: bold;
}
a.remove:hover {
    color: darkred;
}
.total {
    text-align: right;
    margin-top: 15px;
    font-size: 18px;
    font-weight: bold;
}
.btn {
    display: inline-block;
    padding: 10px 18px;
    border-radius: 6px;
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    border: none;
    cursor: pointer;
}
.checkout-btn {
    background: #27ae60;
}
.checkout-btn:hover {
    background: #1e8449;
}
.back-btn {
    background: #6c63ff;
}
.back-btn:hover {
    background: #534edc;
}
.empty-btn {
    background: #e74c3c;
}
.empty-btn:hover {
    background: #c0392b;
}
.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 25px;
    gap: 10px;
}
.right-actions {
    display: flex;
    gap: 10px;
}
.empty {
    text-align: center;
    font-size: 18px;
    color: #777;
}
</style>
<script>
function adjustQty(id, delta) {
  const input = document.getElementById('qty-' + id);
  let value = parseInt(input.value) + delta;
  if (value < 1) value = 1;
  input.value = value;
  updateSubtotal(id);
}
function updateSubtotal(id) {
  const price = parseFloat(document.getElementById('price-' + id).dataset.price);
  const qty = parseInt(document.getElementById('qty-' + id).value);
  const subtotal = price * qty;
  document.getElementById('subtotal-' + id).innerText = '₹' + subtotal.toFixed(2);
  updateTotal();
}
function updateTotal() {
  let total = 0;
  document.querySelectorAll('[id^="subtotal-"]').forEach(el => {
    total += parseFloat(el.innerText.replace(/[₹,]/g, '')) || 0;
  });
  document.getElementById('total-amount').innerText = '₹' + total.toFixed(2);
}
</script>
</head>
<body>

<div class="container">
  <h1>Your Cart</h1>

  <?php if (empty($items)): ?>
    <p class="empty">🛒 Your cart is empty. <a href="catalog.php">Continue shopping</a></p>
  <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <table>
        <tr>
          <th>Item</th>
          <th>Quantity</th>
          <th>Price</th>
          <th>Subtotal</th>
          <th></th>
        </tr>
        <?php foreach ($items as $it): ?>
          <?php
            // ✅ Ensure correct image path
            $imagePath = $it['image'];
            $imagePath = $it['image'];

// If image_path is just a filename, prepend uploads folder
            if (!empty($imagePath)) {
                if (!preg_match('~^https?://~', $imagePath)) {
                    // Auto-detect common storage folders
                    if (!str_starts_with($imagePath, '/uploads/') && !str_starts_with($imagePath, 'uploads/')) {
                        $imagePath = '/uploads/' . ltrim($imagePath, '/');
                    } else {
                        $imagePath = '/' . ltrim($imagePath, '/');
                    }
                }
            } else {
                $imagePath = '/assets/no-image.png';
            }

          ?>
          <tr>
            <td>
              <div class="product-cell">
                <a href="product.php?slug=<?= urlencode($it['slug']) ?>">
                  <img src="<?= htmlspecialchars($imagePath ?: '/assets/no-image.png') ?>" alt="Product" class="product-thumb">
                </a>
                <a href="product.php?slug=<?= urlencode($it['slug']) ?>" class="product-link">
                  <?= htmlspecialchars($it['title']) ?>
                </a>
              </div>
            </td>
            <td>
              <div class="qty-box">
                <button type="button" class="qty-btn" onclick="adjustQty(<?= $it['id'] ?>, -1)">−</button>
                <input type="number" id="qty-<?= $it['id'] ?>" name="qty[<?= $it['id'] ?>]" value="<?= $it['qty'] ?>" min="1" class="qty-input" onchange="updateSubtotal(<?= $it['id'] ?>)">
                <button type="button" class="qty-btn" onclick="adjustQty(<?= $it['id'] ?>, 1)">+</button>
              </div>
            </td>
            <td id="price-<?= $it['id'] ?>" data-price="<?= $it['price'] ?>">₹<?= number_format($it['price'], 2) ?></td>
            <td id="subtotal-<?= $it['id'] ?>">₹<?= number_format($it['subtotal'], 2) ?></td>
            <td><a href="?remove=<?= $it['id'] ?>" class="remove">×</a></td>
          </tr>
        <?php endforeach; ?>
      </table>

      <p class="total">Total: <span id="total-amount">₹<?= number_format($total, 2) ?></span></p>

      <div class="actions">
        <a href="catalog.php" class="btn back-btn">⬅ Back to Catalog</a>
        <div class="right-actions">
          <a href="?empty=1" class="btn empty-btn" onclick="return confirm('Are you sure you want to empty your cart?')">🗑 Empty Cart</a>
          <a href="checkout.php" class="btn checkout-btn">💳 Proceed to Checkout →</a>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
