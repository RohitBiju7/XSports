<?php
session_start();
require_once 'includes/db.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
$total_results = 0;

if (!empty($search_query)) {
    // Search in product name, brand, and category
    $stmt = $pdo->prepare('
        SELECT * FROM products 
        WHERE name LIKE ? OR brand LIKE ? OR category LIKE ?
        ORDER BY name ASC
    ');
    $search_term = '%' . $search_query . '%';
    $stmt->execute([$search_term, $search_term, $search_term]);
    $results = $stmt->fetchAll();
    $total_results = count($results);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .search-form { margin: 20px 0; text-align: center; }
        .search-form input[type="text"] { padding: 10px; width: 300px; margin-right: 10px; }
        .search-form button { padding: 10px 20px; }
        .search-results { margin: 20px 0; }
        .no-results { text-align: center; padding: 50px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Search Products</h2>
        
        <!-- Search Form -->
        <form class="search-form" method="get">
            <input type="text" name="q" placeholder="Search for products, brands, or categories..." value="<?php echo htmlspecialchars($search_query); ?>" required>
            <button type="submit">Search</button>
        </form>
        
        <!-- Search Results -->
        <?php if (!empty($search_query)): ?>
            <div class="search-results">
                <h3>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h3>
                <p>Found <?php echo $total_results; ?> result(s)</p>
                
                <?php if (empty($results)): ?>
                    <div class="no-results">
                        <h3>No products found</h3>
                        <p>Try searching with different keywords or browse our categories.</p>
                        <a href="index.php" class="btn">Browse All Products</a>
                    </div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($results as $product): ?>
                            <div class="product-card">
                                <img src="<?php echo $product['image_path'] ?: 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p style="font-size: 0.8rem; color: #666; margin: 4px 0;"><?php echo htmlspecialchars($product['category']); ?></p>
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
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>Search for Products</h3>
                <p>Enter keywords to search for products, brands, or categories.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 