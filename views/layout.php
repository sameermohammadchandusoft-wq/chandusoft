<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page['title'] ?? 'Home') ?></title>
    <link rel="stylesheet" href="Style.css">
</head>
<body>

    <!-- Header (navbar included here) -->

    <main>
        <?= $content ?>
    </main>
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
