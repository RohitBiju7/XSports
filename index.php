<?php
session_start(); 
require_once 'includes/db.php';

// Fetch random products from each category for trending products
$categories = ['Running', 'Fitness & Clothing', 'Football', 'Badminton', 'Tennis', 'Cycling', 'Swimming'];
$trending_products = [];

foreach ($categories as $category) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? ORDER BY RAND() LIMIT 2');
    $stmt->execute([$category]);
    $category_products = $stmt->fetchAll();
    $trending_products = array_merge($trending_products, $category_products);
}

// Shuffle the products to mix categories
shuffle($trending_products);

// Limit to 14 products to match the original design
$trending_products = array_slice($trending_products, 0, 14);

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
    <h2>Trending Products</h2>
    
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
      <?php if (empty($trending_products)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
          <h3>No products available</h3>
          <p>Check back later for new arrivals!</p>
        </div>
      <?php else: ?>
        <?php foreach ($trending_products as $product): ?>
          <div class="product-card">
            <a href="product.php?id=<?php echo (int)$product['id']; ?>" style="text-decoration:none;color:inherit;">
              <div style="position: relative;">
                <img src="<?php echo $product['image_path'] ?: 'images/trendpro1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div style="position:absolute;top:8px;left:8px;background:#f0c040;color:#000;padding:2px 6px;font-size:12px;border-radius:4px;">Trending</div>
              </div>
              <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
              <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
            </a>
            <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
            <div class="product-actions">
              <?php if ($product['quantity'] > 0): ?>
                <form method="post" action="buy_now.php" style="display: inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="buy_now" class="btn-buy-now" style="background: #005eb8 !important; color: white !important; border: 2px solid #005eb8 !important; padding: 12px 16px !important; border-radius: 8px !important; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; min-height: 44px !important; box-sizing: border-box; margin-top: 0 !important; transition: all 0.3s ease;">BUY NOW</button>
                </form>
                <form method="post" action="add_to_cart.php" style="display: inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="add_to_cart" class="btn-add-cart">ADD TO CART</button>
                </form>
                <form method="post" action="add_to_wishlist.php" style="display: inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="add_to_wishlist" class="btn-wishlist">WISHLIST</button>
                </form>
              <?php else: ?>
                <button class="btn-buy-now" disabled style="opacity: 0.5; cursor: not-allowed;">OUT OF STOCK</button>
                <button class="btn-add-cart" disabled style="opacity: 0.5; cursor: not-allowed;">OUT OF STOCK</button>
                <form method="post" action="add_to_wishlist.php" style="display: inline;">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="add_to_wishlist" class="btn-wishlist">WISHLIST</button>
                </form>
              <?php endif; ?>
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
