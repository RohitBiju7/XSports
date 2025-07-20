<?php
session_start();
require_once 'includes/db.php';

// Fetch football products from database
$stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? ORDER BY id DESC');
$stmt->execute(['football']);
$products = $stmt->fetchAll();
?>

<?php include('includes/header.php'); ?>

<main class="page-container">

  <!-- Hero Banner (Single GIF) -->
  <section class="hero-banner">
    <img src="images/footballhero.gif" alt="Football Hero Banner" class="hero-gif">
  </section>

  <!-- Featured Products -->
  <section class="products">
    <div class="product-grid">
      <?php if (empty($products)): ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
          <h3>No football products available</h3>
          <p>Check back later for new arrivals!</p>
        </div>
      <?php else: ?>
        <?php foreach ($products as $product): ?>
        <div class="product-card">
          <div style="position: relative;">
              <img src="<?php echo $product['image_path'] ?: 'images/football1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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

<?php include('includes/footer.php'); ?>
