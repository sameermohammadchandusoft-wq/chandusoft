<?php
require __DIR__ . '/app/db.php';

// Fetch published catalog items, newest first
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE status = 'published' ORDER BY updated_at DESC");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f9f9f9;
        }
        h1 {
            color: #244157;
            text-align: center;
            margin-bottom: 30px;
        }
        .catalog-item {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .catalog-item img {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
            border-radius: 6px;
        }
        .catalog-details {
            flex: 1;
        }
        .catalog-title {
            font-size: 22px;
            margin: 0 0 10px 0;
            color: #1690e8;
        }
        .catalog-price {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .catalog-desc {
            font-size: 15px;
            color: #555;
        }
        .no-items {
            text-align: center;
            color: #888;
            font-size: 18px;
        }
        .catalog-item a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

<h1>Our Catalog</h1>

<?php if (!$items): ?>
    <p class="no-items">No items available at the moment. Please check back later.</p>
<?php else: ?>
    <?php foreach ($items as $item): ?>
        <div class="catalog-item">
            <!-- Check if image path is available, otherwise show placeholder -->
            <a href="catalog-item.php?slug=<?= urlencode($item['slug']) ?>">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/150?text=No+Image" alt="No image available">
                <?php endif; ?>
            </a>

            <div class="catalog-details">
                <h2 class="catalog-title"><?= htmlspecialchars($item['title']) ?></h2>
                <div class="catalog-price">$<?= number_format($item['price'], 2) ?></div>
                <p class="catalog-desc"><?= nl2br(htmlspecialchars($item['short_desc'])) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>