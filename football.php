<?php include('includes/header.php'); ?>

<main class="page-container">

  <!-- Hero Banner (Single GIF) -->
  <section class="hero-banner">
    <img src="images/footballhero.gif" alt="Football Hero Banner" class="hero-gif">
  </section>

  <!-- Featured Products -->
  <section class="products">
    <div class="product-grid">
      <?php for ($i = 1; $i <= 14; $i++): ?>
        <div class="product-card">
          <div style="position: relative;">
            <img src="images/football<?= $i ?>.jpg" alt="Product <?= $i ?>">
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

<?php include('includes/footer.php'); ?>
