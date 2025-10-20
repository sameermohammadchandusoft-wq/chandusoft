<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

// ✅ Turnstile verification
$token = $_POST['cf-turnstile-response'] ?? '';
$secret = '0x4AAAAAAB7ii73wAJ7ecUp7fBr4RTvr5N8';

$verify = curl_init();
curl_setopt_array($verify, [
  CURLOPT_URL => "https://challenges.cloudflare.com/turnstile/v0/siteverify",
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POSTFIELDS => http_build_query([
    'secret' => $secret,
    'response' => $token
  ])
]);
$response = json_decode(curl_exec($verify), true);
curl_close($verify);

if (!$response['success']) {
  echo json_encode(["success" => false, "error" => "Turnstile verification failed."]);
  exit;
}

// ✅ Basic validation
if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
  echo json_encode(["success" => false, "error" => "All fields are required."]);
  exit;
}

// ✅ Save enquiry
$stmt = $pdo->prepare("INSERT INTO enquiries (product_id, name, email, message) VALUES (?, ?, ?, ?)");
$stmt->execute([
  $_POST['product_id'],
  $_POST['name'],
  $_POST['email'],
  $_POST['message']
]);

echo json_encode(["success" => true]);
?>
