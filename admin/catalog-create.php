<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();
?>

<?php include __DIR__ . '/header1.php'; ?>

<div class="dashboard-container">
    <h1>Create New Catalog Item</h1>

    <?php if (!empty($_GET['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php
    // Add image upload validation (file size and type)
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Collect form data
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $price = $_POST['price'] ?? 0;
        $short_desc = trim($_POST['short_desc'] ?? '');
        $status = $_POST['status'] ?? 'draft';

        // File upload validation
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileSize = $_FILES['image']['size'];
            $fileType = $_FILES['image']['type'];

            // Allowed image types
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            // Check file type
            if (!in_array($fileType, $allowedTypes)) {
                $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
            } 
            // Check file size (max 2MB)
            elseif ($fileSize > 2 * 1024 * 1024) {
                $error = "Image must be smaller than 2MB.";
            }

            // If no errors, process image upload
            if (!$error) {
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/catalog/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Add leading slash here to make path root-relative
                    $image_path = '/uploads/catalog/' . $newFileName;
                } else {
                    $error = "Failed to move uploaded image.";
                }
            }
        }
    }

    // Handle error or success
    if ($error) {
        echo "<div class='message error'>{$error}</div>";
    }
    ?>

    <form method="POST" action="catalog-store.php" enctype="multipart/form-data">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title" required>

        <label for="slug">Slug (optional)</label>
        <input type="text" id="slug" name="slug" placeholder="Auto-generated if empty">

        <label for="price">Price *</label>
        <input type="number" id="price" name="price" step="0.01" required>

        <label for="image_path">Upload Image (Max 2MB)</label>
        <input type="file" id="image_path" name="image" accept="image/*">

        <label for="short_desc">Short Description</label>
        <textarea id="short_desc" name="short_desc" rows="4"></textarea>

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
        </select>

        <button type="submit">Create Item</button>
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
form label {
    font-weight: bold;
    margin-top: 15px;
    display: block;
}
form input[type="text"],
form input[type="number"],
form textarea,
form select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    font-size: 14px;
    border-radius: 5px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
form textarea {
    resize: vertical;
}
form button {
    margin-top: 20px;
    padding: 12px;
    background: #1690e8;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
form button:hover {
    background: #0f6dbf;
}
.message.error {
    background: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}
</style>