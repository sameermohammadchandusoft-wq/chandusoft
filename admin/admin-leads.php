<?php
session_start();
require __DIR__ . '/../app/auth.php';
require_auth();

$user = current_user();

// ✅ Restrict access based on role
if (!in_array($user['role'], ['admin', 'editor'])) {
    die("⛔ Access denied.");
}

// ✅ Database connection
$host = 'localhost';
$dbUser = 'root'; // Laragon default
$dbPass = '';     // Laragon default
$dbName = 'chandusoft';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Handle search
$search = $_GET['search'] ?? '';
$search_sql = "";
$params = [];

if (!empty($search)) {
    $search_sql = "WHERE Name LIKE ? OR Email LIKE ? OR Message LIKE ?";
    $likeSearch = "%$search%";
    $params = [$likeSearch, $likeSearch, $likeSearch];
}

// Prepare query
$stmt = $conn->prepare("SELECT * FROM leads $search_sql ORDER BY id DESC");
if (!empty($params)) {
    $stmt->bind_param("sss", ...$params);
}
$stmt->execute();
$resultLatest = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Leads - Admin Panel</title>
<div id="header1"></div>
<?php include __DIR__ . '/header1.php';

?>
<style>
 
 
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

input[type="text"] { padding:5px; font-size:14px; }
input[type="submit"] { padding:5px 10px; font-size:14px; cursor:pointer; }


</style>
</head>
<body>

<div class="dashboard-container">
<h1>Leads</h1>
<form method="get" class="search-form">
    <input type="text" name="search" placeholder="Search leads..." value="<?= htmlspecialchars($search) ?>">
    <input type="submit" value="Search">
</form>

<table>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Submitted At</th></tr>
<?php if($resultLatest->num_rows > 0): ?>
    <?php while($row = $resultLatest->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['Name']) ?></td>
        <td><?= htmlspecialchars($row['Email']) ?></td>
        <td><?= htmlspecialchars($row['Message']) ?></td>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="5" style="text-align:center;">No leads found</td></tr>
<?php endif; ?>
</table>

</body>
</html>
