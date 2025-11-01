<?php
session_start(); 
require_once 'includes/db.php';

// Fetch curated set of featured products (ensure at least 8 cards when possible)
$categoryPool = ['Running', 'Fitness & Clothing', 'Football', 'Badminton', 'Tennis', 'Cycling', 'Swimming'];
$featured_products = [];

// Pull up to 2 items per category to keep variety
foreach ($categoryPool as $category) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? ORDER BY RAND() LIMIT 2');
    $stmt->execute([$category]);
    $featured_products = array_merge($featured_products, $stmt->fetchAll());
}

// Fallback fetch to reach at least 8 items if variety request returns fewer
if (count($featured_products) < 8) {
    $needed = 8 - count($featured_products);
    $fallbackStmt = $pdo->prepare('SELECT * FROM products ORDER BY RAND() LIMIT ?');
    $fallbackStmt->bindValue(1, $needed, PDO::PARAM_INT);
    $fallbackStmt->execute();
    $featured_products = array_merge($featured_products, $fallbackStmt->fetchAll());
}

// Shuffle and trim to a sensible limit for the grid
shuffle($featured_products);
$featured_products = array_slice($featured_products, 0, max(8, min(count($featured_products), 14)));

include('includes/header.php'); 
?>

<main class="page-container">
  <!-- Hero Image Slider -->
  <section class="hero-slider">
    <div class="slider">
      <img src="images/hero1.jpg" class="slide active" alt="Hero 1">
      <img src="images/hero2.jpg" class="slide" alt="Hero 2">
      <img src="images/hero3.jpg" class="slide" alt="Hero 3">
      <img src="images/hero4.jpg" class="slide" alt="Hero 4">

      <button class="prev" onclick="prevSlide()">❮</button>
      <button class="next" onclick="nextSlide()">❯</button>
    </div>
  </section>

  <!-- Featured Categories -->
<section class="categories">
  <h2>Shop by Category</h2>
  <div class="category-grid">
    <?php
      $categories = [
        'Running', 'Fitness & Clothing', 'Football', 'Badminton', 'Tennis', 'Cycling', 'Swimming'
      ];
      $links = [
        'running.php', 'fitness.php', 'football.php', 'badminton.php', 'tennis.php', 'cycling.php', 'swimming.php'
      ];
      for ($i = 0; $i < count($categories); $i++):
    ?>
      <a href="<?= $links[$i] ?>" style="text-decoration: none; color: inherit;">
        <div class="category-card">
          <img src="images/cat<?= $i + 1 ?>.jpg" alt="<?= htmlspecialchars($categories[$i]); ?>">
          <p><?= htmlspecialchars($categories[$i]); ?></p>
        </div>
      </a>
    <?php endfor; ?>
  </div>
</section>


  <!-- Featured Products -->
  <section class="products">
    <h2>Featured Products</h2>
    
    <?php if (isset($_GET['error'])): ?>
      <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <?php 
        if ($_GET['error'] === 'out_of_stock') {
          echo 'Product is out of stock. Please try another product.';
        } elseif ($_GET['error'] === 'buy_now_failed') {
          echo 'Failed to process your request. Please try again.';
        } else {
          echo 'An error occurred. Please try again.';
        }
        ?>
      </div>
    <?php endif; ?>
    
    <div class="product-grid">
      <?php if (empty($featured_products)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
          <h3>No products available</h3>
          <p>Check back later for new arrivals!</p>
        </div>
      <?php else: ?>
        <?php foreach ($featured_products as $index => $product): ?>
          <div class="product-card <?php echo $index >= 8 ? 'hidden-card' : ''; ?>">
            <a href="product.php?id=<?php echo (int)$product['id']; ?>" style="text-decoration:none;color:inherit;">
              <div style="position: relative;">
                <img src="<?php echo $product['image_path'] ?: 'images/trendpro1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="badge">Featured</div>
              </div>
              <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
              <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
            </a>
            <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
            <div class="product-actions">
              <?php if ($product['quantity'] > 0): ?>
                <form method="post" action="buy_now.php" style="display:inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="buy_now" class="btn-buy-now">BUY NOW</button>
                </form>
                <form method="post" action="add_to_cart.php" style="display:inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="add_to_cart" class="btn-add-cart">ADD TO CART</button>
                </form>
              <?php else: ?>
                <button class="btn-buy-now" disabled>OUT OF STOCK</button>
              <?php endif; ?>
              <form method="post" action="add_to_wishlist.php" style="display:inline;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <button type="submit" name="add_to_wishlist" class="btn-wishlist">WISHLIST</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<!-- JavaScript for hero slider -->
<script>
  let slideIndex = 0;
  let slides = document.querySelectorAll('.slide');
  let autoSlide;

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove('active');
      if (i === index) slide.classList.add('active');
    });
  }

  function nextSlide() {
    slideIndex = (slideIndex + 1) % slides.length;
    showSlide(slideIndex);
  }

  function prevSlide() {
    slideIndex = (slideIndex - 1 + slides.length) % slides.length;
    showSlide(slideIndex);
  }

  function startAutoSlide() {
    autoSlide = setInterval(nextSlide, 5000);
  }

  window.onload = () => {
    showSlide(slideIndex);
    startAutoSlide();
  };
</script>

<?php include('includes/footer.php'); ?>
