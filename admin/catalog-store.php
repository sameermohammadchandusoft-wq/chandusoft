<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: catalog-create.php');
    exit;
}

// Sanitize & collect form data
$title = trim($_POST['title'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$price = $_POST['price'] ?? 0;
$short_desc = trim($_POST['short_desc'] ?? '');
$status = $_POST['status'] ?? 'draft';

// Basic validation
if (empty($title) || !is_numeric($price)) {
    header("Location: catalog-create.php?error=" . urlencode("Title and valid price are required."));
    exit;
}

// Auto-generate slug if empty
if (empty($slug)) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
}

// Check for duplicate slug
$stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE slug = ?");
$stmt->execute([$slug]);
if ($stmt->fetchColumn() > 0) {
    // Append timestamp to make slug unique
    $slug .= '-' . time();
}

$image_path = ''; // Default value if no image uploaded

// Handle image upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = $_FILES['image']['name'];
    $fileSize = $_FILES['image']['size'];
    $fileType = $_FILES['image']['type'];

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($fileType, $allowedTypes)) {
        $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        header("Location: catalog-create.php?error=" . urlencode($error));
        exit;
    } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB
        $error = "Image must be smaller than 2MB.";
        header("Location: catalog-create.php?error=" . urlencode($error));
        exit;
    } else {
        // Get current year and month
        $year = date('Y');
        $month = date('m');

        // Define the upload directory (e.g., /uploads/YYYY/MM/)
        $uploadDir = __DIR__ . '/../uploads/' . $year . '/' . $month . '/';

        // Check if the upload directory exists, if not, create it
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);  // Create directories with write permissions
        }

        // Sanitize file name and move the uploaded file
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('img_', true) . '.' . $ext;
        $destPath = $uploadDir . $newFileName;

        // Move the uploaded file to the designated folder
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Save the relative path in the database
            $image_path = '/uploads/' . $year . '/' . $month . '/' . $newFileName;
        } else {
            $error = "Failed to move uploaded image.";
            header("Location: catalog-create.php?error=" . urlencode($error));
            exit;
        }
    }
}

// Insert into DB
try {
    $stmt = $pdo->prepare("INSERT INTO catalog_items 
        (title, slug, price, image_path, short_desc, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");

    $stmt->execute([$title, $slug, $price, $image_path, $short_desc, $status]);

    // Redirect to a catalog list page (or show success)
    header("Location: catalog-list.php?message=" . urlencode("Catalog item created successfully."));
    exit;
} catch (PDOException $e) {
    // On failure, redirect back to form with error
    $errorMsg = "Database error: " . $e->getMessage();
    header("Location: catalog-create.php?error=" . urlencode($errorMsg));
    exit;
}