<?php
require __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/logger.php';
require_once __DIR__ . '/app/env.php'; // ✅ Load environment vars (TURNSTILE keys)
setup_error_handling('development');
log_info('Catalog item viewed');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get product slug
$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 🛒 Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $id  = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($id > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$id] = [
                'id'    => $product['id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'qty'   => $qty
            ];
        }

        header("Location: cart.php");
        exit;
    }
}

// Product data
$name = htmlspecialchars($product['title']);
$desc = htmlspecialchars($product['short_desc']);
$price = number_format($product['price'], 2);
$imagePath = htmlspecialchars($product['image_path']);

// ✅ Turnstile keys
$turnstileSiteKey = $_ENV['TURNSTILE_SITEKEY'] ?? getenv('TURNSTILE_SITEKEY') ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $name ?> | Chandusoft Catalog</title>
  <meta name="description" content="<?= substr(strip_tags($desc), 0, 150) ?>" />
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: "Poppins", Arial, sans-serif; background-color: #f7f9fc; color: #333; line-height: 1.6; padding: 20px; }
    .container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 30px; }
    .back-btn { display: inline-block; margin-bottom: 25px; background: #1e90ff; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.3s ease, transform 0.2s ease; }
    .back-btn:hover { background: #1171c1; transform: translateY(-2px); }

    .product-detail { display: flex; flex-wrap: wrap; gap: 40px; margin-bottom: 50px; }
    .product-image { flex: 1 1 380px; max-width: 480px; border-radius: 12px; object-fit: cover; width: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .product-info { flex: 1 1 300px; }
    .product-info h1 { font-size: 2rem; color: #111; margin-bottom: 10px; }
    .price { font-size: 1.4rem; font-weight: 700; color: #1e90ff; margin-bottom: 20px; }
    .product-info p { font-size: 1rem; color: #444; margin-bottom: 20px; }

    .cart-form { display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 12px 16px; border: 1px solid #eee; border-radius: 10px; }
    .cart-form input[type="number"] { width: 80px; padding: 8px; border-radius: 6px; border: 1px solid #ccc; text-align: center; }
    .cart-form button { flex: 1; background: #28a745; color: #fff; border: none; padding: 10px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.3s ease, transform 0.2s ease; }
    .cart-form button:hover { background: #218838; transform: translateY(-2px); }

    .enquiry-form { padding: 25px; border-top: 2px solid #eee; }
    .enquiry-form h2 { font-size: 1.5rem; margin-bottom: 20px; position: relative; }
    .enquiry-form h2::after { content: ""; display: block; width: 60px; height: 3px; background: #1e90ff; margin-top: 8px; border-radius: 3px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #222; }
    .form-group input, .form-group textarea { width: 100%; padding: 12px 14px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; }
    .form-group input:focus, .form-group textarea:focus { border-color: #1e90ff; outline: none; box-shadow: 0 0 0 3px rgba(30,144,255,0.15); }
    button[type="submit"] { background-color: #1e90ff; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; }
    button[type="submit"]:hover { background-color: #1171c1; transform: translateY(-2px); }

    @media (max-width: 768px) {
      .product-detail { flex-direction: column; text-align: center; }
      .cart-form { flex-direction: column; align-items: stretch; }
    }
  </style>
</head>

<body>
  <div class="container">
    <a href="catalog" class="back-btn">← Back to Catalog</a>

    <div class="product-detail">
      <img src="<?= !empty($product['image_path']) ? $imagePath : 'https://via.placeholder.com/400x400?text=No+Image' ?>"
           alt="<?= $name ?>" class="product-image" loading="lazy" />

      <div class="product-info">
        <h1><?= $name ?></h1>
        <p class="price">₹<?= $price ?></p>
        <p><?= nl2br($desc) ?></p>

        <!-- ✅ Add to Cart -->
        <form method="POST" action="" class="cart-form">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
          <label for="qty">Qty:</label>
          <input type="number" name="qty" id="qty" value="1" min="1" required>
          <button type="submit">🛒 Add to Cart</button>
        </form>
      </div>
    </div>

    <!-- ✅ Enquiry Form -->
    <div class="enquiry-form">
      <h2>Enquire about this product</h2>

     <form id="enquiryForm" action="app/submit-enquiry.php" method="POST" style="max-width:400px;margin:auto;">
    
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">

    <!-- FIX -->
    <input type="hidden" id="cf-response" name="cf-turnstile-response">


        <div class="form-group">
          <label>Your Name</label>
          <input type="text" name="name" required>
        </div>

        <div class="form-group">
          <label>Your Email</label>
          <input type="email" name="email" required>
        </div>

        <div class="form-group">
          <label>Message</label>
          <textarea name="message" rows="4" required></textarea>
        </div>

        <?php if (!empty($turnstileSiteKey)): ?>
        <div class="cf-turnstile"
             data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>"
             data-theme="light"
             data-callback="onTurnstileSuccess"></div>
        <?php else: ?>
        <p style="color:red;text-align:center;">⚠️ CAPTCHA not configured (missing TURNSTILE_SITEKEY)</p>
        <?php endif; ?>

        <button type="submit">Send Enquiry</button>
        <p id="formMsg" class="mt-2 text-sm text-center"></p>
      </form>
    </div>
  </div>

<script>
let turnstileResponse = null;

window.onTurnstileSuccess = function (token) {
    turnstileResponse = token;
    document.getElementById('cf-response').value = token; // Save token!
};



document.getElementById('enquiryForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const msg = document.getElementById('formMsg');
  msg.textContent = '';

  if (!turnstileResponse) {
    msg.style.color = 'red';
    msg.textContent = '⚠️ Please complete the CAPTCHA.';
    return;
  }

  msg.style.color = 'black';
  msg.textContent = '⏳ Sending...';

  const form = e.target;
  const formData = new FormData(form);
  formData.append('cf-turnstile-response', turnstileResponse);

  try {
    const res = await fetch(form.action, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      msg.style.color = 'green';
      msg.textContent = '✅ Enquiry submitted successfully!';
      form.reset();
    } else {
      msg.style.color = 'red';
      msg.textContent = '❌ ' + (data.error || 'Something went wrong.');
      console.error(data);
    }
  } catch (err) {
    msg.style.color = 'red';
    msg.textContent = '❌ Network error. Please try again.';
    console.error(err);
  } finally {
    turnstileResponse = null;
    if (typeof turnstile !== 'undefined' && turnstile.reset) turnstile.reset();
  }
});
</script>
</body>
</html>
