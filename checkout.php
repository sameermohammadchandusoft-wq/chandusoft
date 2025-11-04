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
    $gateway = $_POST['gateway'] ?? '';

    if ($name === '' || $email === '') {
        $error = "Please fill in all fields.";
    } elseif (!in_array($gateway, ['stripe', 'paypal'])) {
        $error = "Please select a valid payment method.";
    } else {
        try {
            $itemsJson = json_encode($items);
            $stmt = $pdo->prepare("INSERT INTO orders 
                (order_ref, customer_name, customer_email, items_json, total, payment_status, gateway)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$orderRef, $name, $email, $itemsJson, $total, $gateway]);

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
            } elseif ($gateway === 'paypal') {
                // ✅ Direct redirect for PayPal handled via JS
                // Just save order record and let PayPal script handle redirection
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
  <title>Checkout</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: "Poppins", sans-serif;
      background: #f5f6fa;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }
    .checkout-container {
      width: 95%;
      max-width: 550px;
      background: #fff;
      margin: 40px 0;
      border-radius: 14px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      padding: 30px 35px;
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 25px;
      font-size: 26px;
    }
    h3 {
      margin-top: 20px;
      color: #555;
      border-bottom: 1px solid #eee;
      padding-bottom: 5px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      padding: 10px 8px;
      border-bottom: 1px solid #f0f0f0;
      font-size: 15px;
    }
    th { text-align: left; background: #fafafa; }
    tr:last-child td { border-bottom: none; }
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
      transition: border-color 0.2s;
    }
    input:focus { border-color: #007bff; outline: none; }
    .payment-options {
      margin-top: 15px;
      display: flex;
      gap: 15px;
      align-items: center;
    }
    .payment-option {
      display: flex;
      align-items: center;
      gap: 5px;
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
      transition: background 0.3s, transform 0.2s;
    }
    button:hover {
      background: linear-gradient(135deg, #0056d2, #0040aa);
      transform: translateY(-1px);
    }
    .error {
      color: #e74c3c;
      background: #fdecea;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 10px;
      text-align: center;
      font-size: 14px;
    }
    .total {
      text-align: right;
      font-weight: bold;
      color: #000;
    }
  </style>
</head>
<body>
  <div class="checkout-container">
    <h2>💳 Checkout</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h3>Order Summary</h3>
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

    <form method="POST" action="">
      <label>Full Name</label>
      <input type="text" name="name" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Payment Method</label>
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

      <button type="submit">Pay with Stripe — $<?= number_format($total, 2) ?></button>
    </form>

    <div id="paypal-button-container" style="margin-top:20px; display:none;"></div>
  </div>

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
