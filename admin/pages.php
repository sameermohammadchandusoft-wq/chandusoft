<?php
session_start();
require __DIR__ . '/../app/db.php';

// ------------------------------------------------------------
// Detect whether this is a frontend or admin request
// ------------------------------------------------------------
$is_admin = str_contains($_SERVER['REQUEST_URI'], '/admin/pages');

// ------------------------------------------------------------
// 🌐 FRONTEND DYNAMIC PAGE RENDER
// ------------------------------------------------------------
if (!$is_admin && isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        http_response_code(404);
        include __DIR__ . '/../404.php';
        exit;
    }

    include __DIR__ . '/../header.php';
    echo "<main class='page-container'>";
    echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
    echo "<div class='page-body'>" . $page['content'] . "</div>";
    echo "</main>";
    include __DIR__ . '/../footer.php';
    exit;
}

// ------------------------------------------------------------
// 🧭 ADMIN DASHBOARD
// ------------------------------------------------------------
require __DIR__ . '/../app/auth.php';
require_auth();
$user = current_user();
require __DIR__ . '/header1.php';

// Filters / Search
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(title LIKE ? OR status LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter) && in_array($filter, ['published', 'draft', 'archived'])) {
    $whereClauses[] = "status = ?";
    $params[] = $filter;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Counts
$totalCount = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$publishedCount = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
$draftCount = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='draft'")->fetchColumn();
$archivedCount = $pdo->query("SELECT COUNT(*) FROM pages WHERE status='archived'")->fetchColumn();

// Pagination
$limit = 10;
$pageNo = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($pageNo - 1) * $limit;

$countSQL = "SELECT COUNT(*) FROM pages $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch rows
$sql = "SELECT * FROM pages $whereSQL ORDER BY updated_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- UI START -->
<div class="mac-container">

    <div class="mac-header">
        <h1>Pages</h1>
        <a href="create" class="mac-btn-primary">+ New Page</a>
    </div>

    <!-- Filters & Search -->
    <div class="mac-toolbar">

        <div class="mac-filters">
            <a href="pages.php" class="mac-filter <?= empty($filter) ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
            <a href="pages.php?filter=published" class="mac-filter <?= $filter === 'published' ? 'active' : '' ?>">Published (<?= $publishedCount ?>)</a>
            <a href="pages.php?filter=draft" class="mac-filter <?= $filter === 'draft' ? 'active' : '' ?>">Draft (<?= $draftCount ?>)</a>
            <a href="pages.php?filter=archived" class="mac-filter <?= $filter === 'archived' ? 'active' : '' ?>">Archived (<?= $archivedCount ?>)</a>
        </div>

        <form class="mac-search" method="GET">
            <input type="text" name="search" placeholder="Search pages…" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
            <?php if (!empty($filter)): ?>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <?php endif; ?>
        </form>

    </div>

    <!-- Table -->
    <div class="mac-card">
        <table class="mac-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if (count($pages) > 0): ?>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?= htmlspecialchars($page['title']) ?></td>

                        <td>
                            <a class="mac-slug" href="/<?= htmlspecialchars($page['slug']) ?>" target="_blank">
                                <?= htmlspecialchars($page['slug']) ?>
                            </a>
                        </td>

                        <td><span class="mac-badge mac-<?= $page['status'] ?>"><?= ucfirst($page['status']) ?></span></td>
                        <td><?= $page['updated_at'] ?></td>

                        <td class="mac-actions">

                            <a class="mac-btn edit" href="edit?id=<?= $page['id'] ?>">Edit</a>

                            <?php if ($user['role'] === 'admin'): ?>
                                <?php if ($page['status'] === 'archived'): ?>
                                    <a class="mac-btn unarchive" href="unarchive?id=<?= $page['id'] ?>">Unarchive</a>
                                <?php else: ?>
                                    <a class="mac-btn archive" href="archive?id=<?= $page['id'] ?>">Archive</a>
                                <?php endif; ?>

                                <a class="mac-btn disabled">Delete</a>
                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="mac-empty">No pages found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mac-pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="mac-page <?= ($i == $pageNo) ? 'active' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 🍏 MAC OS STYLE CSS -->
<style>
    body {
        background: #f1f2f4;
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", Arial, sans-serif;
    }

    .mac-container {
        padding: 30px;
    }

    .mac-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .mac-header h1 {
        font-size: 26px;
        font-weight: 600;
        color: #222;
    }

    .mac-btn-primary {
        background: #007aff;
        padding: 10px 18px;
        color: white;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        transition: 0.2s;
    }
    .mac-btn-primary:hover {
        background: #0062cc;
    }

    /* Toolbar */
    .mac-toolbar {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 18px;
    }

    /* Filters */
    .mac-filter {
        background: #ffffffcc;
        padding: 7px 14px;
        border-radius: 10px;
        text-decoration: none;
        margin-right: 6px;
        color: #444;
        border: 1px solid #ddd;
        backdrop-filter: blur(8px);
        transition: 0.3s;
    }
    .mac-filter:hover {
        background: #e6e7e8;
    }
    .mac-filter.active {
        background: #007aff;
        color: white;
        border-color: #007aff;
    }

    /* Search */
    .mac-search input {
        padding: 8px 14px;
        border-radius: 10px;
        border: 1px solid #ccc;
        outline: none;
    }
    .mac-search button {
        padding: 8px 15px;
        background: #007aff;
        border: none;
        border-radius: 10px;
        color: white;
        cursor: pointer;
        font-weight: 600;
    }

    /* Card */
    .mac-card {
        background: #ffffffdd;
        backdrop-filter: blur(12px);
        border-radius: 14px;
        padding: 0;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    /* Table */
    .mac-table {
        width: 100%;
        border-collapse: collapse;
    }

    .mac-table th {
        background: #f6f7f8;
        color: #333;
        font-weight: 600;
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid #e9e9e9;
    }

    .mac-table td {
        padding: 14px;
        border-bottom: 1px solid #f1f1f1;
        color: #444;
    }

    .mac-table tr:hover {
        background: #f9f9f9;
    }

    .mac-empty {
        text-align: center;
        padding: 20px;
        font-weight: 600;
        color: #777;
    }

    /* Status Badges */
    .mac-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }
    .mac-published { background:#e1f7e7; color:#1d883a; }
    .mac-draft { background:#fff7d8; color:#c99700; }
    .mac-archived { background:#fde5e5; color:#c44; }

    /* Actions */
    .mac-actions {
        display: flex;
        justify-content: center;
        gap: 6px;
    }

    .mac-btn {
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 14px;
        min-width: 90px;
        text-align:center;
        color:white;
        text-decoration:none;
        font-weight:600;
        transition:0.2s;
    }

    .mac-btn.edit { background:#34c759; }
    .mac-btn.edit:hover { background:#28a745; }

    .mac-btn.archive { background:#ff3b30; }
    .mac-btn.archive:hover { background:#d63027; }

    .mac-btn.unarchive { background:#ff9f0a; }
    .mac-btn.unarchive:hover { background:#e08900; }

    .mac-btn.disabled {
        background:#bfbfbf;
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Pagination */
    .mac-pagination {
        margin-top: 20px;
        text-align:center;
    }

    .mac-page {
        padding: 8px 14px;
        background:white;
        border:1px solid #ccc;
        border-radius:8px;
        margin: 0 4px;
        text-decoration:none;
        color:#333;
        transition:0.2s;
    }
    .mac-page:hover {
        background:#e6e7e8;
    }
    .mac-page.active {
        background:#007aff;
        color:white;
        border-color:#007aff;
    }
</style>
