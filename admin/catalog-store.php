<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Auth & DB
require __DIR__ . '/../app/env.php';
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();
$logFile = __DIR__ . '/../storage/logs/catalog.log';

function catalog_log($level, $message, $logFile)
{
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Load Turnstile secret
    $turnstile_secret = env('TURNSTILE_SECRET', '');
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    if (empty($turnstile_secret)) {
        header("Location: catalog-create?error=" . urlencode("Server captcha secret not configured."));
        exit;
    }

    // Missing token = fail
    if (empty($turnstile_response)) {
        catalog_log('error', 'Missing Turnstile token', $logFile);
        header("Location: catalog-create?error=" . urlencode("Captcha missing."));
        exit;
    }

    // Verify with Cloudflare
    $verify = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($verify, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret'   => $turnstile_secret,
            'response' => $turnstile_response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);

    $result = curl_exec($verify);
    $curlErr = curl_error($verify);
    $httpCode = curl_getinfo($verify, CURLINFO_HTTP_CODE);
    curl_close($verify);

    // Debug log
    file_put_contents(__DIR__ . '/../storage/logs/turnstile_debug.log',
        "HTTP: $httpCode\nCurlError: $curlErr\nResponse: $result\n\n",
        FILE_APPEND
    );

    if ($result === false) {
        catalog_log('error', "Turnstile network error: $curlErr", $logFile);
        header("Location: catalog-create?error=" . urlencode("Service temporarily unavailable. Try again."));
        exit;
    }

    $json = json_decode($result, true);

    if (empty($json['success'])) {
        catalog_log('error', "Turnstile failed: $result", $logFile);
        header("Location: catalog-create?error=" . urlencode("Captcha verification failed."));
        exit;
    }

    // ---------------------------
    // FORM VALIDATION
    // ---------------------------
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $price = $_POST['price'] ?? 0;
    $short_desc = trim($_POST['short_desc'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    if (empty($title) || !is_numeric($price)) {
        header("Location: catalog-create?error=" . urlencode("Title and valid price are required."));
        exit;
    }

    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }

    // Unique slug
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time();
    }

    // ---------------------------
    // IMAGE UPLOAD
    // ---------------------------
    $image_path = '';

    if (!empty($_FILES['image']['tmp_name'])) {

        $year = date('Y');
        $month = date('m');
        $uploadDir = __DIR__ . "/../uploads/$year/$month/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmp = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $newFile = uniqid('img_', true) . ".$ext";
        $destPath = $uploadDir . $newFile;

        move_uploaded_file($fileTmp, $destPath);

        $image_path = "/uploads/$year/$month/$newFile";
    }

    // ---------------------------
    // INSERT INTO DATABASE
    // ---------------------------
    $stmt = $pdo->prepare("INSERT INTO catalog_items
        (title, slug, price, image_path, short_desc, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");

    $stmt->execute([$title, $slug, $price, $image_path, $short_desc, $status]);

    header("Location: catalog-list?success=" . urlencode("Catalog item created successfully."));
    exit;
}
?>
