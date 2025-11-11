<?php
session_start();
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/app/env.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/vendor/autoload.php';

$stripeSecret = getenv('STRIPE_SECRET_KEY');
if ($stripeSecret) \Stripe\Stripe::setApiKey($stripeSecret);

$paypalClientId = getenv('PAYPAL_CLIENT_ID');
$paypalEnabled  = !empty($paypalClientId);

if (empty($_SESSION['cart'])) {
    die("<h3 style='text-align:center;margin-top:40px;'>Your cart is empty. <a href='catalog.php'>Go back to shop</a></h3>");
}

$items = [];
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal = $item['price'] * $item['qty'];
    $item['subtotal'] = $subtotal;
    $items[] = $item;
    $total += $subtotal;
}

$error = '';
$orderRef = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (
        !isset($_POST['_csrf']) ||
        !isset($_SESSION['_csrf']) ||
        $_POST['_csrf'] !== $_SESSION['_csrf']
    ) {
        http_response_code(403);
        die("<h3 style='text-align:center;margin-top:40px;'>Security check failed. Please refresh the page and try again.</h3>");
    }
    unset($_SESSION['_csrf']);

    // Shipping / customer fields (new + existing)
    $ship_method = $_POST['ship_method'] ?? 'delivery'; // delivery|pickup (UI only)
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');           // NEW
    $country     = trim($_POST['country'] ?? '');         // NEW
    $state       = trim($_POST['state'] ?? '');           // NEW
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $pincode     = trim($_POST['pincode'] ?? '');
    $perm_name   = trim($_POST['perm_name'] ?? '');
    $perm_addr   = trim($_POST['perm_address'] ?? '');
    $perm_city   = trim($_POST['perm_city'] ?? '');
    $perm_pin    = trim($_POST['perm_pincode'] ?? '');
    $coupon      = trim($_POST['coupon'] ?? '');
    $gateway     = $_POST['gateway'] ?? '';

    if (strtolower($coupon) === 'discount10') {
        $total *= 0.9;
    }

    if ($name === '' || $email === '' || $address === '' || $city === '' || $pincode === '' || $phone === '' || $country === '' || $state === '') {
        $error = "Please fill in all required fields.";
    } elseif (!in_array($gateway, ['stripe', 'paypal', 'cod', 'upi'])) {
        $error = "Please select a valid payment method.";
    } else {
        try {
            $itemsJson = json_encode($items);
            // NOTE: requires DB migration (customer_phone, customer_country, customer_state)
            $stmt = $pdo->prepare("INSERT INTO orders 
                (order_ref, customer_name, customer_email, customer_phone, customer_country, customer_state,
                 customer_address, customer_city, customer_pincode,
                 perm_name, perm_address, perm_city, perm_pincode,
                 items_json, total, payment_status, gateway)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([
                $orderRef,
                $name, $email, $phone, $country, $state,
                $address, $city, $pincode,
                $perm_name, $perm_addr, $perm_city, $perm_pin,
                $itemsJson, $total, $gateway
            ]);

            $domain = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];

            if ($gateway === 'stripe') {
                $lineItems = [];
                foreach ($items as $it) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => $it['title']],
                            'unit_amount' => intval($it['price'] * 100), // cents
                        ],
                        'quantity' => $it['qty'],
                    ];
                }
                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'metadata' => [
                        'order_ref' => $orderRef
                    ],
                    'success_url' => $domain . '/checkout_success.php?order=' . urlencode($orderRef) . '&method=stripe',
                    'cancel_url'  => $domain . '/checkout_cancel.php?order=' . urlencode($orderRef),
                ]);

                $_SESSION['cart'] = [];
                header("Location: " . $session->url);
                exit;
            }

            if ($gateway === 'paypal') {
                header("Location: " . $domain . "/checkout_paypal.php?order=" . urlencode($orderRef));
                exit;
            }

            if ($gateway === 'cod') {
                $pdo->prepare("UPDATE orders SET payment_status='cod_confirmed' WHERE order_ref=?")->execute([$orderRef]);
                $_SESSION['cart'] = [];
                header("Location: checkout_success.php?order=$orderRef&method=cod");
                exit;
            }

            if ($gateway === 'upi') {
                $pdo->prepare("UPDATE orders SET payment_status='awaiting_upi' WHERE order_ref=?")->execute([$orderRef]);
                $_SESSION['cart'] = [];
                header("Location: checkout_upi.php?order=$orderRef");
                exit;
            }

        } catch (Exception $e) {
            $error = "Checkout failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout - Chandusoft</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --primary:#4c7ff0; --primary-hover:#335fd4; --text:#1a1a1a; --muted:#6d6d6d;
  --border:#e2e6ee; --bg:#f8fafc; --white:#fff; --radius:10px;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:var(--bg);padding:40px;display:flex;justify-content:center;}
.checkout-container{max-width:1150px;width:100%;display:flex;gap:28px;}
.left,.right{background:var(--white);padding:32px;border-radius:var(--radius);border:1px solid var(--border);}
.left{flex:1.8} .right{flex:1;height:fit-content;position:sticky;top:30px;}
h1{font-size:26px;font-weight:600;color:#111;margin-bottom:10px;}
.section-sub{font-size:14px;color:var(--muted);margin-bottom:18px;}
h2{font-size:18px;font-weight:600;color:#111;padding-bottom:12px;margin-bottom:18px;border-bottom:1px solid var(--border);}

/* Shipping toggle */
.ship-toggle{display:flex;gap:12px;margin:6px 0 14px 0;}
.ship-pill{flex:0 0 auto;display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:8px;border:1px solid var(--border);cursor:pointer;background:#fff;font-size:13px;}
.ship-pill input{accent-color:var(--primary);}
.ship-pill.active{border-color:var(--primary);background:#f0f5ff;}

/* Grid layout for form */
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}

label{font-size:14px;font-weight:500;color:#222;margin-top:14px;display:block;}
input[type=text],input[type=email],select{
  width:100%;padding:12px;border-radius:8px;background:#fff;border:1px solid #dfe3eb;font-size:14px;transition:.18s;
}
input:focus,select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(76,127,240,.18);outline:none;}

.show-coupon-link{margin-top:12px;font-size:14px;color:var(--primary);cursor:pointer;text-decoration:underline;}
.coupon {
  display: flex;
  align-items: center;
  margin-top: 18px;
  gap: 10px;
}

.coupon input {
  flex: 1;
  padding: 12px;
  border-radius: 8px;
  border: 1px solid var(--border);
  font-size: 15px;
}

.coupon button {
  padding: 12px 18px;
  background: var(--primary);
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  border: none;
  cursor: pointer;
  transition: 0.2s;
  width: auto;
}

.coupon button:hover {
  background: var(--primary-hover);
}

table{width:100%;margin-top:12px;border-collapse:collapse;}
th,td{padding:12px 0;border-bottom:1px solid var(--border);font-size:14px;color:#111;}
.total{font-weight:700;text-align:right;}
button{margin-top:22px;width:100%;padding:15px;background:var(--primary);color:#fff;border:none;border-radius:6px;font-weight:600;font-size:15px;transition:.25s;cursor:pointer;}
button:hover{background:var(--primary-hover);}
.error{padding:12px;background:#ffe5e5;border-left:4px solid #d22;color:#b30000;border-radius:6px;margin-bottom:16px;}

@media(max-width:900px){body{padding:20px}.checkout-container{flex-direction:column}.right{position:relative;top:unset}}
</style>
</head>
<body>

<form method="POST" style="width:100%;height:100%;">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf']); ?>">

  <div class="checkout-container">
    <div class="left">
      <h1>Checkout</h1>
      <div class="section-sub">Shipping Information</div>

      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Delivery / Pickup toggle -->
      <div class="ship-toggle">
        <label class="ship-pill active">
          <input type="radio" name="ship_method" value="delivery" checked>
          Delivery
        </label>
        <label class="ship-pill">
          <input type="radio" name="ship_method" value="pickup">
          Pick up
        </label>
      </div>

      <label>Full name *</label>
      <input type="text" name="name" required placeholder="Enter full name">

      <div class="grid-2">
        <div>
          <label>Email address *</label>
          <input type="email" name="email" required placeholder="Enter email address">
        </div>
        <div>
          <label>Phone number *</label>
          <input type="text" name="phone" required placeholder="Enter phone number">
        </div>
      </div>

      <label>Address *</label>
      <input type="text" name="address" required placeholder="Street address">

      <div class="grid-3">
        <div>
          <label>Country *</label>
          <select name="country" required>
            <option value="">Choose country</option>
            <option value="India">India</option>
            <option value="United States">United States</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="Canada">Canada</option>
            <option value="Australia">Australia</option>
          </select>
        </div>
        <div>
          <label>State *</label>
          <input type="text" name="state" required placeholder="Enter state">
        </div>
        <div>
          <label>ZIP Code *</label>
          <input type="text" name="pincode" required placeholder="Enter ZIP code">
        </div>
      </div>

      <div class="grid-2">
        <div>
          <label>City *</label>
          <input type="text" name="city" required placeholder="Enter city">
        </div>
        <div></div>
      </div>

      <h2>Permanent Address</h2>
      <label><input type="checkbox" id="sameAddress"> Same as shipping</label>

      <label>Full name</label>
      <input type="text" name="perm_name" id="perm_name" placeholder="Enter full name">

      <label>Address</label>
      <input type="text" name="perm_address" id="perm_address" placeholder="Street address">

      <div class="grid-3">
        <div>
          <label>City</label>
          <input type="text" name="perm_city" id="perm_city" placeholder="City">
        </div>
        <div>
          <label>State</label>
          <input type="text" id="perm_state" placeholder="State (optional)">
        </div>
        <div>
          <label>ZIP Code</label>
          <input type="text" name="perm_pincode" id="perm_pincode" placeholder="ZIP code">
        </div>
      </div>
    </div>

    <div class="right">
      <h2>Review your cart</h2>

      <div class="payment-methods">
        <label class="option"><input type="radio" name="gateway" value="stripe" checked> Stripe (Card)</label>
        <label class="option"><input type="radio" name="gateway" value="upi"> UPI (Coming Soon)</label>
        <?php if ($paypalEnabled): ?>
        <label class="option"><input type="radio" name="gateway" value="paypal"> PayPal</label>
        <?php endif; ?>
        <label class="option"><input type="radio" name="gateway" value="cod"> Cash on Delivery</label>
      </div>

      <p id="showCoupon" class="show-coupon-link">Have a discount code?</p>
      <div class="coupon" id="couponBox" style="display:none;">
        <input type="text" name="coupon" placeholder="Discount code">
        <button type="submit" name="apply_coupon">Apply</button>
      </div>

      <table>
        <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['title']) ?></td>
          <td><?= $it['qty'] ?></td>
          <td>$<?= number_format($it['price'], 2) ?></td>
          <td>$<?= number_format($it['subtotal'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr><td colspan="3" class="total">Total:</td><td class="total">$<?= number_format($total, 2) ?></td></tr>
      </table>

      <button type="submit">Pay Now — $<?= number_format($total, 2) ?></button>
    </div>
  </div>
</form>

<script>
// Same-as shipping checkbox -> copy fields
const checkbox = document.getElementById('sameAddress');
const billingFields = ['name','address','city','pincode'];
checkbox.addEventListener('change', () => {
  billingFields.forEach(f => {
    const src = document.querySelector(`[name="${f}"]`);
    const dest = document.getElementById('perm_' + f);
    if (src && dest) dest.value = checkbox.checked ? src.value : '';
  });
});
billingFields.forEach(f => {
  const src = document.querySelector(`[name="${f}"]`);
  if (src) src.addEventListener('input', () => {
    const dest = document.getElementById('perm_' + f);
    if (checkbox.checked && dest) dest.value = src.value;
  });
});

// Toggle UI highlight
document.querySelectorAll('.ship-pill input[name="ship_method"]').forEach(r => {
  r.addEventListener('change', () => {
    document.querySelectorAll('.ship-pill').forEach(p => p.classList.remove('active'));
    r.closest('.ship-pill').classList.add('active');
  });
});

// Coupon toggle
document.getElementById("showCoupon").addEventListener("click", () => {
  const box = document.getElementById("couponBox");
  box.style.display = box.style.display === "none" ? "flex" : "none";
});
</script>
</body>
</html>
