<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Chandusoft</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="Style.css" />
</head>

<body>
  <!-- ✅ Header -->
  
    <?php include "header.php"; ?>

  <?php
  require __DIR__ . '/app/db.php';

  // -----------------------------
  // Detect current page / slug
  // -----------------------------
  $uri = strtok($_SERVER['REQUEST_URI'], '?');
  $uri = trim($uri, '/');
  $pageSlug = $uri ?: 'home'; // default = home

$excludedSlugs = ['register', 'login', 'logout', 'about', 'services', 'contact'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$uri = trim($uri, '/');

if (in_array($uri, $excludedSlugs)) {
    return; // stop index.php from hijacking static/auth pages
}

  // -----------------------------
  // 1️⃣ Home Page
  // -----------------------------
  if ($pageSlug === 'home' || $pageSlug === 'index' || $pageSlug === 'index.php') {
      ?>
      <section class="hero">
        <div class="hero-content">
          <h1>Welcome To Chandusoft Technologies</h1>
          <p>Delivering IT & BPO solutions for over 15 years.</p>
          <a href="services.php" class="btn-hero">Explore Services</a>
        </div>
      </section>

      <section class="testimonials">
        <h2>What Our Clients Say</h2>
        <div class="testimonial-container">
          <div class="testimonial">
            <p>"Chandusoft has transformed the way we manage our IT infrastructure. Their team is highly skilled and reliable."</p>
            <h4>Ramesh Kumar</h4>
            <span>CEO, TechWorld Ltd</span>
          </div>
          <div class="testimonial">
            <p>"Excellent BPO services! Our efficiency has improved drastically since partnering with Chandusoft."</p>
            <h4>Anita Sharma</h4>
            <span>Operations Head, Global BPO</span>
          </div>
          <div class="testimonial">
            <p>"Professional, dedicated, and innovative team. Highly recommend their consulting services!"</p>
            <h4>John Smith</h4>
            <span>Director, Innovate Inc.</span>
          </div>
        </div>
      </section>
      <?php
  }

  // -----------------------------
  // 2️⃣ Static pages (about, services, contact)
  // -----------------------------
  elseif (in_array($pageSlug, ['about', 'services', 'contact'])) {
      $staticPage = __DIR__ . "/{$pageSlug}.php";
      if (file_exists($staticPage)) {
          include $staticPage;
      } else {
          include __DIR__ . '/views/404.php';
      }
  }

  // -----------------------------
  // 3️⃣ Dynamic pages (from DB)
  // -----------------------------
  else {
      $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
      $stmt->execute([$pageSlug]);
      $page = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($page) {
          echo "<div class='page-wrapper'><div class='page-content'>";
          echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
          echo $page['content_html'];
          echo "</div></div>";
      } else {
          include __DIR__ . '/views/404.php';
      }
  }
  ?>

  <!-- ✅ Footer -->
  <div id="footer">
    <?php include "footer.php"; ?>
  </div>

  <button id="back-to-top" title="Back to Top">↑</button>
  <script src="include.js"></script>
</body>
</html>