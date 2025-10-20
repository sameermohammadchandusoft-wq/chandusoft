<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();

// Only admins can edit
if ($user['role'] !== 'admin') {
    die("Access denied.");
}

// Get catalog item ID from URL
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid catalog item ID.");
}

// Fetch existing item
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Catalog item not found.");
}

$error = "";
$success = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $short_desc = trim($_POST['short_desc'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $image_path = $item['image_path'] ?? ''; // keep existing if no new upload

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
        } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB
            $error = "Image must be smaller than 2MB.";
        } else {
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/catalog/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Optional: delete old image to save space
                if (!empty($item['image_path']) && file_exists(__DIR__ . '/../' . ltrim($item['image_path'], '/'))) {
                    unlink(__DIR__ . '/../' . ltrim($item['image_path'], '/'));
                }
                

                // Add leading slash here to make path root-relative
                $image_path = '/uploads/catalog/' . $newFileName;
            } else {
                $error = "Failed to move uploaded image.";
            }
        }
    }

    if (empty($title)) {
        $error = "Title is required.";
    } elseif (empty($slug)) {
        $error = "Slug is required.";
    } else {
        // Check if slug is unique except current item
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalog_items WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Slug already exists, please choose another.";
        } else {
            if (!$error) {
                // Update catalog item
                $stmt = $pdo->prepare("UPDATE catalog_items SET title=?, slug=?, price=?, short_desc=?, image_path=?, status=?, updated_at=NOW() WHERE id=?");
                try {
                    $stmt->execute([$title, $slug, $price, $short_desc, $image_path, $status, $id]);
                    $success = "Catalog item updated successfully.";
                    // Refresh $item data
                    $stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE id = ?");
                    $stmt->execute([$id]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
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

    <!-- Added enctype for file upload -->
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