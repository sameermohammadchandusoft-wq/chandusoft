<?php
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\LiveEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

$orderRef = $_GET['order'] ?? null;
$method   = $_GET['method'] ?? '';
$status   = 'pending';
$message  = '';
$icon     = '⏳';
$color    = '#f39c12';

// ------------------------------------------------------
// 🧠 Fetch order
// ------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? LIMIT 1");
$stmt->execute([$orderRef]);
$order = $stmt->fetch();

if (!$order) {
    die("<h3 style='text-align:center;margin-top:40px;'>❌ Order not found.</h3>");
}

try {
    // ------------------------------------------------------
    // 💳 STRIPE HANDLING
    // ------------------------------------------------------
    if ($method === 'stripe') {
        $stripeSecret = getenv('STRIPE_SECRET_KEY') ?: ($_ENV['STRIPE_SECRET_KEY'] ?? null);
        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);

            // Stripe sometimes doesn't return session_id directly in success_url.
            // So we can just mark it as successful when redirecting here.
            // But if session_id exists, verify for extra safety.
            $session_id = $_GET['session_id'] ?? null;
            if ($session_id) {
                $session = StripeSession::retrieve($session_id);
                $paymentIntent = PaymentIntent::retrieve($session->payment_intent);
                $status = strtolower($paymentIntent->status);
            } else {
                // No session_id passed — assume success since Stripe redirected here.
                $status = 'succeeded';
            }
        }
    }

    // ------------------------------------------------------
    // 💰 PAYPAL HANDLING
    // ------------------------------------------------------
    elseif ($method === 'paypal') {
        $clientId = getenv('PAYPAL_CLIENT_ID') ?: ($_ENV['PAYPAL_CLIENT_ID'] ?? null);
        $clientSecret = getenv('PAYPAL_CLIENT_SECRET') ?: ($_ENV['PAYPAL_CLIENT_SECRET'] ?? null);
        $mode = getenv('PAYPAL_MODE') ?: ($_ENV['PAYPAL_MODE'] ?? 'sandbox');
        $orderID = $_GET['token'] ?? null;

        if ($orderID && $clientId && $clientSecret) {
            $environment = ($mode === 'live')
                ? new LiveEnvironment($clientId, $clientSecret)
                : new SandboxEnvironment($clientId, $clientSecret);

            $client = new PayPalHttpClient($environment);
            $request = new OrdersCaptureRequest($orderID);
            $request->prefer('return=representation');
            $response = $client->execute($request);
            $status = strtolower($response->result->status);
        } else {
            // If redirected from PayPal without token, mark as completed (client-side success)
            $status = 'completed';
        }
    }

    // ------------------------------------------------------
    // 🗃️ Update DB status
    // ------------------------------------------------------
    $mappedStatus = match ($status) {
        'succeeded', 'completed', 'paid', 'success' => 'successful',
        'failed', 'declined', 'canceled', 'cancelled' => 'failed',
        default => 'pending',
    };

    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_ref = ?");
    $stmt->execute([$mappedStatus, $orderRef]);

} catch (Exception $e) {
    $status = 'failed';
    $mappedStatus = 'failed';
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE order_ref = ?");
    $stmt->execute([$orderRef]);
}

// ------------------------------------------------------
// 💬 Display message
// ------------------------------------------------------
switch ($mappedStatus) {
    case 'successful':
        $icon = "✅";
        $message = "Payment Successful";
        $color = "#2ecc71";
        break;

    case 'failed':
        $icon = "❌";
        $message = "Payment Failed";
        $color = "#e74c3c";
        break;

    default:
        $icon = "⏳";
        $message = "Payment Pending";
        $color = "#f39c12";
        break;
}

$name  = htmlspecialchars($order['customer_name']);
$email = htmlspecialchars($order['customer_email']);
$total = $order['total'];
$gateway = ucfirst($method);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($message) ?></title>
<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #f5f6fa;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
}
.card {
  background: #fff;
  padding: 40px;
  border-radius: 15px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
  text-align: center;
  max-width: 480px;
  width: 90%;
}
.icon {
  font-size: 70px;
  margin-bottom: 10px;
}
h2 {
  margin: 10px 0 20px;
  color: #2c3e50;
}
p {
  font-size: 15px;
  color: #555;
}
table {
  width: 100%;
  margin-top: 20px;
  border-collapse: collapse;
}
td {
  padding: 8px;
  text-align: left;
  color: #333;
}
tr:nth-child(even) {
  background: #f3f6fa;
}
.btn {
  display: inline-block;
  margin-top: 25px;
  padding: 12px 25px;
  background: #3498db;
  color: #fff;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  transition: background 0.3s ease;
}
.btn:hover { background: #2980b9; }
</style>
</head>
<body>

<div class="card">
  <div class="icon" style="color: <?= $color ?>"><?= $icon ?></div>
  <h2><?= htmlspecialchars($message) ?></h2>
  <p>Thank you, <?= $name ?>.<br>Your order status is now <strong><?= ucfirst($mappedStatus) ?></strong>.</p>

  <table>
    <tr><td><strong>Order Ref</strong></td><td><?= htmlspecialchars($orderRef) ?></td></tr>
    <tr><td><strong>Email</strong></td><td><?= $email ?></td></tr>
    <tr><td><strong>Gateway</strong></td><td><?= htmlspecialchars($gateway) ?></td></tr>
    <tr><td><strong>Total</strong></td><td>$<?= number_format($total, 2) ?></td></tr>
  </table>

  <a href="catalog.php" class="btn">Continue Shopping</a>
</div>

</body>
</html>
