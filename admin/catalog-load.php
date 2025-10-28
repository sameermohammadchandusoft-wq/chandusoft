<?php
require __DIR__ . '/../app/db.php';

// PDO safe setup
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Pagination
$itemsPerPage = 8;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Search
$search = trim($_GET['q'] ?? '');
$whereClause = "WHERE status='published'";
$params = [];

if ($search !== '') {
    $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
    $whereClause .= " AND (title LIKE :q OR short_desc LIKE :q)";
    $params[':q'] = "%{$safeSearch}%";
}

// Fetch items
$sql = "SELECT * FROM catalog_items 
        $whereClause 
        ORDER BY updated_at DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Bind search parameters
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stop if no items
if (!$items) exit;

// Render HTML
foreach ($items as $i => $item):
    $imagePath = $item['image_path'] ?? '';
    if (!empty($imagePath)) {
        $dir = pathinfo($imagePath, PATHINFO_DIRNAME);
        $filename = pathinfo($imagePath, PATHINFO_FILENAME);

        $src400 = $dir . '/' . $filename . '-400.jpg';
        $webp400 = $dir . '/' . $filename . '-400.webp';
        $absSrc400 = __DIR__ . '/../' . $src400;
        $absWebp400 = __DIR__ . '/../' . $webp400;

        $loadingAttr = $i < 3 ? 'eager' : 'lazy';
    }
?>
<section class="catalog-item">
    <?php if (!empty($imagePath)): ?>
        <picture>
            <?php if (file_exists($absWebp400)): ?>
                <source srcset="<?= htmlspecialchars($webp400) ?>" type="image/webp">
            <?php endif; ?>
            <img src="<?= file_exists($absSrc400) ? htmlspecialchars($src400) : htmlspecialchars($imagePath) ?>"
                 alt="<?= htmlspecialchars($item['title']) ?>"
                 width="400" height="300"
                 loading="<?= $loadingAttr ?>"
                 style="object-fit: cover; border-radius: 10px; width:100%; height:200px;">
        </picture>
    <?php else: ?>
        <img src="https://via.placeholder.com/400x300?text=No+Image"
             alt="No image"
             width="400" height="300"
             loading="lazy"
             style="object-fit: cover; border-radius:10px; width:100%; height:200px;">
    <?php endif; ?>

    <div class="catalog-details">
        <h2 class="catalog-title"><?= htmlspecialchars($item['title']) ?></h2>
        <div class="catalog-price">$<?= number_format($item['price'],2) ?></div>
        <div class="catalog-buttons">
            <a href="../catalog-item?slug=<?= urlencode($item['slug']) ?>" class="catalog-button">View Details</a>
        </div>
    </div>
</section>
<?php endforeach; ?>
