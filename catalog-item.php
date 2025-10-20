<?php
require __DIR__ . '/app/db.php';

// Get the slug from the URL
$slug = $_GET['slug'] ?? '';

// Fetch the product using the slug
$stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo "Product not found.";
    exit;
}

// Use the correct database column names for the product details
$name = htmlspecialchars($product['title']);  // Assuming 'title' is the column name
$desc = htmlspecialchars($product['short_desc']);  // Assuming 'short_desc' is the column name
$price = number_format($product['price'], 2);

// Use the correct image path (web accessible path)
$imagePath = htmlspecialchars($product['image_path']);  // Directly use the path stored in the database
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $name ?> | Chandusoft Catalog</title>
    <meta name="description" content="<?= substr(strip_tags($desc), 0, 150) ?>">

    <!-- ✅ JSON-LD Product Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "<?= $name ?>",
        "image": "<?= $imagePath ?>",
        "description": "<?= addslashes($desc) ?>",
        "brand": { "@type": "Brand", "name": "Chandusoft" },
        "offers": {
            "@type": "Offer",
            "url": "https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>",
            "priceCurrency": "INR",
            "price": "<?= $product['price'] ?>",
            "availability": "https://schema.org/InStock"
        }
    }
    </script>

    <link rel="stylesheet" href="Style.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>

<div class="container">
    <div class="product-detail">
        <!-- Display product image with placeholder if not available -->
        <img src="<?= !empty($product['image_path']) ? $imagePath : 'https://via.placeholder.com/400x400?text=No+Image' ?>" 
            alt="<?= $name ?>" 
            class="product-image" 
            loading="lazy">

        <div class="product-info">
            <h1><?= $name ?></h1>
            <p class="price">₹<?= $price ?></p>
            <p><?= nl2br($desc) ?></p>
        </div>
    </div>

    <!-- ✅ Enquiry Form -->
    <div class="enquiry-form">
        <h2>Enquire about this product</h2>
        <form method="POST" action="/app/submit-enquiry.php">
    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

    <div class="form-group">
        <label>Name:</label>
        <input type="text" name="name" required>
    </div>
    <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" required>
    </div>
    <div class="form-group">
        <label>Message:</label>
        <textarea name="message" rows="4" required></textarea>
    </div>

    <!-- ✅ Cloudflare Turnstile -->
    <div class="cf-turnstile"
         data-sitekey="0x4AAAAAAB7ii-4RV0QMh131"
         data-theme="light"></div>
    <button type="submit">Submit Enquiry</button>
</form>
        <div id="formMsg" style="margin-top:10px;"></div>
    </div>
</div>
<script>
document.getElementById('enquiryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
// Get the Turnstile response
    const turnstileResponse = document.querySelector('.cf-turnstile').getAttribute('data-response');

    // If no response, show an error message
    if (!turnstileResponse) {
        alert('Please complete the Turnstile verification.');
        return;
    }
    const form = e.target;
    const msg = document.getElementById('formMsg');
    msg.innerHTML = '⏳ Sending...';

    const formData = new FormData(form);
 // Append the Turnstile response to the form data
    formData.append('cf-turnstile-response', turnstileResponse);
    try {
        const res = await fetch('/app/submit-enquiry.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            msg.style.color = 'green';
            msg.innerHTML = '✅ Enquiry submitted successfully!';
            form.reset();
            turnstile.reset(); // reset Turnstile widget
        } else {
            msg.style.color = 'red';
            msg.innerHTML = '❌ ' + (data.error || 'Something went wrong.');
            turnstile.reset();
        }
    } catch (err) {
        msg.style.color = 'red';
        msg.innerHTML = '❌ Network error. Please try again.';
        turnstile.reset();
    }
});
</script>

</body>
</html>