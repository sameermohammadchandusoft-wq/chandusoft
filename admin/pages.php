<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();
require __DIR__ . '/../app/db.php';

$user = current_user();
require __DIR__ . '/header1.php'; // Admin Navbar

// ----------------------------------------------------
// Handle search and filter logic
// ----------------------------------------------------
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$whereClauses = [];
$params = [];

// Search by title or status
if (!empty($search)) {
    $whereClauses[] = "(title LIKE ? OR status LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

// Filter by status
if (!empty($filter) && in_array($filter, ['published', 'draft', 'archived'])) {
    $whereClauses[] = "status = ?";
    $params[] = $filter;
}

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// ----------------------------------------------------
// Fetch Counts for filters
// ----------------------------------------------------
$totalCount = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$publishedCount = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
$draftCount = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='draft'")->fetchColumn();
$archivedCount = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='archived'")->fetchColumn();

// ----------------------------------------------------
// Fetch pages list
// ----------------------------------------------------
if (!empty($params)) {
    $stmt = $pdo->prepare("SELECT * FROM pages $whereSQL ORDER BY updated_at DESC");
    $stmt->execute($params);
} else {
    $stmt = $pdo->query("SELECT * FROM pages ORDER BY updated_at DESC");
}
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ✅ Dashboard Container -->
<div class="dashboard-container">
    <h1>Pages</h1>

    <div class="top-bar">
        <div class="filters">
            <a href="pages.php">All (<?= $totalCount ?>)</a>
            <a href="pages.php?filter=published">Published (<?= $publishedCount ?>)</a>
            <a href="pages.php?filter=draft">Draft (<?= $draftCount ?>)</a>
            <a href="pages.php?filter=archived">Archived (<?= $archivedCount ?>)</a>
        </div>

        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search pages..." value="<?= htmlspecialchars($search) ?>">
            <input type="submit" value="Search">
            <?php if(!empty($filter)): ?>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <?php endif; ?>
        </form>

        <a class="create-btn" href="create">+ Create New Page</a>
    </div>

    <table>
        <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Updated</th>
            <th>Actions</th>
        </tr>

        <?php if(count($pages) > 0): ?>
            <?php foreach($pages as $page): ?>
                <tr>
                    <td><?= htmlspecialchars($page['title']) ?></td>
                    <td>
                        <a href="/<?= htmlspecialchars($page['slug']) ?>" target="_blank">
                            <?= htmlspecialchars($page['slug']) ?>
                        </a>
                    </td>
                    <td><?= ucfirst($page['status']) ?></td>
                    <td><?= htmlspecialchars($page['updated_at']) ?></td>
                    <td class="actions">
                        <a class="btn" href="edit?id=<?= $page['id'] ?>">Edit</a>

                        <?php if($user['role'] === 'admin'): ?>
                            <?php if($page['status'] !== 'archived'): ?>
                                <a class="btn2" href="archive?id=<?= $page['id'] ?>">Archive</a>
                            <?php endif; ?>
                            <a class="btn3 disabled" href="javascript:void(0)" onclick="return false;">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No pages found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- ✅ Embedded CSS -->
<style>
    .dashboard-container {
        padding: 20px;
        font-family: Arial, sans-serif;
    }

    .btn {
        background: #138d02ff;
        color: #fff;
        padding: 8px 16px;
        text-decoration: none;
        font-weight: bold;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .btn:hover { background: #0f7302; }

    .btn2 {
        background: #ff804dff;
        color: #fff;
        padding: 8px 16px;
        text-decoration: none;
        font-weight: bold;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .btn2:hover { background: #e76b3c; }

    .btn3.disabled {
        pointer-events: none;
        padding: 8px 16px;
        border-radius: 6px;
        opacity: 0.5;
        font-weight: bold;
        cursor: not-allowed;
        color: white;
        text-decoration: none;
        background-color: #c20922ff;
        border: 1px solid #ccc;
    }

    .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .top-bar .filters a {
        text-decoration: none;
        color: black;
        padding: 2px 4px;
        transition: all 0.2s ease;
    }

    .top-bar .filters a:hover {
        color: #1379ecff;
        text-decoration: underline;
        text-decoration-color: #1379ecff;
    }

    .search-form input[type="text"] {
        padding: 6px 10px;
        border-radius: 5px;
        border:1px solid #ccc;
    }

    .search-form input[type="submit"] {
        padding: 6px 12px;
        border-radius:5px;
        background:#1690e8;
        color:#fff;
        border:none;
        cursor:pointer;
    }

    .create-btn {
        background: #138d02ff;
        color: #fff;
        padding: 8px 16px;
        text-decoration: none;
        font-weight: bold;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .create-btn:hover { background: #0e78c1; }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    th, td {
        border:1px solid #ddd;
        padding:12px;
        text-align:left;
    }

    th { background:#1690e8; color:white; }
    tr:nth-child(even) { background:#f9f9f9; }
    tr:hover { background:#eef7ff; }
    .actions a { margin-right:8px; color: white; text-decoration:none; }
</style>
