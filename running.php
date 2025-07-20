<?php include('includes/header.php'); ?>

<main class="page-container">
  <!-- Hero Image Slider -->
  <section class="hero-slider">
    <div class="slider">
      <img src="images/runninghero1.jpg" class="slide active" alt="Hero 1">
      <img src="images/runninghero2.jpg" class="slide" alt="Hero 2">
      <img src="images/runninghero3.jpg" class="slide" alt="Hero 3">
      <!-- <img src="images/hero4.jpg" class="slide" alt="Hero 4"> -->

      <button class="prev" onclick="prevSlide()">❮</button>
      <button class="next" onclick="nextSlide()">❯</button>
    </div>
  </section>

  

  <!-- Featured Products -->
  <section class="products">
    <!-- <h2>Trending Products</h2> -->
    <div class="product-grid">
      <?php for ($i = 1; $i <= 14; $i++): ?>
        <div class="product-card">
          <div style="position: relative;">
            <img src="images/running<?= $i ?>.jpg" alt="Product <?= $i ?>">
            <!-- <div style="position:absolute;top:8px;left:8px;background:#f0c040;color:#000;padding:2px 6px;font-size:12px;border-radius:4px;">Trending</div> -->
          </div>
          <p style="margin: 0.3rem 0; font-size: 0.9rem; color: #888;">Brand Name</p>
          <h3 style="margin: 0.3rem 0; font-size: 1rem; font-weight: normal;">Product <?= $i ?> Name</h3>
          <div style="display:flex;justify-content:center;gap:8px;margin:0.3rem 0;">
            <span style="font-weight: bold;">₹Price</span>
          </div>
          <button style="margin-top: 0.5rem; background: white; border: 1px solid #005eb8; color: #005eb8; padding: 0.4rem 1rem; cursor: pointer; border-radius: 4px; font-weight: bold;">ADD TO CART</button>
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
