<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/logger.php';
setup_error_handling('development');

// ✅ Set Stripe Secret Key
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

// ✅ Read RAW Webhook Payload
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpointSecret = getenv('STRIPE_WEBHOOK_SECRET');

// ✅ Verify Webhook Signature
try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid signature"]);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload"]);
    exit;
}

$type = $event->type;
$session = $event->data->object;

// ✅ Metadata Passed from Checkout Session
$orderRef = $session->metadata['order_ref'] ?? null;

if (!$orderRef) {
    log_error("⚠️ Webhook received but no order_ref found.");
    http_response_code(200);
    exit;
}

// ✅ Handle Successful Payment
if ($type === 'checkout.session.completed') {

    $paymentIntent = $session->payment_intent ?? '';

    // ✅ Prevent marking paid multiple times (idempotency)
    $stmt = $pdo->prepare("SELECT payment_status FROM orders WHERE order_ref = ?");
    $stmt->execute([$orderRef]);
    $status = $stmt->fetchColumn();

    if ($status !== 'paid') {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', txn_id = ? WHERE order_ref = ?");
        $stmt->execute([$paymentIntent, $orderRef]);
        log_info("✅ Order $orderRef marked as PAID via webhook");
    }
}

// ✅ Handle Failed Payment
if ($type === 'payment_intent.payment_failed') {
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed' WHERE order_ref = ?");
    $stmt->execute([$orderRef]);
    log_info("❌ Payment failed for Order $orderRef");
}

http_response_code(200);
echo json_encode(["status" => "ok"]);
exit;
