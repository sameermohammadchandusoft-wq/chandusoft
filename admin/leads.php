<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../app/auth.php';
require_auth();

require __DIR__ . '/../app/db.php';
require __DIR__ . '/../admin/header1.php'; // Navbar

$user = current_user();

// ✅ Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Handle search
$search = $_GET['search'] ?? '';
$search_sql = "";
$params = [];

if (!empty($search)) {
    $search_sql = "WHERE Name LIKE ? OR Email LIKE ? OR Message LIKE ?";
    $likeSearch = "%$search%";
    $params = [$likeSearch, $likeSearch, $likeSearch];
}

// ✅ Prepare and execute query safely
if (!empty($search_sql)) {
    $stmt = $pdo->prepare("SELECT * FROM leads $search_sql ORDER BY created_at DESC");
    $stmt->execute($params);
} else {
    $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC");
}

$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leads - Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f3f6;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            max-width: 1100px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            padding: 20px 30px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        .search-form {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-form input[type="text"] {
            padding: 8px 10px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .search-form input[type="submit"] {
            padding: 8px 14px;
            background: #1690e8;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .search-form input[type="submit"]:hover {
            background: #0f6dbf;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background: #1690e8;
            color: white;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        table tr:hover {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Leads</h1>

        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search leads..." value="<?= htmlspecialchars($search) ?>">
            <input type="submit" value="Search">
        </form>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Submitted At</th>
            </tr>
            <?php if (!empty($leads)): ?>
                <?php foreach ($leads as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['Name']) ?></td>
                        <td><?= htmlspecialchars($row['Email']) ?></td>
                        <td><?= htmlspecialchars($row['Message']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No leads found</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
