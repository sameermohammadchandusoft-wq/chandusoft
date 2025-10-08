<?php
require __DIR__ . '/../app/auth.php';
require_auth();

$user = current_user();

// ✅ Database connection
require __DIR__ . '/../app/db.php'; // use centralized DB connection

$error = $success = "";

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $content_html = trim($_POST['content_html'] ?? '');

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title)));
        }

        // ✅ Check for duplicate slug
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            // Append a unique suffix if slug already exists
            $slug .= '-' . time();
        }

        // ✅ Insert into DB
        try {
            $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content_html, status, updated_at)
                                   VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $slug, $content_html, $status]);
            $success = "✅ Page created successfully!";
        } catch (PDOException $e) {
            $error = "❌ Database insert failed: " . $e->getMessage();
        }
    }
}
?>
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

<?php include __DIR__ . '/../admin/header1.php'; ?>

<div class="dashboard-container">
    <h1>Create New Page</h1>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="title">Page Title *</label>
        <input type="text" name="title" id="title" required>

        <label for="slug">Slug (optional)</label>
        <input type="text" name="slug" id="slug" placeholder="auto-generated-if-empty">

        <label for="content_html">Page Content (HTML allowed)</label>
        <textarea name="content_html" id="content_html" placeholder="Write your page content here..."></textarea>

        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
        </select>

        <button type="submit">Create Page</button>
    </form>
</div>

</body>
</html>