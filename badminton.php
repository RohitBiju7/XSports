<?php
session_start();
require_once 'includes/db.php';

// Fetch badminton products from database
$stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? ORDER BY id DESC');
$stmt->execute(['badminton']);
$products = $stmt->fetchAll();
?>

<?php include('includes/header.php'); ?>

<main class="page-container">
  <!-- Hero Image Slider -->
  <section class="hero-slider">
    <div class="slider">
      <img src="images/badmintonhero1.jpg" class="slide active" alt="Hero 1">
      <img src="images/badmintonhero2.jpg" class="slide" alt="Hero 2">
      <!-- <img src="images/tennishero3.jpg" class="slide" alt="Hero 3"> -->
      <!-- <img src="images/hero4.jpg" class="slide" alt="Hero 4"> -->

      <button class="prev" onclick="prevSlide()">❮</button>
      <button class="next" onclick="nextSlide()">❯</button>
    </div>
  </section>

  

  <!-- Featured Products -->
  <section class="products">
    <!-- <h2>Trending Products</h2> -->
    <div class="product-grid">
      <?php if (empty($products)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
          <h3>No badminton products available</h3>
          <p>Check back later for new arrivals!</p>
        </div>
      <?php else: ?>
        <?php foreach ($products as $product): ?>
          <div class="product-card">
            <div style="position: relative;">
              <img src="<?php echo $product['image_path'] ?: 'images/badminton1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
              <!-- <div style="position:absolute;top:8px;left:8px;background:#f0c040;color:#000;padding:2px 6px;font-size:12px;border-radius:4px;">Trending</div> -->
            </div>
            <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
            <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
            <?php if ($product['quantity'] > 0): ?>
              <div class="product-actions">
                <form method="post" action="add_to_cart.php">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="add_to_cart" class="btn-add-cart">ADD TO CART</button>
                </form>
                <form method="post" action="add_to_wishlist.php">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="add_to_wishlist" class="btn-wishlist">WISHLIST</button>
                </form>
              </div>
            <?php else: ?>
              <p style="color: red; font-weight: bold; margin-top: 0.5rem;">Out of Stock</p>
            <?php endif; ?>
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
