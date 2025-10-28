<?php 
require __DIR__ . '/app/db.php';  

// PDO safe setup
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Pagination setup
$itemsPerPage = 8;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page-1)*$itemsPerPage;

// --- SEARCH FEATURE --- //
$search = trim($_GET['q'] ?? '');
$whereClause = "WHERE status='published'";
$params = [];

if ($search !== '') {
    $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
    $whereClause .= " AND (title LIKE :q1 OR short_desc LIKE :q2)";
    $params[':q1'] = "%{$safeSearch}%";
    $params[':q2'] = "%{$safeSearch}%";
}

// --- Count total items --- //
$countSql = "SELECT COUNT(*) FROM catalog_items $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// --- Fetch items --- //
$sql = "SELECT * FROM catalog_items 
        $whereClause 
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
?>  

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="Style.css" />

<title>Catalog | Chandusoft</title>
<style>

h1{text-align:center;color:#244157;margin-bottom:20px;font-size:32px;}
.search-bar{text-align:center;margin-bottom:30px;}
.search-bar input[type="search"]{padding:10px;width:60%;max-width:400px;border:1px solid #ccc;border-radius:6px;}
.search-bar button{padding:10px 16px;background:#1690e8;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;}
.search-bar button:hover{background:#1175c2;}
main.catalog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:20px;}
.catalog-item{background:#fff;border-radius:10px;overflow:hidden;display:flex;flex-direction:column;min-height:380px;}
.catalog-item img,picture{width:100%;height:200px;object-fit:cover;background:#f0f0f0;}
.catalog-details{padding:15px;display:flex;flex-direction:column;flex-grow:1;}
.catalog-title{font-size:20px;color:#1690e8;margin:0 0 8px;line-height:1.2;}
.catalog-price{font-weight:bold;font-size:17px;color:#244157;margin-bottom:10px;}
.catalog-buttons{display:flex;justify-content:space-between;gap:10px;}
.catalog-button{flex:1;text-align:center;padding:10px 16px;border-radius:6px;font-weight:600;font-size:14px;text-decoration:none;color:#fff;transition:background 0.2s ease;background:#1690e8;}
.catalog-button:hover{background:#1175c2;}
.load-more-btn{display:block;margin:30px auto;padding:10px 20px;border:none;border-radius:6px;background:#1690e8;color:#fff;font-size:15px;cursor:pointer;}
.load-more-btn:hover{background:#1175c2;}
.no-items{text-align:center;margin-top:40px;color:#666;}
@media(max-width:600px){main.catalog-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
 <div id="header"></div>
  <?php include("header.php"); ?>
  <h1>Our Catalog</h1>

<!-- 🔍 Search Form -->
<div class="search-bar">
  <form id="searchForm" method="get" action="catalog">
    <input type="search" name="q" placeholder="Search items..." 
           value="<?= htmlspecialchars($search) ?>" maxlength="200" />
    <button type="submit">Search</button>
  </form>
</div>

<main class="catalog-grid" id="catalogGrid">
<?php if (!$items): ?>
    <p class="no-items">No items found.</p>
<?php else: ?>
    <?php foreach ($items as $i => $item): ?>
        <section class="catalog-item">
            <?php 
            $imagePath = $item['image_path'] ?? '';
            if (!empty($imagePath)): 
                $dir = pathinfo($imagePath, PATHINFO_DIRNAME);
                $filename = pathinfo($imagePath, PATHINFO_FILENAME);
                $src400 = $dir . '/' . $filename . '-400.jpg';
                $webp400 = $dir . '/' . $filename . '-400.webp';
                $absSrc400 = __DIR__ . $src400;
                $absWebp400 = __DIR__ . $webp400;
                $loadingAttr = $i < 3 ? 'eager' : 'lazy';
            ?>
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
                    <a href="catalog-item?slug=<?= urlencode($item['slug']) ?>" class="catalog-button">View Details</a>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
</main>

<?php if ($totalPages > 1): ?>
  <button id="loadMore" class="load-more-btn" 
          data-page="<?= $page ?>" 
          data-query="<?= htmlspecialchars($search) ?>">Load More</button>
<?php endif; ?>

<script defer>
document.getElementById('loadMore')?.addEventListener('click', function(){
    const btn = this;
    const page = parseInt(btn.getAttribute('data-page')) + 1;
    const q = encodeURIComponent(btn.getAttribute('data-query') || '');
    btn.disabled = true; btn.innerText='Loading...';

    fetch('catalog-load?page='+page+'&q='+q)
        .then(res=>res.text())
        .then(html=>{
            if(html.trim().length===0){ 
                btn.style.display='none'; 
            } else {
                document.getElementById('catalogGrid').insertAdjacentHTML('beforeend', html);
                btn.disabled=false; 
                btn.innerText='Load More'; 
                btn.setAttribute('data-page', page);
            }
        })
        .catch(err=>{
            console.error(err); 
            btn.disabled=false; 
            btn.innerText='Load More';
        });
});
</script>
<div id="footer"></div>
<?php include("footer.php"); ?>
</body>
</html>
