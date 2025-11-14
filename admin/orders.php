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

// ✅ AJAX Orders with Pagination
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

    // Total rows
    $countStmt = $pdo->prepare("SELECT COUNT(*) $sqlBase");
    $countStmt->execute($params);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    // Fetch paginated orders
    $stmt = $pdo->prepare("
        SELECT id, order_ref, customer_name, customer_email, total, payment_status, gateway, created_at
        $sqlBase
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (empty($orders)): ?>
        <p class="no-orders">No orders found.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Order Ref</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Total (₹)</th>
                <th>Payment</th>
                <th>Gateway</th>
                <th>Date</th>
                <th>View</th>
            </tr>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= $o['id'] ?></td>
                <td><?= $o['order_ref'] ?></td>
                <td><?= $o['customer_name'] ?></td>
                <td><?= $o['customer_email'] ?></td>
                <td><?= number_format($o['total'], 2) ?></td>
                <td><span class="status <?= strtolower($o['payment_status']) ?>"><?= $o['payment_status'] ?></span></td>
                <td class="gateway"><?= $o['gateway'] ?></td>
                <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                <td><a href="order-view.php?id=<?= $o['id'] ?>" class="view-btn">View</a></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Pagination -->
        <div class="pagination" style="text-align:center;margin-top:18px;">
            <?php if ($page > 1): ?>
                <a href="#" class="page-btn" data-page="<?= $page-1 ?>">« Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="#" class="page-btn" data-page="<?= $i ?>" <?= $i == $page ? 'style="background:#007bff;color:#fff;"' : '' ?>>
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="#" class="page-btn" data-page="<?= $page+1 ?>">Next »</a>
            <?php endif; ?>
        </div>

    <?php endif;
    echo ob_get_clean();
    exit;
}

// Counts for Dashboard Filters
$countStmt = $pdo->query("
    SELECT LOWER(payment_status) AS status, COUNT(*) AS count
    FROM orders
    GROUP BY LOWER(payment_status)
");

$statuses = ['paid', 'pending', 'failed', 'awaiting_upi', 'cod_confirmed'];
$counts = array_fill_keys($statuses, 0);
$totalCount = 0;

foreach ($countStmt as $row) {
    $s = $row['status'];
    if (isset($counts[$s])) $counts[$s] = $row['count'];
    $totalCount += $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Orders - Chandusoft</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    border-radius: 10px;
    padding: 30px 40px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
}

h1 {
    text-align: center;
    margin-bottom: 25px;
    color: #222;
    font-size: 26px;
}

/* -------------------------
   TOP BAR
-------------------------- */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

/* -------------------------
   FILTER BUTTONS
-------------------------- */
.filters {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.filters a {
    padding: 8px 14px;
    border-radius: 6px;
    background: #eef1f5;
    margin: 4px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    transition: 0.2s;
    border: 1px solid #d5d8dd;
}

.filters a:hover {
    background: #dbe7ff;
}

.filters a.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.badge {
    padding: 3px 7px;
    border-radius: 8px;
    background: #555;
    color: #fff;
    margin-left: 4px;
    font-size: 11px;
    font-weight: bold;
}

/* -------------------------
   DROPDOWN
-------------------------- */
#quickStatus {
    padding: 8px 14px;
    border-radius: 6px;
    border: 1px solid #bbb;
    font-size: 14px;
    background: #fff;
    margin: 4px 10px;
    cursor: pointer;
    outline: none;
    transition: 0.2s;
}

#quickStatus:hover {
    border-color: #007bff;
}

/* -------------------------
   SEARCH BAR
-------------------------- */
.search input[type="text"] {
    padding: 9px 14px;
    width: 260px;
    border-radius: 6px;
    border: 1px solid #ccc;
    outline: none;
    font-size: 14px;
    transition: 0.2s;
}

.search input[type="text"]:focus {
    border-color: #007bff;
}

.search button {
    padding: 9px 14px;
    border-radius: 6px;
    border: none;
    background: #007bff;
    color: #fff;
    cursor: pointer;
    font-size: 14px;
    margin-left: 6px;
    transition: 0.2s;
}

.search button:hover {
    background: #005ecb;
}

/* -------------------------
   TABLE
-------------------------- */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th {
    background: #f2f4f7;
    padding: 12px;
    font-size: 13px;
    color: #555;
    text-transform: uppercase;
    border-bottom: 2px solid #e6e6e6;
}

td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    text-align: center;
}

tr:hover {
    background: #f7faff;
}

.status {
    padding: 4px 10px;
    border-radius: 6px;
    color: #fff;
    font-size: 13px;
    text-transform: capitalize;
}

/* Payment color tags */
.status.paid { background: #27ae60; }
.status.pending { background: #f39c12; }
.status.failed { background: #e74c3c; }
.status.awaiting_upi { background: #9b59b6; }
.status.cod_confirmed { background: #16a085; }

.view-btn {
    background: #3498db;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
    color: #fff;
    transition: 0.2s;
}

.view-btn:hover {
    background: #2a7ebf;
}

/* -------------------------
   PAGINATION
-------------------------- */
.pagination a {
    padding: 8px 14px;
    margin: 3px;
    border: 1px solid #ccc;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
    transition: 0.2s;
}

.pagination a:hover {
    background: #007bff;
    color: #fff;
}

/* -------------------------
   NO ORDERS
-------------------------- */
.no-orders {
    text-align: center;
    color: #555;
    font-size: 16px;
    padding: 20px;
}

/* -------------------------
   RESPONSIVE FIXES
-------------------------- */
@media(max-width: 768px) {
    .top-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .search input {
        width: 100%;
        margin-bottom: 10px;
    }
}

</style>
</head>

<body>
<div class="container">
    <h1><i class="fa fa-box"></i> Admin Orders</h1>

    <div class="top-bar">
        <div class="filters">

            <!-- ALL -->
            <a href="#" data-status="" class="active">
                All <span class="badge"><?= $totalCount ?></span>
            </a>

            <!-- Dropdown for Paid + Pending -->
            <select id="quickStatus" style="
                padding: 7px 12px;
                border-radius: 5px;
                border: 1px solid #aaa;
                font-size: 15px;
                margin-left: 10px;
            ">
                <option value="">-- Quick Filters --</option>
                <option value="paid">Paid (<?= $counts['paid'] ?>)</option>
                <option value="pending">Pending (<?= $counts['pending'] ?>)</option>
                <option value="failed">Paid (<?= $counts['failed'] ?>)</option>
                <option value="awaiting_upi">Pending (<?= $counts['awaiting_upi'] ?>)</option>
                <option value="cod_confirmed">Pending (<?= $counts['pendcod_confirmeding'] ?>)</option>

            </select>

        <div class="search">
            <form id="searchForm">
                <input type="text" name="search" placeholder="Search by Email or Order ID">
                <button type="submit"><i class="fa fa-search"></i> Search</button>
            </form>
        </div>
    </div>

    <div id="orderTable"></div>

    <div style="text-align:center;margin-top:20px;">
        <a href="/app/dashboard.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const orderTable = document.getElementById('orderTable');
    const filters = document.querySelectorAll('.filters a');
    const quickStatus = document.getElementById('quickStatus');
    const searchForm = document.getElementById('searchForm');

    let currentStatus = "";
    let currentSearch = "";

    function loadOrders(page = 1) {
        const url = `?ajax=1&page=${page}&status=${encodeURIComponent(currentStatus)}&search=${encodeURIComponent(currentSearch)}`;
        fetch(url).then(res => res.text()).then(html => {
            orderTable.innerHTML = html;

            document.querySelectorAll('.page-btn').forEach(btn => {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    loadOrders(btn.dataset.page);
                });
            });
        });
    }

    // Button filters
    filters.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();

            filters.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            quickStatus.value = ""; // reset dropdown

            currentStatus = link.dataset.status;
            loadOrders(1);
        });
    });

    // Dropdown filter
    quickStatus.addEventListener('change', () => {
        currentStatus = quickStatus.value;

        filters.forEach(l => l.classList.remove('active')); // remove active from buttons

        loadOrders(1);
    });

    // Search
    searchForm.addEventListener('submit', e => {
        e.preventDefault();
        currentSearch = searchForm.search.value.trim();
        loadOrders(1);
    });

    loadOrders(1);
});
</script>

</body>
</html>
