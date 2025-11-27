<?php
// =============================================================
// Chandusoft Catalog Page (Public + Admin + Add-to-Cart + Buy-Now)
// =============================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/logger.php';

// =============================================================
// 🛒 Handle Add-to-Cart or Buy-Now (AJAX)
// =============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $id  = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    try {
        $stmt = $pdo->prepare("SELECT id, title, price, image_path FROM catalog_items WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // ADD TO CART
        if (!isset($_POST['buy_now'])) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$id] = [
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'price' => $product['price'],
                    'image' => $product['image_path'],
                    'qty' => $qty
                ];
            }
        }

        // BUY NOW — clear cart and add only this item
        if (isset($_POST['buy_now'])) {
            $_SESSION['cart'] = [
                $id => [
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'price' => $product['price'],
                    'image' => $product['image_path'],
                    'qty' => 1
                ]
            ];
        }

        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        log_error("Cart action failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error.']);
        exit;
    }
}


// =============================================================
// 📦 Catalog Listing Logic
// =============================================================
try {
    $itemsPerPage = 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $itemsPerPage;
    $search = trim($_GET['q'] ?? '');

    $isAdmin = isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';

    $whereClause = $isAdmin ? "WHERE 1" : "WHERE status='published'";
    $params = [];

    if ($search !== '') {
        $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
        $whereClause .= " AND (title LIKE :q OR short_desc LIKE :q)";
        $params[':q'] = "%{$safeSearch}%";
    }

    // Count total items matching filter
    $countSql = "SELECT COUNT(*) FROM catalog_items $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();

    $totalPages = max(1, ceil($totalItems / $itemsPerPage));

    // Fetch paginated items
    $sql = "SELECT * FROM catalog_items $whereClause 
            ORDER BY updated_at DESC 
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    log_error("Catalog page error: " . $e->getMessage());
    $items = [];
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catalog | Chandusoft</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="Style.css" />

<style>
/* Title */
h1 {
    text-align:center;
    color:#244157;
    margin-bottom:20px;
    font-size:32px;
}

/* MAC-style item count bar */
.catalog-count-bar {
    text-align:center;
    font-size:17px;
    background:#ffffffdd;
    padding:12px 18px;
    margin:-10px auto 25px;
    border-radius:10px;
    max-width:330px;
    color:#1d2a44;
    font-weight:600;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
    border:1px solid #e6e6e6;
}

/* Search */
.search-bar{ text-align:center; margin-bottom:30px; }
.search-bar input[type="search"]{
    padding:10px; width:60%; max-width:400px;
    border:1px solid #ccc; border-radius:6px;
}
.search-bar button{
    padding:10px 16px; background:#1690e8;
    color:#fff; border:none; border-radius:6px;
    font-weight:600; cursor:pointer;
}

/* GRID */
main.catalog-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(270px,1fr));
    gap:20px;
}

/* Item Card */
.catalog-item{
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    min-height:380px;
}
.catalog-item img{
    width:100%; height:200px; object-fit:cover; background:#f0f0f0;
}
.catalog-details{ padding:15px; flex-grow:1; }
.catalog-title{ font-size:20px; color:#1690e8; margin:0 0 8px; }
.catalog-price{ font-weight:bold; font-size:17px; color:#244157; margin-bottom:10px; }

/* Buttons */
.catalog-buttons{
    display:flex; gap:8px; margin-top:auto;
}

.catalog-button{
    flex:1; text-align:center; padding:10px;
    border-radius:6px; font-weight:600;
    font-size:13px; border:none; cursor:pointer;
    color:#fff; display:flex; align-items:center;
    justify-content:center; gap:6px;
}

/* macOS green */
.add-to-cart-btn{ background:#34c759; }
.add-to-cart-btn:hover{ background:#28a745; }

/* macOS orange */
.buy-now-btn{ background:#ff9500; }
.buy-now-btn:hover{ background:#e68900; }

/* Pagination */
.pagination{text-align:center;margin:30px 0;}
.pagination a, .pagination span{
    padding:8px 14px; border-radius:6px;
    text-decoration:none; margin:0 5px;
    font-weight:600;
}
.pagination a{ background:#1690e8; color:#fff; }
.pagination span.current{ background:#244157; color:#fff; }
</style>
</head>
<body>

<!-- HEADER -->
<?php include("header.php"); ?>

<h1>Our Catalog</h1>

<?php
// Correct item display range
$startItem = ($page - 1) * $itemsPerPage + 1;
$endItem = min($page * $itemsPerPage, $totalItems);
?>
<div class="catalog-count-bar">
    Showing <strong><?= $startItem ?>–<?= $endItem ?></strong> of <strong><?= $totalItems ?></strong> items
</div>

<!-- Search -->
<div class="search-bar">
<form method="get" action="">
    <input type="search" name="q" placeholder="Search items..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>
</div>

<!-- CATALOG GRID -->
<main class="catalog-grid" id="catalogGrid">
<?php if (!$items): ?>
    <p class="no-items">No items found.</p>
<?php else: ?>
    <?php foreach ($items as $i => $item): ?>
    <section class="catalog-item">
        <a href="catalog-item?slug=<?= urlencode($item['slug']) ?>">
            <?php if ($item['image_path'] && file_exists(__DIR__ . '/' . $item['image_path'])): ?>
                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="">
            <?php else: ?>
                <img src="https://via.placeholder.com/400x300?text=No+Image" alt="">
            <?php endif; ?>
        </a>

        <div class="catalog-details">
            <h2 class="catalog-title"><?= htmlspecialchars($item['title']) ?></h2>
            <div class="catalog-price">₹<?= number_format($item['price'],2) ?></div>

            <div class="catalog-buttons">
                <button class="catalog-button add-to-cart-btn" data-id="<?= $item['id'] ?>">
                    <i class="fa fa-cart-plus"></i> Add to Cart
                </button>
                <button class="catalog-button buy-now-btn" data-id="<?= $item['id'] ?>">
                    <i class="fa fa-bolt"></i> Buy Now
                </button>
            </div>
        </div>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
</main>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <?php if ($i == $page): ?>
        <span class="current"><?= $i ?></span>
    <?php else: ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
    <?php endif; ?>
<?php endfor; ?>
</div>
<?php endif; ?>


<script>
// Add-to-cart / Buy-now AJAX
document.querySelectorAll('.add-to-cart-btn, .buy-now-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    const buyNow = btn.classList.contains('buy-now-btn');

    const form = new FormData();
    form.append('product_id', id);
    form.append('qty', 1);
    if (buyNow) form.append('buy_now', 1);

    fetch("", { method:"POST", body:form })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
            window.location.href = buyNow ? "checkout.php" : "cart.php";
        }
      });
  });
});
</script>

<!-- FOOTER -->
<?php include("footer.php"); ?>

</body>
</html>
