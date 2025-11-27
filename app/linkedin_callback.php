<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Validate state
if ($_GET['state'] !== $_SESSION['linkedin_oauth_state']) {
    die("Invalid OAuth State.");
}

$client_id = "YOUR_LINKEDIN_CLIENT_ID";
$client_secret = "YOUR_LINKEDIN_CLIENT_SECRET";
$redirect_uri = "https://yourdomain.com/app/linkedin_callback.php";

$code = $_GET['code'];

// 1. Get access token
$token_url = "https://www.linkedin.com/oauth/v2/accessToken";

$data = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data),
    ]
];

$response = file_get_contents($token_url, false, stream_context_create($options));
$token_data = json_decode($response, true);
$access_token = $token_data['access_token'];

// 2. Get email
$email_json = file_get_contents(
    "https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))",
    false,
    stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer $access_token"
        ]
    ])
);
$email_data = json_decode($email_json, true);
$email = $email_data['elements'][0]['handle~']['emailAddress'];

// 3. Get name
$profile_json = file_get_contents(
    "https://api.linkedin.com/v2/me",
    false,
    stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer $access_token"
        ]
    ])
);
$profile = json_decode($profile_json, true);

$name = $profile['localizedFirstName'] . " " . $profile['localizedLastName'];

// 4. Login or create user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    login($user['id'], $name, 'user');
} else {
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?, ?, '', 'user')");
    $stmt->execute([$name, $email]);
    login($pdo->lastInsertId(), $name, 'user');
}

header("Location: /app/dashboard.php");
exit;
