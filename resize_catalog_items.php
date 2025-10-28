<?php
require __DIR__ . '/app/db.php'; // optional, if you want to fetch paths from DB

// Target width for catalog grid
$targetWidth = 270;
$quality = 80; // JPEG/WebP quality

// Example: fetch all catalog image paths from DB
$stmt = $pdo->query("SELECT image_path FROM catalog_items WHERE status='published'");
$imagesFromDB = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($imagesFromDB as $relativePath) {
    if (empty($relativePath)) continue;

    // Convert relative path to real server path
    $imagePath = __DIR__ . str_replace('/..', '', $relativePath);

    if (!file_exists($imagePath)) {
        echo "Original image not found: $imagePath\n";
        continue;
    }

    $dir = pathinfo($imagePath, PATHINFO_DIRNAME);
    $filename = pathinfo($imagePath, PATHINFO_FILENAME);
    $ext = pathinfo($imagePath, PATHINFO_EXTENSION);

    // Resized file paths
    $resizedJpg = $dir . '/' . $filename . '-270.jpg';
    $resizedWebp = $dir . '/' . $filename . '-270.webp';

    // Skip if already resized
    if (file_exists($resizedJpg) && file_exists($resizedWebp)) {
        echo "Skipped (already resized): $filename\n";
        continue;
    }

    // Load original image
    $img = imagecreatefromjpeg($imagePath);
    if (!$img) {
        echo "Failed to load image: $imagePath\n";
        continue;
    }

    $origWidth = imagesx($img);
    $origHeight = imagesy($img);
    $targetHeight = intval($origHeight * ($targetWidth / $origWidth));

    // Create resized image
    $resized = imagecreatetruecolor($targetWidth, $targetHeight);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);

    // Save resized JPEG and WebP
    imagejpeg($resized, $resizedJpg, $quality);
    imagewebp($resized, $resizedWebp, $quality);

    imagedestroy($resized);
    imagedestroy($img);

    echo "Created: $resizedJpg and $resizedWebp\n";
}

echo "All catalog images processed.\n";