<?php
require_once __DIR__ . '/app/settings.php';

// Stripe
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// PayPal SDK
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\LiveEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

$provider = $_GET['provider'] ?? '';
$success = false;
$message = '';
$details = [];

if ($provider === 'stripe') {
    $session_id = $_GET['session_id'] ?? null;
    if (!$session_id) die("❌ Stripe session ID missing.");

    $session = \Stripe\Checkout\Session::retrieve($session_id);
    $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

    $payerEmail = $session->customer_email ?? '';
    $amount = $paymentIntent->amount_received / 100;
    $currency = strtoupper($paymentIntent->currency);
    $status = ucfirst($paymentIntent->status);
    $orderID = $session_id;

    $stmt = $pdo->prepare("INSERT INTO payments (provider, order_id, payer_email, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['stripe', $orderID, $payerEmail, $amount, $currency, $status]);

    $success = true;
    $message = "Your Stripe payment was successful!";
    $details = compact('orderID', 'payerEmail', 'amount', 'currency', 'status');

} elseif ($provider === 'paypal') {

    $clientId = $_ENV['PAYPAL_CLIENT_ID'];
    $clientSecret = $_ENV['PAYPAL_CLIENT_SECRET'];
    $mode = $_ENV['PAYPAL_MODE'] ?? 'sandbox';
    $orderID = $_GET['token'] ?? null;
    if (!$orderID) die("❌ PayPal order ID missing.");

    $environment = ($mode === 'live')
        ? new LiveEnvironment($clientId, $clientSecret)
        : new SandboxEnvironment($clientId, $clientSecret);

    $client = new PayPalHttpClient($environment);
    $request = new OrdersCaptureRequest($orderID);
    $request->prefer('return=representation');

    $response = $client->execute($request);
    $order = $response->result;

    $payerEmail = $order->payer->email_address ?? '';
    $amount = $order->purchase_units[0]->payments->captures[0]->amount->value ?? 0;
    $currency = $order->purchase_units[0]->payments->captures[0]->amount->currency_code ?? 'USD';
    $status = ucfirst($order->status);

    $stmt = $pdo->prepare("INSERT INTO payments (provider, order_id, payer_email, amount, currency, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['paypal', $orderID, $payerEmail, $amount, $currency, $status]);

    $success = true;
    $message = "Your PayPal payment was successful!";
    $details = compact('orderID', 'payerEmail', 'amount', 'currency', 'status');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful</title>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f7f9fc;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .card {
        background: #fff;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        text-align: center;
        max-width: 500px;
        width: 90%;
    }
    .success-icon {
        font-size: 64px;
        color: #2ecc71;
        margin-bottom: 10px;
    }
    h2 {
        margin: 10px 0 20px;
        color: #2c3e50;
    }
    p {
        font-size: 16px;
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
    .btn:hover {
        background: #2980b9;
    }
</style>
</head>
<body>

<div class="card">
    <div class="success-icon">✅</div>
    <h2><?= htmlspecialchars($message) ?></h2>
    <p>Thank you for your payment. Your transaction has been completed successfully.</p>

    <table>
        <?php foreach ($details as $key => $value): ?>
            <tr>
                <td><strong><?= ucfirst($key) ?></strong></td>
                <td><?= htmlspecialchars($value) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <a href="catalog.php" class="btn">Continue Shopping</a>
</div>

</body>
</html>
