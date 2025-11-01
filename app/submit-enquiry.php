<?php
// ------------------------------------------------------------
// Chandusoft - Submit Enquiry Endpoint
// ------------------------------------------------------------
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/env.php'; // ✅ Load .env variables

setup_error_handling('development'); // Optional

try {
    // ------------------------------------------------------------
    // 1️⃣ Verify Turnstile secret exists
    // ------------------------------------------------------------
    $secret = $_ENV['TURNSTILE_SECRET'] ?? getenv('TURNSTILE_SECRET');
    if (empty($secret)) {
        echo json_encode([
            'success' => false,
            'error' => 'Server captcha secret not configured.',
            'debug_env_keys' => array_keys($_ENV)
        ]);
        exit;
    }

    // ------------------------------------------------------------
    // 2️⃣ Verify CAPTCHA token
    // ------------------------------------------------------------
    $token = $_POST['cf-turnstile-response'] ?? '';
    if (empty($token)) {
        echo json_encode(['success' => false, 'error' => 'Captcha missing.']);
        exit;
    }

    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $response = file_get_contents($verifyUrl, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ])
        ]
    ]));

    $captchaResult = json_decode($response, true);

    if (empty($captchaResult['success'])) {
        log_error('Captcha failed: ' . json_encode($captchaResult));
        echo json_encode(['success' => false, 'error' => 'Captcha verification failed.']);
        exit;
    }

    // ------------------------------------------------------------
    // 3️⃣ Validate and process enquiry
    // ------------------------------------------------------------
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($name === '' || $email === '' || $message === '') {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']);
        exit;
    }

    // ------------------------------------------------------------
    // 4️⃣ Insert into database
    // ------------------------------------------------------------
    $stmt = $pdo->prepare("INSERT INTO enquiries (product_id, name, email, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $ok = $stmt->execute([$product_id, $name, $email, $message]);

    if (!$ok) {
        log_error('DB Insert Failed: ' . json_encode($stmt->errorInfo()));
        echo json_encode(['success' => false, 'error' => 'Database insert failed.']);
        exit;
    }

    // ------------------------------------------------------------
    // 5️⃣ Success response
    // ------------------------------------------------------------
    log_info("New enquiry submitted for product_id=$product_id by $email");
    echo json_encode(['success' => true, 'message' => '✅ Enquiry saved successfully.']);

} catch (Exception $e) {
    log_error('Submit enquiry error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
