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
    <img src="images/footballhero.webp" alt="Football Hero Banner" class="hero-gif">
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
          <a href="product.php?id=<?php echo (int)$product['id']; ?>" style="text-decoration:none;color:inherit;">
          <div style="position: relative;">
              <img src="<?php echo $product['image_path'] ?: 'images/football1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
          </div>
            <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
          </a>
          <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
            <?php if ($product['quantity'] > 0): ?>
              <div class="product-actions">
                <form method="post" action="buy_now.php">
                  <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                  <button type="submit" name="buy_now" class="btn-buy-now" style="background: #005eb8 !important; color: white !important; border: 2px solid #005eb8 !important; padding: 12px 16px !important; border-radius: 8px !important; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; min-height: 44px !important; box-sizing: border-box; margin-top: 0 !important; transition: all 0.3s ease;" onmouseover="this.style.background='#004a94'; this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 6px 20px rgba(0, 94, 184, 0.4)'" onmouseout="this.style.background='#005eb8'; this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='none'">BUY NOW</button>
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
              <button class="btn-buy-now" disabled style="opacity: 0.5; cursor: not-allowed;">OUT OF STOCK</button>
              <button class="btn-add-cart" disabled style="opacity: 0.5; cursor: not-allowed;">OUT OF STOCK</button>
              <form method="post" action="add_to_wishlist.php" style="display: inline;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <button type="submit" name="add_to_wishlist" class="btn-wishlist">WISHLIST</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</main>

<?php include('includes/footer.php'); ?>
