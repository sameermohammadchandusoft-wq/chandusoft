<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admins only.');
}

/* ============================================================
   AJAX Request Handler - Paginated Orders Fetch
============================================================ */
if (isset($_GET['ajax'])) {

    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $sqlBase = "FROM orders WHERE 1";
    $params = [];

    if ($search !== '') {
        $sqlBase .= " AND (order_ref LIKE ? OR customer_email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status !== '' && in_array($status, ['paid', 'pending', 'failed', 'awaiting_upi', 'cod_confirmed'])) {
        $sqlBase .= " AND LOWER(payment_status) = ?";
        $params[] = $status;
    }

    // Total Rows
    $countStmt = $pdo->prepare("SELECT COUNT(*) $sqlBase");
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    // Fetch Orders
    $stmt = $pdo->prepare("
        SELECT id, order_ref, customer_name, customer_email, total, payment_status, gateway, created_at
        $sqlBase
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* AJAX Output */
    ob_start();

    if (empty($orders)): ?>
        <div class="empty-block">No orders found.</div>

    <?php else: ?>
        <table class="mac-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order Ref</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total (₹)</th>
                    <th>Status</th>
                    <th>Gateway</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= $o['id'] ?></td>
                    <td><?= $o['order_ref'] ?></td>
                    <td><?= $o['customer_name'] ?></td>
                    <td><?= $o['customer_email'] ?></td>
                    <td><?= number_format($o['total'], 2) ?></td>

                    <td>
                        <span class="badge <?= strtolower($o['payment_status']) ?>">
                            <?= $o['payment_status'] ?>
                        </span>
                    </td>

                    <td><?= $o['gateway'] ?></td>
                    <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>

                    <td>
                        <a href="order-view.php?id=<?= $o['id'] ?>" class="view-btn">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="#" class="page-btn" data-page="<?= $page - 1 ?>">‹</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="#" class="page-btn <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="#" class="page-btn" data-page="<?= $page + 1 ?>">›</a>
            <?php endif; ?>
        </div>

    <?php endif;

    echo ob_get_clean();
    exit;
}

/* ============================================================
   COUNT FILTERS
============================================================ */
$countStmt = $pdo->query("
    SELECT LOWER(payment_status) AS status, COUNT(*) AS count
    FROM orders GROUP BY LOWER(payment_status)
");

$statuses = ['paid', 'pending', 'failed', 'awaiting_upi', 'cod_confirmed'];
$counts = array_fill_keys($statuses, 0);
$totalCount = 0;

foreach ($countStmt as $row) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = $row['count'];
        $totalCount += $row['count'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Orders - Chandusoft</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* MACOS PRO ADMIN STYLE UI */
body {
    background:#f1f2f4;
    margin:0;
    padding:0;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", Arial, sans-serif;
}
/* Better header spacing (keeps navbar visible and clean) */
.page-header {
    margin-top: 20px;
    margin-bottom: 25px;
    text-align: left;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1c1c1e;
    display: flex;
    align-items: center;
    gap: 10px;
}


.container {
    max-width: 1200px;
    margin: 35px auto;
    padding: 30px;
    background: #ffffffdd;
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
}

/* Title */
h1 {
    margin:0 0 20px 0;
    font-size: 26px;
    font-weight: 600;
    color: #1c1c1e;
    text-align: center;
}

/* -------- FILTER BAR ---------- */
.top-bar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.filters a {
    padding:7px 16px;
    border-radius:10px;
    background:#f2f3f5;
    text-decoration:none;
    color:#444;
    margin-right:8px;
    border:1px solid #ddd;
    font-size:14px;
    transition:0.2s;
}

.filters a:hover { background:#e6e7e8; }
.filters a.active {
    background:#007aff;
    color:white;
    border-color:#007aff;
}

.badge-count {
    background:#444;
    padding:3px 7px;
    border-radius:10px;
    color:white;
    margin-left:4px;
    font-size:11px;
}

/* Dropdown */
#quickStatus {
    padding:8px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    background:white;
    outline:none;
}

/* -------- SEARCH -------- */
.search input {
    padding:9px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    width:260px;
    font-size:14px;
    outline:none;
}
.search button {
    padding:9px 14px;
    background:#007aff;
    border:none;
    color:white;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
}

/* -------- TABLE -------- */
.mac-table {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:14px;
    overflow:hidden;
}

.mac-table thead {
    background:#f6f7f8;
}

.mac-table th {
    padding:12px;
    text-align:left;
    font-size:13px;
    color:#555;
    border-bottom:1px solid #e3e3e3;
}

.mac-table td {
    padding:12px;
    font-size:14px;
    border-bottom:1px solid #f1f1f1;
}

.mac-table tr:hover {
    background:#f9f9f9;
}

/* Status badges */
.badge {
    padding:5px 12px;
    border-radius:20px;
    color:white;
    font-size:12px;
    font-weight:600;
}
.badge.paid { background:#34c759; }
.badge.pending { background:#f0a500; }
.badge.failed { background:#ff453a; }
.badge.awaiting_upi { background:#af52de; }
.badge.cod_confirmed { background:#0a84ff; }

/* View Button */
.view-btn {
    padding:7px 14px;
    border-radius:10px;
    background:#007aff;
    color:white;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    transition:0.2s;
}
.view-btn:hover { background:#005ecb; }

/* Empty */
.empty-block {
    padding:20px;
    text-align:center;
    font-size:16px;
    color:#666;
}

/* -------- PAGINATION -------- */
.pagination {
    margin-top:18px;
    text-align:center;
}
.page-btn {
    padding:8px 14px;
    margin:3px;
    background:white;
    border:1px solid #ccc;
    border-radius:10px;
    text-decoration:none;
    color:#333;
    font-size:14px;
}
.page-btn:hover { background:#007aff; color:white; }
.page-btn.active {
    background:#007aff;
    color:white;
    border-color:#007aff;
}
</style>
</head>

<body>
<div class="container">

    <div class="page-header">
    <h1><i class="fa fa-box"></i> Orders</h1>
</div>


    <div class="top-bar">

        <div class="filters">
            <a href="#" data-status="" class="active">
                All <span class="badge-count"><?= $totalCount ?></span>
            </a>

            <select id="quickStatus">
                <option value="">Quick Filters</option>
                <option value="paid">Paid (<?= $counts['paid'] ?>)</option>
                <option value="pending">Pending (<?= $counts['pending'] ?>)</option>
                <option value="failed">Failed (<?= $counts['failed'] ?>)</option>
                <option value="awaiting_upi">Awaiting UPI (<?= $counts['awaiting_upi'] ?>)</option>
                <option value="cod_confirmed">COD Confirmed (<?= $counts['cod_confirmed'] ?>)</option>
            </select>
        </div>

        <div class="search">
            <form id="searchForm">
                <input type="text" name="search" placeholder="Search Email or Order Ref">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </div>

    <div id="orderTable"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const orderTable = document.getElementById("orderTable");
    const filters = document.querySelectorAll(".filters a");
    const dropdown = document.getElementById("quickStatus");
    const searchForm = document.getElementById("searchForm");

    let currentStatus = "";
    let currentSearch = "";

    function loadOrders(page = 1) {
        fetch(`?ajax=1&page=${page}&status=${currentStatus}&search=${currentSearch}`)
            .then(res => res.text())
            .then(html => {
                orderTable.innerHTML = html;
                document.querySelectorAll(".page-btn").forEach(btn => {
                    btn.addEventListener("click", e => {
                        e.preventDefault();
                        loadOrders(btn.dataset.page);
                    });
                });
            });
    }

    filters.forEach(link => {
        link.addEventListener("click", e => {
            e.preventDefault();
            filters.forEach(l => l.classList.remove("active"));
            link.classList.add("active");

            dropdown.value = "";
            currentStatus = link.dataset.status || "";
            loadOrders(1);
        });
    });

    dropdown.addEventListener("change", () => {
        currentStatus = dropdown.value;
        filters.forEach(l => l.classList.remove("active"));
        loadOrders(1);
    });

    searchForm.addEventListener("submit", e => {
        e.preventDefault();
        currentSearch = searchForm.search.value.trim();
        loadOrders(1);
    });

    loadOrders(1);
});
</script>

</body>
</html>
