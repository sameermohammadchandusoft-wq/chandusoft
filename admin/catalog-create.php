<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
 
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';
 
$user = current_user();
$turnstile_sitekey = getenv('TURNSTILE_SITEKEY') ?: ''; // Optional site key
?>
<?php include __DIR__ . '/header1.php'; ?>
 
<div class="dashboard-container">
    <h1>Create New Catalog Item</h1>
 
    <?php if (!empty($_GET['error'])): ?>
        <div class="message error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="message success"><?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>
 
    <form method="POST" action="/admin/catalog-store" enctype="multipart/form-data" class="catalog-form">
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
 
        <!-- Show Turnstile widget only if sitekey is configured -->
        <?php if (!empty($turnstile_sitekey)): ?>
            <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstile_sitekey) ?>"></div>
        <?php endif; ?>
 
        <button type="submit">Create Item</button>
    </form>
</div>
 
<!-- Cloudflare Turnstile script -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
 
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
.catalog-form label {
    font-weight: bold;
    margin-top: 15px;
    display: block;
}
.catalog-form input[type="text"],
.catalog-form input[type="number"],
.catalog-form textarea,
.catalog-form select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    font-size: 14px;
    border-radius: 5px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}
.catalog-form textarea {
    resize: vertical;
}
.catalog-form button {
    margin-top: 25px;
    padding: 12px;
    background: #1690e8;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
.catalog-form button:hover {
    background: #0f6dbf;
}
.message {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}
.message.error {
    background: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
}
.message.success {
    background: #e6ffea;
    color: #007a00;
    border: 1px solid #00a000;
}
</style>