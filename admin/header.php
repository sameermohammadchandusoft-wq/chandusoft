
<?php
require __DIR__ . '/../app/db.php'; // ✅ Database connection

// ✅ Fetch published pages for navbar
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY id ASC");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<header class="navbar">
    <a href="index.php" class="logo">
        <img src="images/chandusoft_logo.png" title="Chandusoft Technologies" width="350" height="100" style="vertical-align:middle">
    </a>
    <nav>
        <a href="index.php" class="btn">Home</a>

        <?php foreach ($navPages as $p): ?>
            <a href="index.php?page=<?= htmlspecialchars($p['slug']) ?>" class="btn">
                <?= htmlspecialchars($p['title']) ?>
            </a>
        <?php endforeach; ?>

        <a href="contact.php" class="btn">Contact</a>
    </nav>
</header>