<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();

// ✅ Get page by ID
$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid page ID.");
}

$stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    die("Page not found.");
}

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = trim($_POST['status'] ?? 'draft');

    if (empty($title) || empty($slug)) {
        $error = "Title and slug are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE pages SET title=?, slug=?, content=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$title, $slug, $content, $status, $id]);
        header("Location: pages.php");
        exit;
    }
}
?>

<?php include __DIR__ . '/../admin/header1.php'; ?>

<div class="dashboard-container">
    <h1>Edit Page</h1>

    <?php if (!empty($error)): ?>
        <p class="message error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>">

        <label>Slug</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>">

        <label>Content</label>
        <textarea name="content" rows="6"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>

        <label>Status</label>
        <select name="status">
            <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="archived" <?= ($page['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>

        <button type="submit">Update Page</button>
    </form>
</div>

<style>
/* Dashboard container */
.dashboard-container {
    max-width: 700px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}

/* Heading */
.dashboard-container h1 {
    text-align: center;
    color: #244157;
    margin-bottom: 25px;
    font-size: 28px;
}

/* Labels */
form label {
    font-weight: bold;
    display: block;
    margin-top: 15px;
    color: #333;
}

/* Inputs, textarea, select */
form input[type="text"],
form textarea,
form select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

/* Textarea resize */
form textarea {
    resize: vertical;
}

/* Button */
form button {
    margin-top: 20px;
    padding: 12px 20px;
    background: #1690e8;
    color: #fff;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 16px;
}

/* Button hover */
form button:hover {
    background: #0f6dbf;
}

/* Error messages */
.message.error {
    padding: 12px;
    background: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
    border-radius: 6px;
    font-weight: bold;
    margin-bottom: 15px;
    text-align: center;
}
</style>
