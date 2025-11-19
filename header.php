<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/settings.php';

// Fetch dynamic site settings
$site_name = get_setting('site_name', '');
$site_logo = get_setting('site_logo', 'images/chandusoft_logo.png');

// Fetch pages
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY id ASC");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cart count
$cart_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $c) {
        $cart_count += $c['qty'];
    }
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentSlug = trim($requestUri, '/');

if ($currentSlug === '' || $currentSlug === 'index.php') {
    $currentSlug = 'index';
}
?>
<header class="navbar">
    <a href="/index" class="logo" title="<?= htmlspecialchars($site_name) ?>">
        <img src="<?= htmlspecialchars($site_logo) ?>" 
             alt="<?= htmlspecialchars($site_name) ?>" 
             width="350" height="100">
    </a>

    <div style="display:flex;flex-direction:column;justify-content:center;margin-left:10px;">
        <span style="font-size:22px;font-weight:bold;color:#333;"><?= htmlspecialchars($site_name) ?></span>
    </div>

    <nav>

        <a href="/index.php" class="btn <?= ($currentSlug === 'index') ? 'active' : '' ?>">Home</a>
        <a href="/about.php" class="btn <?= ($currentSlug === 'about') ? 'active' : '' ?>">About</a>
        <a href="/service.php" class="btn <?= ($currentSlug === 'services') ? 'active' : '' ?>">Services</a>
        <a href="/catalog.php" class="btn <?= ($currentSlug === 'catalog') ? 'active' : '' ?>">Catalogs</a>
        <a href="/contact.php" class="btn <?= ($currentSlug === 'contact') ? 'active' : '' ?>">Contact</a>

        <?php foreach ($navPages as $p): ?>
            <a href="/<?= htmlspecialchars($p['slug']) ?>"
               class="btn <?= ($currentSlug === $p['slug']) ? 'active' : '' ?>">
               <?= htmlspecialchars($p['title']) ?>
            </a>
        <?php endforeach; ?>


        <!-- Login + Register -->
        <span class="auth-links">
            <a href="/login" class="btn">Login</a>
            
        </span>
        
      

        <!-- 🛒 CART ICON -->
        <a href="/cart.php" class="btn cart-btn" style="margin-left:10px; position:relative;">
            🛒
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

    </nav>
</header>

<style>
.cart-btn {
    font-weight: bold;
    display: inline-block;
}

.cart-badge {
    background: red;
    color: white;
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 50%;
    position: absolute;
    top: -6px;
    right: -6px;
}
</style>
