<?php
session_start();
require_once 'includes/db.php';

// Fetch fitness & clothing products from database
$stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? ORDER BY id DESC');
$stmt->execute(['Fitness & Clothing']);
$products = $stmt->fetchAll();
$totalProducts = count($products);
?>

<?php include('includes/header.php'); ?>
<style>
  .load-more-container {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin: 24px auto 0;
  }
  .btn-load-more {
    background: #005eb8;
    color: #fff;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
  }
  .btn-load-more:hover { background: #024c95; }
  .btn-load-more.secondary {
    background: #fff;
    color: #005eb8;
    border: 2px solid #005eb8;
  }
  .btn-load-more.secondary:hover { background: #f0f6ff; }
  .hidden-card { display: none !important; }
</style>

<main class="page-container">
  <!-- Hero Image Slider -->
  <section class="hero-slider">
    <div class="slider">
      <img src="images/fitnesshero1.webp" class="slide active" alt="Hero 1">
      <img src="images/fitnesshero2.webp" class="slide" alt="Hero 2">
      <img src="images/fitnesshero3.webp" class="slide" alt="Hero 3">
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
          <h3>No fitness &amp; clothing products available</h3>
          <p>Check back later for new arrivals!</p>
        </div>
      <?php else: ?>
        <?php $index = 0; foreach ($products as $product): ?>
        <div class="product-card<?php echo $index >= 8 ? ' hidden-card' : ''; ?>">
          <a href="product.php?id=<?php echo (int)$product['id']; ?>" style="text-decoration:none;color:inherit;">
          <div style="position: relative;">
              <img src="<?php echo $product['image_path'] ?: 'images/fitness1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <!-- <div style="position:absolute;top:8px;left:8px;background:#f0c040;color:#000;padding:2px 6px;font-size:12px;border-radius:4px;">Trending</div> -->
          </div>
            <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
          </a>
          <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
            <?php if ($product['quantity'] > 0): ?>
              <div class="product-actions">
                <form method="post" action="buy_now.php">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="buy_now" class="btn-buy-now" style="background: #005eb8 !important; color: white !important; border: 2px solid #005eb8 !important; padding: 12px 16px !important; border-radius: 8px !important; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; min-height: 44px !important; box-sizing: border-box; margin-top: 0 !important; transition: all 0.3s ease;">BUY NOW</button>
                </form>
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
              <button class="btn-add-cart" disabled style="opacity: 0.5; cursor: not-allowed;">OUT OF STOCK</button>
              <form method="post" action="add_to_wishlist.php" style="display: inline;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <button type="submit" name="add_to_wishlist" class="btn-wishlist">WISHLIST</button>
              </form>
            <?php endif; ?>
          </div>
        <?php $index++; endforeach; ?>
      <?php endif; ?>
    </div>
    <?php if ($totalProducts > 8): ?>
      <div class="load-more-container">
        <button type="button" class="btn-load-more" id="btnShowMore">Show More</button>
        <button type="button" class="btn-load-more secondary" id="btnShowAll">Show All</button>
      </div>
    <?php endif; ?>
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

  document.addEventListener('DOMContentLoaded', () => {
    showSlide(slideIndex);
    startAutoSlide();

    const cards = Array.from(document.querySelectorAll('.product-grid .product-card'));
    const showStep = 8;
    let visibleCount = Math.min(showStep, cards.length);
    const btnMore = document.getElementById('btnShowMore');
    const btnAll = document.getElementById('btnShowAll');

    function updateVisibility() {
      cards.forEach((card, idx) => {
        if (idx < visibleCount) card.classList.remove('hidden-card');
        else card.classList.add('hidden-card');
      });
      if (btnMore) btnMore.style.display = visibleCount >= cards.length ? 'none' : 'inline-flex';
      if (btnAll) btnAll.style.display = visibleCount >= cards.length ? 'none' : 'inline-flex';
    }

    if (btnMore) {
      btnMore.addEventListener('click', () => {
        visibleCount = Math.min(visibleCount + showStep, cards.length);
        updateVisibility();
      });
    }

    if (btnAll) {
      btnAll.addEventListener('click', () => {
        visibleCount = cards.length;
        updateVisibility();
      });
    }

    updateVisibility();
  });
</script>

<?php include('includes/footer.php'); ?>
