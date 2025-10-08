

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Chandusoft</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="Style.css" />
   
</head>
 


 <div id="header">
    <?php include("header.php"); ?>


    <?php
require __DIR__ . '/../app/db.php'; // DB connection

// -----------------------
// Fetch pages for navbar
// -----------------------
$navStmt = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published'");
$navPages = $navStmt->fetchAll(PDO::FETCH_ASSOC);

// -----------------------
// Determine which page to show
// -----------------------
$pageSlug = $_GET['page'] ?? 'home';

if ($pageSlug === 'home') {
    $page = null; // Home page doesn't need DB content
    include __DIR__ . '/../views/home.php';
} else {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
    $stmt->execute([$pageSlug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($page) {
        include __DIR__ . '/../views/page.php';
    } else {
        include __DIR__ . '/../views/404.php';
    }
    
    
}
?>
</div>
  
  

 
<body>
 
 
<section class="hero">
  <div class="hero-content">
 
    <main>
 
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
 
</main>
  <script src="include.js"></script>
</body>
<button id="backToTop" title="Go to top">↑</button>

 
  <div id="footer"></div>
      <?php include("footer.php"); ?>
 
    </body>
</html>
 