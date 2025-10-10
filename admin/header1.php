<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f1f3f6; /* Slightly lighter background for contrast */
}

/* Navbar styling */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background:#244157ff;
    color: white;
    padding: 15px 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.navbar-left .brand {
    font-weight: bold;
    font-size: 22px;
}

.navbar-right a {
    color: white;
    text-decoration: none;
    margin-left: 25px;
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
    transition: background 0.3s, opacity 0.3s;
}

.navbar-right a:hover {
    background: rgba(255,255,255,0.2);
    opacity: 0.9;
}

/* Dashboard container */
.dashboard-container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

h1 {
    color: #141618ff;
    margin-bottom: 25px;
    font-size: 30px;
}

/* Stats list */
.stats ul {
    list-style-type: disc;  /* Show bullets */
    padding-left: 25px;
    margin: 20px 0;
}

.stats li {
    margin-bottom: 12px;    /* Space between items */
    font-weight: 600;
    font-size: 16px;
    color: #333;
}

/* Table styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
    font-size: 16px;
}

th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

th {
    background: #1690e8;
    color: white;
    font-weight: 600;
}

tr:nth-child(even) {
    background: #f9f9f9;
}

tr:hover {
    background: #e6f0fa; /* subtle hover effect */
}

</style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-left">
            <span class="brand">Chandusoft Admin</span>
        </div>
        <div class="navbar-right">
        <?php if (!empty($user['role'])): ?>
        <span style="margin-right:15px; font-weight:bold;">
            Welcome <?= htmlspecialchars($user['role']) ?>!
        </span>
        <?php endif; ?>

    <a href="/app/dashboard.php">Dashboard</a>
    <a href="/admin/admin-leads.php">Leads</a>
    <a href="/admin/pages.php">Pages</a>
    <a href="/admin/logout.php">Logout</a>
</div>
    </div>
    </body>
    </html>

  