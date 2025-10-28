<?php
require __DIR__ . '/db.php';

// ----------------------
// CONFIG
// ----------------------
$TURNSTILE_SECRET = '0x4AAAAAAB7ii73wAJ7ecUp7fBr4RTvr5N8';

// Enable PDO exceptions
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ----------------------
// ONLY POST REQUESTS
// ----------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ----------------------
// GET POST DATA
// ----------------------
$product_id = $_POST['product_id'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');
$token = $_POST['cf-turnstile-response'] ?? '';

header('Content-Type: application/json');

// ----------------------
// BASIC VALIDATIONS
// ----------------------
if (!$product_id || !$name || !$email || !$message) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit;
}

if (empty($token)) {
    echo json_encode(['success' => false, 'error' => 'CAPTCHA response missing.']);
    exit;
}

// ----------------------
// VERIFY TURNSTILE
// ----------------------
$verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
$data = http_build_query([
    'secret' => $TURNSTILE_SECRET,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'],
]);

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => $data,
        'timeout' => 10,
    ],
];

$context = stream_context_create($options);
$result = @file_get_contents($verifyUrl, false, $context);

if ($result === false) {
    echo json_encode(['success' => false, 'error' => 'Unable to verify CAPTCHA. Try again later.']);
    exit;
}

$resp = json_decode($result, true);

if (empty($resp['success']) || !$resp['success']) {
    echo json_encode(['success' => false, 'error' => 'CAPTCHA verification failed.']);
    exit;
}

// ----------------------
// VALIDATE PRODUCT EXISTS
// ----------------------
$stmt = $pdo->prepare("SELECT id FROM catalog_items WHERE id = ?");
$stmt->execute([$product_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Invalid product selected.']);
    exit;
}

// ----------------------
// INSERT ENQUIRY
// ----------------------
try {
    $stmt = $pdo->prepare("
        INSERT INTO enquiries (product_id, name, email, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$product_id, $name, $email, $message]);

    echo json_encode(['success' => true]);
} catch (PDOException $ex) {
    // For debugging: return the exact PDO error
    echo json_encode(['success' => false, 'error' => $ex->getMessage()]);
}
