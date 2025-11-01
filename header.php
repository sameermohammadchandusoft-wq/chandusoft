<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/settings.php'; // ✅ For dynamic site settings

// Fetch dynamic site settings
$site_name = get_setting('site_name', '');
$site_logo = get_setting('site_logo', 'images/chandusoft_logo.png');
 

// Fetch published pages
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY id ASC");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentSlug = trim($requestUri, '/');

// Normalize home route
if ($currentSlug === '' || $currentSlug === 'index.php') {
    $currentSlug = 'index';
}
?>

<header class="navbar">
    <!-- ✅ Dynamic logo & site name -->
    <a href="/index" class="logo" title="<?= htmlspecialchars($site_name) ?>">
        <img src="<?= htmlspecialchars($site_logo) ?>" 
             alt="<?= htmlspecialchars($site_name) ?>" 
             width="350" height="100" 
             style="vertical-align:middle">
    </a>

    <div style="display:flex;flex-direction:column;justify-content:center;margin-left:10px;">
        <span style="font-size:22px;font-weight:bold;color:#333;"><?= htmlspecialchars($site_name) ?></span>
        <?php if (!empty($site_tagline)): ?>
            <span style="font-size:14px;color:#666;"><?= htmlspecialchars($site_tagline) ?></span>
        <?php endif; ?>
    </div>

 <nav>
    <!-- Static Pages -->
    <a href="/index.php" class="btn <?= ($currentSlug === 'index') ? 'active' : '' ?>">Home</a>
    <a href="/about.php" class="btn <?= ($currentSlug === 'about') ? 'active' : '' ?>">About</a>
    <a href="/service.php " class="btn <?= ($currentSlug === 'services') ? 'active' : '' ?>">Services</a>
    <a href="/catalog.php" class="btn <?= ($currentSlug === 'catalog') ? 'active' : '' ?>">Catalogs</a>
    <a href="/contact.php" class="btn <?= ($currentSlug === 'contact') ? 'active' : '' ?>">Contact</a>
    <!-- Dynamic Pages -->
    <?php foreach ($navPages as $p): ?>
        <a href="/<?= htmlspecialchars($p['slug']) ?>"
           class="btn <?= ($currentSlug === $p['slug']) ? 'active' : '' ?>">
           <?= htmlspecialchars($p['title']) ?>
        </a>
    <?php endforeach; ?>

    <!-- Contact + Auth -->
    

    <span class="auth-links <?= ($currentSlug === 'login' || $currentSlug === 'register') ? 'active' : '' ?>">
        <a href="/login" class="btn">Login</a>
        <span class="divider">/</span>
        <a href="/register" class="btn">Register</a>
    </span>
</nav>
</header>
