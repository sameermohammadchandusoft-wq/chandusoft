<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ----------------------------------------------------
// ✅ Load Environment Variables
// ----------------------------------------------------

// ----------------------------------------------------
// ✅ Auth & Database
// ----------------------------------------------------
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user(); // e.g. ['id'=>1, 'name'=>'root', 'role'=>'admin']

// Dedicated catalog log file
$logFile = __DIR__ . '/../storage/logs/catalog.log';

// ----------------------------------------------------
// 🪵 Logging Helper
// ----------------------------------------------------
function catalog_log($level, $message, $logFile)
{
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $formatted, FILE_APPEND);
}

// ----------------------------------------------------
// 🖼️ Image Resize Helper
// ----------------------------------------------------
function resizeImage($imagePath, $ext, $maxWidth = 1600)
{
    list($width, $height) = getimagesize($imagePath);
    if ($width <= $maxWidth) return;

    $newWidth = $maxWidth;
    $newHeight = intval($height * $newWidth / $width);

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $img = imagecreatefromjpeg($imagePath);
            break;
        case 'png':
            $img = imagecreatefrompng($imagePath);
            break;
        case 'gif':
            $img = imagecreatefromgif($imagePath);
            break;
        default:
            return;
    }

    $resized = imagecreatetruecolor($newWidth, $newHeight);
    if (in_array($ext, ['png', 'gif'])) {
        imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }

    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($resized, $imagePath, 85);
            break;
        case 'png':
            imagepng($resized, $imagePath);
            break;
        case 'gif':
            imagegif($resized, $imagePath);
            break;
    }

    imagedestroy($img);
    imagedestroy($resized);
}

// ----------------------------------------------------
// 🗜️ Generate WebP Helper
// ----------------------------------------------------
function generateWebP($imagePath, $ext)
{
    if (!function_exists('imagewebp')) return;
    $webpPath = preg_replace('/\.[a-zA-Z]+$/', '.webp', $imagePath);

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $img = imagecreatefromjpeg($imagePath);
            break;
        case 'png':
            $img = imagecreatefrompng($imagePath);
            break;
        case 'gif':
            $img = imagecreatefromgif($imagePath);
            break;
        default:
            return;
    }

    imagewebp($img, $webpPath, 80);
    imagedestroy($img);
}

// ----------------------------------------------------
// 🧩 MAIN FORM HANDLER
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ Load Turnstile secret from .env
    $turnstile_secret = $_ENV['TURNSTILE_SECRET'] ?? getenv('TURNSTILE_SECRET') ?? '';

    if (empty($turnstile_secret)) {
        $errorMsg = "Server captcha secret not configured.";
        catalog_log('error', $errorMsg, $logFile);
        header("Location: catalog-create?error=" . urlencode($errorMsg));
        exit;
    }

    // ✅ CAPTCHA Verification
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    if (!empty($turnstile_response)) {
        $verify = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($verify, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $turnstile_secret,
                'response' => $turnstile_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $result = curl_exec($verify);
        curl_close($verify);
        $json = json_decode($result, true);

        if (empty($json['success'])) {
            $errorMsg = "Captcha verification failed.";
            catalog_log('error', $errorMsg, $logFile);
            header("Location: catalog-create?error=" . urlencode($errorMsg));
            exit;
        }
    }

    // ----------------------------------------------------
    // 📦 FORM FIELDS
    // ----------------------------------------------------
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

    // Ensure slug is unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time();
    }

    // ----------------------------------------------------
    // 🖼️ IMAGE UPLOAD
    // ----------------------------------------------------
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($fileType, $allowedTypes)) {
            header("Location: catalog-create?error=" . urlencode("Only JPG, PNG, GIF, and WEBP images allowed."));
            exit;
        } elseif ($fileSize > 2 * 1024 * 1024) {
            header("Location: catalog-create?error=" . urlencode("Image must be smaller than 2MB."));
            exit;
        }

        $year = date('Y');
        $month = date('m');
        $uploadDir = __DIR__ . "/../uploads/$year/$month/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (!is_writable($uploadDir)) {
            $errorMsg = "Upload failed - directory not writable: $uploadDir";
            catalog_log('error', $errorMsg, $logFile);
            header("Location: catalog-create?error=" . urlencode($errorMsg));
            exit;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid('img_', true) . '.' . $ext;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            resizeImage($destPath, $ext, 1600);
            generateWebP($destPath, $ext);
            $image_path = "/uploads/$year/$month/$newFileName";
            catalog_log('info', "File uploaded successfully by {$user['name']}: " . basename($image_path), $logFile);
        } else {
            $errorMsg = "Failed to move uploaded file.";
            catalog_log('error', $errorMsg, $logFile);
            header("Location: catalog-create?error=" . urlencode($errorMsg));
            exit;
        }
    }

    // ----------------------------------------------------
    // 💾 DATABASE INSERT
    // ----------------------------------------------------
    try {
        $stmt = $pdo->prepare("INSERT INTO catalog_items
            (title, slug, price, image_path, short_desc, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$title, $slug, $price, $image_path, $short_desc, $status]);

        header("Location: catalog-list?success=" . urlencode("Catalog item created successfully."));
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
        catalog_log('error', $errorMsg, $logFile);
        header("Location: catalog-create?error=" . urlencode($errorMsg));
        exit;
    }
}
?>
