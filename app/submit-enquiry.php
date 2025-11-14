<?php
// ------------------------------------------------------------
// Chandusoft - Submit Enquiry Endpoint
// ------------------------------------------------------------
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/env.php'; // Load .env variables

setup_error_handling('development');

try {

    // ------------------------------------------------------------
    // 1️⃣ Load Turnstile Secret
    // ------------------------------------------------------------
    $secret = $_ENV['TURNSTILE_SECRET'] ?? getenv('TURNSTILE_SECRET');

    if (empty($secret)) {
        echo json_encode([
            'success' => false,
            'error' => 'Server CAPTCHA secret not configured.',
            'env_loaded' => array_keys($_ENV)
        ]);
        exit;
    }

    // ------------------------------------------------------------
    // 2️⃣ Get Token from form
    // ------------------------------------------------------------
    $token = $_POST['cf-turnstile-response'] ?? '';

    if (empty($token)) {
        echo json_encode([
            'success' => false,
            'error' => 'Captcha missing.'
        ]);
        exit;
    }

    // ------------------------------------------------------------
    // 3️⃣ Verify Turnstile with Cloudflare
    // ------------------------------------------------------------
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $responseData = http_build_query([
        'secret'   => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $options = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/x-www-form-urlencoded",
            "content" => $responseData
        ]
    ];

    $response = file_get_contents($verifyUrl, false, stream_context_create($options));
    $captcha = json_decode($response, true);

    // Debug log (optional)
    file_put_contents(__DIR__ . '/turnstile_debug.log', print_r([
        'POST' => $_POST,
        'captcha_response' => $captcha
    ], true), FILE_APPEND);

    if (empty($captcha['success'])) {
        log_error("Captcha failed: " . json_encode($captcha));
        echo json_encode([
            'success' => false,
            'error' => 'Captcha verification failed.'
        ]);
        exit;
    }

    // ------------------------------------------------------------
    // 4️⃣ Validate Form Data
    // ------------------------------------------------------------
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $message    = trim($_POST['message'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($name === '' || $email === '' || $message === '') {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }

    // ------------------------------------------------------------
    // 5️⃣ Save to Database
    // ------------------------------------------------------------
    $stmt = $pdo->prepare("
        INSERT INTO enquiries (product_id, name, email, message, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $ok = $stmt->execute([$product_id, $name, $email, $message]);

    if (!$ok) {
        log_error("DB Insert Failed: " . json_encode($stmt->errorInfo()));
        echo json_encode(['success' => false, 'error' => 'Database error.']);
        exit;
    }

    // ------------------------------------------------------------
    // 6️⃣ Success Response
    // ------------------------------------------------------------
    log_info("Enquiry submitted for product_id=$product_id by $email");

    echo json_encode([
        'success' => true,
        'message' => 'Enquiry submitted successfully!'
    ]);

} catch (Exception $e) {

    log_error("submit-enquiry ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

?>
