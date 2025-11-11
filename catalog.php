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

        // ✅ Add or update in session cart
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

        // ✅ BUY NOW → Clear cart and add only this item
        if (isset($_POST['buy_now']) && $_POST['buy_now'] == 1) {
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
    $itemsPerPage = 8;
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
        log_info("Catalog search | Query: '{$search}' | Page: {$page}");
    } else {
        log_info("Catalog viewed | Page: {$page}");
    }

    $countSql = "SELECT COUNT(*) FROM catalog_items $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));

    $sql = "SELECT * FROM catalog_items $whereClause ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
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
h1{text-align:center;color:#244157;margin-bottom:20px;font-size:32px;}
.search-bar{text-align:center;margin-bottom:30px;}
.search-bar input[type="search"]{padding:10px;width:60%;max-width:400px;border:1px solid #ccc;border-radius:6px;}
.search-bar button{padding:10px 16px;background:#1690e8;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;}
.search-bar button:hover{background:#1175c2;}
main.catalog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:20px;}
.catalog-item{background:#fff;border-radius:10px;overflow:hidden;display:flex;flex-direction:column;min-height:380px;position:relative;}
.catalog-item picture, .catalog-item img{width:100%;height:200px;object-fit:cover;background:#f0f0f0;cursor:pointer;}
.catalog-details{padding:15px;display:flex;flex-direction:column;flex-grow:1;}
.catalog-title{font-size:20px;color:#1690e8;margin:0 0 8px;line-height:1.2;}
.catalog-price{font-weight:bold;font-size:17px;color:#244157;margin-bottom:10px;}
.catalog-buttons {display:flex;justify-content:space-between;gap:8px;margin-top:auto;}
.catalog-button {flex:1;text-align:center;padding:8px 10px;border-radius:6px;font-weight:600;font-size:13px;text-decoration:none;color:#fff;transition:all 0.2s ease;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;}
.add-to-cart-btn {background:#28a745;}
.add-to-cart-btn:hover {background:#218838;}
.buy-now-btn {background:#ff9800;}
.buy-now-btn:hover {background:#e68900;}
.catalog-button i {font-size:14px;}
@media (max-width:480px){.catalog-button{font-size:12px;padding:7px 8px;}}
.catalog-item.unpublished {opacity:0.6;}
.catalog-item.unpublished::after {
  content: "Unpublished";
  position: absolute;
  top: 10px;
  left: 10px;
  background: #d9534f;
  color: #fff;
  font-size: 12px;
  padding: 3px 6px;
  border-radius: 4px;
}
.pagination{text-align:center;margin:30px 0;}
.pagination a, .pagination span{display:inline-block;margin:0 5px;padding:8px 14px;border-radius:6px;text-decoration:none;font-weight:600;}
.pagination a{background:#1690e8;color:#fff;}
.pagination a:hover{background:#1175c2;}
.pagination span.current{background:#244157;color:#fff;}
</style>
</head>
<body>

<?php include("header.php"); ?>

<h1>Our Catalog</h1>

<div class="search-bar">
  <form method="get" action="catalog.php">
    <input type="search" name="q" placeholder="Search items..." 
           value="<?= htmlspecialchars($search) ?>" maxlength="200" />
    <button type="submit">Search</button>
  </form>
</div>

<main class="catalog-grid" id="catalogGrid">
<?php if (!$items): ?>
    <p class="no-items">No items found.</p>
<?php else: ?>
    <?php foreach ($items as $i => $item): 
        $slug = urlencode($item['slug']);
        $imagePath = $item['image_path'] ?? '';
        $loadingAttr = $i < 3 ? 'eager' : 'lazy';
        $isUnpublished = ($isAdmin && $item['status'] !== 'published');
    ?>
    <section class="catalog-item<?= $isUnpublished ? ' unpublished' : '' ?>">
        <a href="catalog-item?slug=<?= $slug ?>">
            <?php if ($imagePath && file_exists(__DIR__ . '/' . $imagePath)): ?>
                <img src="<?= htmlspecialchars($imagePath) ?>" 
                     alt="<?= htmlspecialchars($item['title']) ?>" 
                     loading="<?= $loadingAttr ?>" />
            <?php else: ?>
                <img src="https://via.placeholder.com/400x300?text=No+Image" 
                     alt="No image" loading="lazy" />
            <?php endif; ?>
        </a>

        <div class="catalog-details">
            <h2 class="catalog-title">
                <?= htmlspecialchars($item['title']) ?>
                <?php if ($isUnpublished): ?>
                    <span style="color:#d9534f;font-size:13px;">(<?= htmlspecialchars($item['status']) ?>)</span>
                <?php endif; ?>
            </h2>
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

<!-- 🔹 Pagination -->
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
document.querySelectorAll('.add-to-cart-btn, .buy-now-btn').forEach(btn => {
  btn.addEventListener('click', () => handleCartAction(btn, btn.classList.contains('buy-now-btn')));
});

function handleCartAction(btn, buyNow) {
  const id = btn.dataset.id;
  btn.disabled = true;
  const original = btn.innerHTML;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';

  const data = new URLSearchParams({ product_id: id, qty: 1 });
  if (buyNow) data.append('buy_now', 1);

  // ✅ FIXED: Always post to same page, whether URL is /catalog or /catalog.php
  fetch(window.location.pathname, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: data
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      btn.innerHTML = '<i class="fa fa-check"></i> Added!';
      setTimeout(() => {
        window.location.href = buyNow ? 'checkout.php' : 'cart.php';
      }, 400);
    } else {
      alert(data.message || 'Action failed.');
      btn.innerHTML = original;
      btn.disabled = false;
    }
  })
  .catch(() => {
    alert('Network error.');
    btn.innerHTML = original;
    btn.disabled = false;
  });
}
</script>


<?php include("footer.php"); ?>
</body>
</html>
