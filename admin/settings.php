<?php
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require_auth();

// Helper: get setting or empty
function get_setting_value($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?? '';
}

$site_name   = get_setting_value($pdo, 'site_name');
$site_tagline = get_setting_value($pdo, 'site_tagline');
$site_logo   = get_setting_value($pdo, 'site_logo');
$footer_text = get_setting_value($pdo, 'footer_text');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'site_name'   => trim($_POST['site_name']),
        'site_logo'   => trim($_POST['site_logo']),
    ];

    foreach ($data as $key => $value) {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    $success = "✅ Settings updated successfully!";
    extract($data); // refresh values on page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Settings</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f3f6;
    margin: 0;
    padding: 20px;
}
.container {
    max-width: 600px;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
h2 { margin-bottom: 20px; }
label { display: block; margin-top: 15px; font-weight: bold; }
input[type=text], textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
textarea { resize: vertical; }
button {
    margin-top: 20px;
    padding: 10px 20px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.message { margin-top: 15px; color: green; }
img.preview {
    margin-top: 10px;
    max-width: 150px;
    display: block;
    border: 1px solid #ddd;
    border-radius: 5px;
}
</style>
</head>
<body>
<div class="container">
    <h2>🛠 Site Settings</h2>

    <form method="post">
       <label>Site Name</label>
      <input type="text" name="site_name" value="<?= htmlspecialchars($site_name) ?>">

      <label>Logo URL</label>
      <input type="text" name="site_logo" value="<?= htmlspecialchars($site_logo) ?>">


        <button type="submit">Save Changes</button>
    </form>

    <?php if (!empty($success)) echo "<p class='message'>$success</p>"; ?>
</div>
</body>
</html>
