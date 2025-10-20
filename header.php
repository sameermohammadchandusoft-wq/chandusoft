<?php
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/settings.php'; // ✅ For dynamic site settings

// Fetch dynamic site settings
$site_name = get_setting('site_name', '');
$site_logo = get_setting('site_logo', 'images/chandusoft_logo.png');
 

// Fetch published pages
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY id ASC");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// Determine current page/slug
$currentFile = basename($_SERVER['PHP_SELF']); // e.g., index.php, about.php, router.php
$currentSlug = ($currentFile === 'router.php') ? basename($_SERVER['REQUEST_URI']) : '';
$currentSlug = explode('?', $currentSlug)[0];
?>

<header class="navbar">
    <!-- ✅ Dynamic logo & site name -->
    <a href="index.php" class="logo" title="<?= htmlspecialchars($site_name) ?>">
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
        <a href="index.php" class="btn <?= ($currentFile==='index.php') ? 'active' : '' ?>">Home</a>
        <a href="about.php" class="btn <?= ($currentFile==='about.php') ? 'active' : '' ?>">About</a>
        <a href="services.php" class="btn <?= ($currentFile==='services.php') ? 'active' : '' ?>">Services</a>

        <!-- Dynamic Pages (from DB) -->
        <?php foreach ($navPages as $p): ?>
            <a href="<?= htmlspecialchars($p['slug']) ?>"
               class="btn <?= ($currentFile==='router.php' && $currentSlug===$p['slug']) ? 'active' : '' ?>">
                <?= htmlspecialchars($p['title']) ?>
            </a>
        <?php endforeach; ?>

        <!-- Contact -->
        <a href="contact.php" class="btn <?= ($currentFile==='contact.php') ? 'active' : '' ?>">Contact</a>
    </nav>
</header>
