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

        <!-- ✅ Pagination -->
        <div class="pagination" style="text-align:center;margin-top:18px;">
            <?php if ($page > 1): ?>
                <a href="#" class="page-btn" data-page="<?= $page-1 ?>" style="padding:6px 12px;margin:2px;border:1px solid #ccc;border-radius:4px;">« Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="#" class="page-btn" data-page="<?= $i ?>" style="padding:6px 10px;margin:2px;border:1px solid #ccc;border-radius:4px;<?= $i == $page ? 'background:#007bff;color:#fff;' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="#" class="page-btn" data-page="<?= $page+1 ?>" style="padding:6px 12px;margin:2px;border:1px solid #ccc;border-radius:4px;">Next »</a>
            <?php endif; ?>
        </div>

    <?php endif;
    echo ob_get_clean();
    exit;
}

// ✅ Counts for Dashboard Filters
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
    border-radius: 8px;
    padding: 25px 35px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #222;
}
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.filters {
    flex: 1;
    text-align: left;
}
.filters a {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 5px;
    background: #f0f2f5;
    color: #333;
    text-decoration: none;
    margin: 3px;
    font-weight: 500;
    position: relative;
}
.filters a.active {
    background: #007bff;
    color: #fff;
}
.filters a:hover {
    background: #007bff;
    color: white;
}
.badge {
    background: #ccc;
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    padding: 2px 7px;
    border-radius: 10px;
    margin-left: 6px;
}
.badge.paid { background: #27ae60; }
.badge.pending { background: #f39c12; }
.badge.failed { background: #e74c3c; }
.badge.awaiting_upi { background: #9b59b6; }
.badge.cod_confirmed { background: #16a085; }
.search {
    flex: 1;
    text-align: center;
}
input[type="text"] {
    width: 300px;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
}
button {
    padding: 8px 14px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 5px;
}
button:hover {
    background: #0056b3;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    text-align: center;
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
}
th {
    background: #f9fafb;
    text-transform: uppercase;
    font-size: 13px;
    color: #666;
}
tr:hover {
    background: #f2f6ff;
}
.status {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    color: white;
    text-transform: capitalize;
}
.pending { background: #f39c12; }
.paid { background: #27ae60; }
.failed { background: #e74c3c; }
.awaiting_upi { background: #9b59b6; }
.cod_confirmed { background: #16a085; }
.gateway {
    text-transform: uppercase;
    font-weight: 600;
    color: #555;
}
.no-orders {
    text-align: center;
    color: #777;
    margin-top: 25px;
    font-size: 16px;
}
.view-btn {
    background: #3498db;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
}
.view-btn:hover {
    background: #2c80b4;
}
.back-link {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    color: #333;
}
.back-link:hover {
    text-decoration: underline;
}
 
</style>

</head>
<body>
<div class="container">
    <h1><i class="fa fa-box"></i> Admin Orders</h1>

    <div class="top-bar">
        <div class="filters">
            <a href="#" data-status="" class="active">All 
                <span class="badge"><?= $totalCount ?></span>
            </a>
            <a href="#" data-status="paid">Paid <span class="badge paid"><?= $counts['paid'] ?></span></a>
            <a href="#" data-status="pending">Pending <span class="badge pending"><?= $counts['pending'] ?></span></a>
            <a href="#" data-status="failed">Failed <span class="badge failed"><?= $counts['failed'] ?></span></a>
            <a href="#" data-status="awaiting_upi">Awaiting UPI <span class="badge awaiting_upi"><?= $counts['awaiting_upi'] ?></span></a>
            <a href="#" data-status="cod_confirmed">COD Confirmed <span class="badge cod_confirmed"><?= $counts['cod_confirmed'] ?></span></a>
        </div>
        <div class="search">
            <form id="searchForm">
                <input type="text" name="search" placeholder="Search by Email or Order ID">
                <button type="submit"><i class="fa fa-search"></i> Search</button>
            </form>
        </div>
    </div>

    <div id="orderTable"></div>

    <div style="text-align:center;">
        <a href="/app/dashboard.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const orderTable = document.getElementById('orderTable');
    const filters = document.querySelectorAll('.filters a');
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

    filters.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            filters.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            currentStatus = link.dataset.status;
            loadOrders(1);
        });
    });

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
