<?php
session_start();
require_once __DIR__ . '/app/env.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/vendor/autoload.php';

// Stripe setup
$stripeSecret = getenv('STRIPE_SECRET_KEY');
if ($stripeSecret) {
    \Stripe\Stripe::setApiKey($stripeSecret);
}

// PayPal setup
$paypalClientId = getenv('PAYPAL_CLIENT_ID');
$paypalEnabled = !empty($paypalClientId);

// Check cart
if (empty($_SESSION['cart'])) {
    die("<h3 style='text-align:center;margin-top:40px;'>Your cart is empty. <a href='catalog.php'>Go back to shop</a></h3>");
}

// Build cart summary
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
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $coupon = trim($_POST['coupon'] ?? '');
    $gateway = $_POST['gateway'] ?? '';

    // Apply simple coupon (optional)
    if (strtolower($coupon) === 'discount10') {
        $total = $total * 0.9;
    }

    if ($name === '' || $email === '' || $address === '' || $city === '' || $pincode === '') {
        $error = "Please fill in all required fields.";
    } elseif (!in_array($gateway, ['stripe', 'paypal'])) {
        $error = "Please select a valid payment method.";
    } else {
        try {
            $itemsJson = json_encode($items);
            $stmt = $pdo->prepare("INSERT INTO orders 
                (order_ref, customer_name, customer_email, customer_address, customer_city, customer_pincode, items_json, total, payment_status, gateway)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$orderRef, $name, $email, $address, $city, $pincode, $itemsJson, $total, $gateway]);

            $domain = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'];

            if ($gateway === 'stripe') {
                $lineItems = [];
                foreach ($items as $it) {
                    $lineItems[] = [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => $it['title']],
                            'unit_amount' => intval($it['price'] * 100),
                        ],
                        'quantity' => $it['qty'],
                    ];
                }

                $session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => $domain . '/checkout_success.php?order=' . urlencode($orderRef) . '&method=stripe',
                    'cancel_url'  => $domain . '/checkout_cancel.php?order=' . urlencode($orderRef),
                ]);

                $_SESSION['cart'] = [];
                header("Location: " . $session->url);
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
<style>
* { box-sizing: border-box; }
body {
  font-family: "Poppins", sans-serif;
  background: #f4f6fa;
  margin: 0;
  padding: 40px 0;
}
.checkout-container {
  max-width: 1100px;
  margin: auto;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.08);
  overflow: hidden;
  display: flex;
  flex-wrap: wrap;
}
.left-side, .right-side {
  padding: 30px 35px;
}
.left-side {
  flex: 1.1;
  border-right: 1px solid #eee;
}
.right-side {
  flex: 1;
  background: #fafafa;
}
h2 {
  margin-top: 0;
  color: #333;
  font-size: 24px;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 10px;
}
label {
  display: block;
  margin-top: 15px;
  font-weight: 500;
  color: #444;
}
input[type="text"], input[type="email"] {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 15px;
}
.payment-options {
  margin-top: 15px;
  display: flex;
  gap: 15px;
  align-items: center;
}
.payment-option {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 15px;
}
button {
  margin-top: 25px;
  width: 100%;
  padding: 12px;
  font-size: 17px;
  background: linear-gradient(135deg, #007bff, #0056d2);
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}
button:hover {
  background: linear-gradient(135deg, #0056d2, #0040aa);
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
}
th, td {
  padding: 10px 8px;
  border-bottom: 1px solid #f0f0f0;
  font-size: 15px;
}
th { text-align: left; background: #f9f9f9; }
.total {
  text-align: right;
  font-weight: bold;
}
.error {
  color: #e74c3c;
  background: #fdecea;
  padding: 10px;
  border-radius: 6px;
  margin-bottom: 10px;
  text-align: center;
}
#paypal-button-container {
  margin-top: 20px;
}
@media(max-width: 768px){
  .checkout-container { flex-direction: column; }
  .left-side { border-right: none; border-bottom: 1px solid #eee; }
}
</style>
</head>
<body>

<form method="POST" action="">
<div class="checkout-container">
  <!-- LEFT SIDE -->
  <div class="left-side">
    <h2>🧾 Billing Details</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <label>Full Name</label>
    <input type="text" name="name" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Address</label>
    <input type="text" name="address" required>

    <label>City</label>
    <input type="text" name="city" required>

    <label>Pincode</label>
    <input type="text" name="pincode" required>

    <label>Coupon Code (Optional)</label>
    <input type="text" name="coupon" placeholder="e.g. DISCOUNT10">
  </div>

  <!-- RIGHT SIDE -->
  <div class="right-side">
    <h2>💳 Payment & Order Summary</h2>

    <div class="payment-options">
      <label class="payment-option">
        <input type="radio" name="gateway" value="stripe" checked>
        <img src="https://img.icons8.com/color/48/stripe.png" alt="Stripe" width="22"> Stripe
      </label>
      <?php if ($paypalEnabled): ?>
      <label class="payment-option">
        <input type="radio" name="gateway" value="paypal">
        <img src="https://img.icons8.com/color/48/paypal.png" alt="PayPal" width="22"> PayPal
      </label>
      <?php endif; ?>
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
      <tr>
        <td colspan="3" class="total">Total:</td>
        <td class="total">$<?= number_format($total, 2) ?></td>
      </tr>
    </table>

    <button type="submit">Complete Payment — $<?= number_format($total, 2) ?></button>
    <div id="paypal-button-container" style="display:none;"></div>
  </div>
</div>
</form>

<?php if ($paypalEnabled): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= $paypalClientId ?>&currency=USD"></script>
<script>
const paypalContainer = document.getElementById("paypal-button-container");
const stripeRadio = document.querySelector('input[value="stripe"]');
const paypalRadio = document.querySelector('input[value="paypal"]');
const payButton = document.querySelector('button[type="submit"]');

function togglePaymentUI() {
  if (paypalRadio.checked) {
    payButton.style.display = "none";
    paypalContainer.style.display = "block";
  } else {
    payButton.style.display = "block";
    paypalContainer.style.display = "none";
  }
}
stripeRadio.addEventListener('change', togglePaymentUI);
paypalRadio.addEventListener('change', togglePaymentUI);
togglePaymentUI();

paypal.Buttons({
  createOrder: (data, actions) => {
    return actions.order.create({
      purchase_units: [{
        amount: { value: "<?= number_format($total, 2, '.', '') ?>" },
        description: "Order <?= htmlspecialchars($orderRef) ?>"
      }]
    });
  },
  onApprove: (data, actions) => {
    return actions.order.capture().then(details => {
      window.location.href = "checkout_success.php?order=<?= urlencode($orderRef) ?>&method=paypal&payer=" + encodeURIComponent(details.payer.email_address);
    });
  }
}).render('#paypal-button-container');
</script>
<?php endif; ?>
</body>
</html>
