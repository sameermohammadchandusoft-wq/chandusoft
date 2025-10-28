<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

// Logging helper
$logFile = __DIR__ . '/../storage/logs/catalog.log';
function logMessage($msg, $level = 'info') {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    $line = "[$time] [$level] $msg" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

// Resize image function (resize to max width)
function resizeImage($imagePath, $ext, $maxWidth = 1600) {
    list($width, $height) = getimagesize($imagePath);
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = intval($height * $newWidth / $width);

        switch (strtolower($ext)) {
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

        $resizedImg = imagecreatetruecolor($newWidth, $newHeight);

        if ($ext === 'png' || $ext === 'gif') {
            imagecolortransparent($resizedImg, imagecolorallocatealpha($resizedImg, 0, 0, 0, 127));
            imagealphablending($resizedImg, false);
            imagesavealpha($resizedImg, true);
        }

        imagecopyresampled($resizedImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($resizedImg, $imagePath, 85);
                break;
            case 'png':
                imagepng($resizedImg, $imagePath);
                break;
            case 'gif':
                imagegif($resizedImg, $imagePath);
                break;
        }

        imagedestroy($img);
        imagedestroy($resizedImg);
    }
}

// Generate WebP version of the image
function generateWebP($imagePath, $ext) {
    if (!function_exists('imagewebp')) return;

    $webpPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . pathinfo($imagePath, PATHINFO_FILENAME) . '.webp';

    switch (strtolower($ext)) {
        case 'jpg':
        case 'jpeg':
            $img = imagecreatefromjpeg($imagePath);
            imagewebp($img, $webpPath, 80);
            break;
        case 'png':
            $img = imagecreatefrompng($imagePath);
            imagewebp($img, $webpPath, 80);
            break;
        case 'gif':
            $img = imagecreatefromgif($imagePath);
            imagewebp($img, $webpPath, 80);
            break;
    }
    if (isset($img)) imagedestroy($img);
}

// Current user
$user = current_user();

// Only admins
if ($user['role'] !== 'admin') {
    logMessage("Unauthorized edit attempt by user ID {$user['id']}", 'error');
    die("Access denied.");
}

// Get catalog item ID from URL
$id = $_GET['id'] ?? null;
if (!$id) {
    logMessage("Invalid catalog item ID access attempt", 'error');
    die("Invalid catalog item ID.");
}

// Fetch existing item
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    logMessage("Catalog item not found for ID $id", 'error');
    die("Catalog item not found.");
}

$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Year/Month for upload path
    $year = date('Y');
    $month = date('m');

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $short_desc = trim($_POST['short_desc'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $image_path = $item['image_path'] ?? '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            logMessage("Upload failed - invalid file type by user {$user['id']}", 'error');
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $error = "Image must be smaller than 2MB.";
            logMessage("Upload failed - file too large by user {$user['id']}", 'error');
        } else {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = uniqid('img_', true) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/' . $year . '/' . $month . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                logMessage("File uploaded successfully by {$user['role']}: $newFileName", 'info');

                // Delete old image
                if (!empty($item['image_path']) && file_exists(__DIR__ . '/../' . ltrim($item['image_path'], '/'))) {
                    unlink(__DIR__ . '/../' . ltrim($item['image_path'], '/'));
                    logMessage("Deleted old image for item $id", 'info');
                }

                resizeImage($destPath, $ext, 1600);
                generateWebP($destPath, $ext);

                $image_path = '/uploads/' . $year . '/' . $month . '/' . $newFileName;
            } else {
                $error = "Failed to move uploaded image.";
                logMessage("Upload failed - directory not writable: $uploadDir", 'error');
            }
        }
    }

    // Validation
    if (empty($title)) {
        $error = "Title is required.";
        logMessage("Validation error on item $id: $error", 'error');
    } elseif (empty($slug)) {
        $error = "Slug is required.";
        logMessage("Validation error on item $id: $error", 'error');
    } else {
        // Check unique slug except current
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Slug already exists, please choose another.";
            logMessage("Validation error on item $id: $error", 'error');
        } elseif (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE catalog_items SET title=?, slug=?, price=?, short_desc=?, image_path=?, status=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$title, $slug, $price, $short_desc, $image_path, $status, $id]);
                $success = "Catalog item updated successfully.";
                logMessage("Updated catalog item $id by user {$user['id']}", 'info');

                // Refresh item
                $stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                logMessage("DB error updating item $id: " . $e->getMessage(), 'error');
            }
        }
    }
}

include __DIR__ . '/../admin/header1.php';
?>

<div class="dashboard-container">
    <h1>Edit Catalog Item</h1>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="title">Title *</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($item['title']) ?>" required>

        <label for="slug">Slug *</label>
        <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($item['slug']) ?>" required>

        <label for="price">Price *</label>
        <input type="number" name="price" id="price" step="0.01" min="0" value="<?= htmlspecialchars($item['price']) ?>" required>

        <label for="short_desc">Short Description</label>
        <textarea name="short_desc" id="short_desc" rows="3"><?= htmlspecialchars($item['short_desc']) ?></textarea>

        <label for="image">Upload Image (Max 2MB)</label><br>
        <?php if (!empty($item['image_path'])): ?>
            <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="max-width:150px; margin-bottom:10px; display:block; border-radius:6px;">
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/*">

        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="draft" <?= ($item['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($item['status'] === 'published') ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= ($item['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
        </select>

        <button type="submit">Update Item</button>
    </form>
</div>



<style>
.dashboard-container {
    max-width: 700px;
    margin: 40px auto;
    padding: 25px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}
.dashboard-container h1 {
    text-align: center;
    color: #244157;
    margin-bottom: 20px;
}
form {
    display: flex;
    flex-direction: column;
}
form label {
    font-weight: bold;
    margin-top: 15px;
}
form input[type="text"],
form input[type="number"],
form select,
form textarea {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-top: 5px;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}
form textarea {
    resize: vertical;
}
form button {
    margin-top: 20px;
    padding: 12px;
    background: #1690e8;
    color: #fff;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}
form button:hover {
    background: #0f6dbf;
}
.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: bold;
    text-align: center;
}
.message.error {
    background: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
}
.message.success {
    background: #e6ffea;
    color: #138d02;
    border: 1px solid #138d02;
}
</style>