<?php
require __DIR__ . '/../app/auth.php';
require_auth();

$user = current_user();

// ✅ Database connection
$host = '127.0.0.1';
$db   = 'chandusoft';
$dbUser = 'root';
$dbPass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = $success = "";

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    if (empty($title)) {
        $error = "Title is required.";
    } else {
        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title)));
        }

        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, status, updated_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$title, $slug, $status]);

        $success = "✅ Page created successfully!";
    }
}
?>
<style>
/* Container */
.dashboard-container {
    max-width: 700px;
    margin: 40px auto;
    padding: 25px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}

/* Headings */
.dashboard-container h1 {
    text-align: center;
    color: #244157;
    margin-bottom: 20px;
}

/* Form */
form {
    display: flex;
    flex-direction: column;
}

/* Labels */
form label {
    font-weight: bold;
    margin-top: 15px;
}

/* Inputs and textarea */
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

/* Textarea resize */
form textarea {
    resize: vertical;
}

/* Button */
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

/* Button hover */
form button:hover {
    background: #0f6dbf;
}

/* Messages */
.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-weight: bold;
}

/* Error message */
.message.error {
    background: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
}

/* Success message */
.message.success {
    background: #e6ffea;
    color: #138d02;
    border: 1px solid #138d02;
}
</style>


<?php include __DIR__ . '/../admin/header1.php'; ?>

<!-- ✅ Page Form -->
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
