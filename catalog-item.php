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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $name ?> | Chandusoft Catalog</title>
  <meta name="description" content="<?= substr(strip_tags($desc), 0, 150) ?>" />

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

  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

  <style>
    /* === Base Styles === */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "Poppins", Arial, sans-serif;
      background-color: #f7f9fc;
      color: #333;
      line-height: 1.6;
      padding: 20px;
    }

    /* === Container === */
    .container {
      max-width: 1100px;
      margin: 40px auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      padding: 30px;
    }

    /* === Back Button === */
    .back-btn {
      display: inline-block;
      margin-bottom: 25px;
      background: #1e90ff;
      color: #fff;
      padding: 10px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    .back-btn:hover {
      background: #1171c1;
      transform: translateY(-2px);
    }

    /* === Product Section === */
    .product-detail {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
      margin-bottom: 50px;
    }

    .product-image {
      flex: 1 1 380px;
      max-width: 480px;
      border-radius: 12px;
      object-fit: cover;
      width: 100%;
      height: auto;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .product-info {
      flex: 1 1 300px;
    }

    .product-info h1 {
      font-size: 2rem;
      color: #111;
      margin-bottom: 10px;
    }

    .price {
      font-size: 1.4rem;
      font-weight: 700;
      color: #1e90ff;
      margin-bottom: 20px;
    }

    .product-info p {
      font-size: 1rem;
      color: #444;
    }

    /* === Enquiry Form === */
    .enquiry-form {
      padding: 25px;
      border-top: 2px solid #eee;
    }

    .enquiry-form h2 {
      font-size: 1.5rem;
      color: #111;
      margin-bottom: 20px;
      position: relative;
    }

    .enquiry-form h2::after {
      content: "";
      display: block;
      width: 60px;
      height: 3px;
      background: #1e90ff;
      margin-top: 8px;
      border-radius: 3px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
      color: #222;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.2s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      border-color: #1e90ff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(30,144,255,0.15);
    }

    /* === Submit Button === */
    button[type="submit"] {
      background-color: #1e90ff;
      color: #fff;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    button[type="submit"]:hover {
      background-color: #1171c1;
      transform: translateY(-2px);
    }

    button[type="submit"]:active {
      transform: translateY(1px);
    }

    /* === Turnstile (CAPTCHA) === */
    .cf-turnstile {
      margin: 20px 0;
    }

    /* === Responsive === */
    @media (max-width: 768px) {
      .product-detail {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .product-info {
        margin-top: 20px;
      }

      .product-image {
        max-width: 90%;
      }

      .enquiry-form {
        padding: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <a href="catalog" class="back-btn">← Back to Catalog</a>

    <div class="product-detail">
      <img src="<?= !empty($product['image_path']) ? $imagePath : 'https://via.placeholder.com/400x400?text=No+Image' ?>"
           alt="<?= $name ?>"
           class="product-image"
           loading="lazy" />

      <div class="product-info">
        <h1><?= $name ?></h1>
        <p class="price">₹<?= $price ?></p>
        <p><?= nl2br($desc) ?></p>
      </div>
    </div>

    <div class="enquiry-form">
      <h2>Enquire about this product</h2>
      <form id="enquiryForm" method="POST" action="/app/submit-enquiry.php">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>" />

        <div class="form-group">
          <label>Name:</label>
          <input type="text" name="name" required />
        </div>

        <div class="form-group">
          <label>Email:</label>
          <input type="email" name="email" required />
        </div>

        <div class="form-group">
          <label>Message:</label>
          <textarea name="message" rows="4" required></textarea>
        </div>

        <!-- Cloudflare Turnstile CAPTCHA -->
        <div class="cf-turnstile"
             data-sitekey="0x4AAAAAAB7ii-4RV0QMh131"
             data-callback="onTurnstileSuccess"
             data-theme="light">
        </div>

        <button type="submit">Submit Enquiry</button>
      </form>

      <div id="formMsg" style="margin-top:10px;"></div>
    </div>
  </div>

  <script>
    let turnstileResponse = null;

    function onTurnstileSuccess(token) {
      turnstileResponse = token;
    }

    document.getElementById('enquiryForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      if (!turnstileResponse) {
        alert('Please complete the Turnstile verification.');
        return;
      }

      const form = e.target;
      const msg = document.getElementById('formMsg');
      msg.style.color = 'black';
      msg.textContent = '⏳ Sending...';

      const formData = new FormData(form);
      formData.append('cf-turnstile-response', turnstileResponse);

      try {
        const res = await fetch('/app/submit-enquiry.php', {
          method: 'POST',
          body: formData
        });

        const data = await res.json();

        if (data.success) {
          msg.style.color = 'green';
          msg.textContent = '✅ Enquiry submitted successfully!';
          form.reset();
          turnstileResponse = null;
          turnstile.reset();
        } else {
          msg.style.color = 'red';
          msg.textContent = '❌ ' + (data.error || 'Something went wrong.');
          turnstileResponse = null;
          turnstile.reset();
        }
      } catch (err) {
        msg.style.color = 'red';
        msg.textContent = '❌ Network error. Please try again.';
        turnstileResponse = null;
        turnstile.reset();
      }
    });
  </script>
</body>
</html>
