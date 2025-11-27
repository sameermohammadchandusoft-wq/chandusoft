<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Validate state
if ($_GET['state'] !== $_SESSION['google_oauth_state']) {
    die("Invalid OAuth state.");
}

$client_id = "474844950796-sk8b8cik8gaal9444skruje2behnri2s.apps.googleusercontent.com";

$client_secret = "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";

$redirect_uri = "https://localhost/app/google_callback.php";
$code = $_GET['code'];

// 1. Get Access Token
$token_url = "https://oauth2.googleapis.com/token";

$data = [
    "code" => $code,
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
];

$options = [
    "http" => [
        "header" => "Content-Type: application/x-www-form-urlencoded",
        "method" => "POST",
        "content" => http_build_query($data),
    ]
];

$response = file_get_contents($token_url, false, stream_context_create($options));
$token_data = json_decode($response, true);

$access_token = $token_data["access_token"];

// 2. Get Google Profile Data
$profile_json = file_get_contents(
    "https://www.googleapis.com/oauth2/v2/userinfo",
    false,
    stream_context_create([
        "http" => [
            "header" => "Authorization: Bearer {$access_token}"
        ]
    ])
);

$profile = json_decode($profile_json, true);

// Extract data
$name = $profile["name"];
$email = $profile["email"];

// 3. Login or register user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    login($user["id"], $name, "user");
} else {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, '', 'user')");
    $stmt->execute([$name, $email]);
    login($pdo->lastInsertId(), $name, "user");
}

// Redirect to dashboard
header("Location: /app/dashboard.php");
exit;
