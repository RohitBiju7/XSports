<?php
session_start(); 
require_once 'includes/db.php';
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
        'Running', 'Fitness', 'Football', 'Badminton', 'Tennis', 'Cycling', 'Swimming'
      ];
      $links = [
        'running.php', 'fitness.php', 'football.php', 'badminton.php', 'tennis.php', 'cycling.php', 'swimming.php'
      ];
      for ($i = 0; $i < count($categories); $i++):
    ?>
      <a href="<?= $links[$i] ?>" style="text-decoration: none; color: inherit;">
        <div class="category-card">
          <img src="images/cat<?= $i + 1 ?>.jpg" alt="<?= $categories[$i] ?>">
          <p><?= $categories[$i] ?></p>
        </div>
      </a>
    <?php endfor; ?>
  </div>
</section>


  <!-- Featured Products -->
  <section class="products">
    <h2>Trending Products</h2>
    <div class="product-grid">
      <?php for ($i = 1; $i <= 14; $i++): ?>
        <div class="product-card">
          <div style="position: relative;">
            <img src="images/trendpro<?= $i ?>.jpg" alt="Product <?= $i ?>">
            <div style="position:absolute;top:8px;left:8px;background:#f0c040;color:#000;padding:2px 6px;font-size:12px;border-radius:4px;">Trending</div>
          </div>
          <p class="brand-name">Brand Name</p>
          <h3 class="product-name">Product <?= $i ?> Name</h3>
          <div class="price">₹Price</div>
          <div class="product-actions">
            <button class="btn-add-cart">ADD TO CART</button>
            <button class="btn-wishlist">WISHLIST</button>
          </div>
        </div>
      <?php endfor; ?>
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
