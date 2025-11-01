<?php
session_start();
require_once 'includes/db.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$results = [];
$total_results = 0;

// Get all available categories for the filter
$stmt_categories = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category ASC');
$categories = $stmt_categories->fetchAll(PDO::FETCH_COLUMN);

// Get global min and max price for products to initialize price filter
$minMaxStmt = $pdo->query('SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM products');
$minMax = $minMaxStmt->fetch(PDO::FETCH_ASSOC);
$global_min_price = $minMax['min_price'] !== null ? (float)$minMax['min_price'] : 0.0;
$global_max_price = $minMax['max_price'] !== null ? (float)$minMax['max_price'] : 100000.0;

// read requested price filters from GET
$req_min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : $global_min_price;
$req_max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : $global_max_price;

if (!empty($search_query) || !empty($selected_category)) {
    // Build the query based on search and/or category filter
    $sql = 'SELECT * FROM products WHERE 1=1';
    $params = [];
    
    if (!empty($search_query)) {
        $sql .= ' AND (name LIKE ? OR brand LIKE ?)';
        $search_term = '%' . $search_query . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if (!empty($selected_category)) {
        $sql .= ' AND category = ?';
        $params[] = $selected_category;
    }

    // Price filtering
    if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
        $sql .= ' AND price >= ?';
        $params[] = (float)$_GET['min_price'];
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
        $sql .= ' AND price <= ?';
        $params[] = (float)$_GET['max_price'];
    }
    
    $sql .= ' ORDER BY name ASC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
        /* Modern Search Page Styling */
        .search-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin: 20px auto;
            max-width: 1200px;
        }
        
        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .search-header h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        
        .search-form {
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            min-width: 300px;
        }
        
        .search-form input[type="text"] {
            padding: 12px 15px;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .search-form input[type="text"]:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }
        
        .search-form button {
            padding: 12px 25px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .search-form button:hover {
            background-color: #0055aa;
        }
        
        .category-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        .category-filter-label {
            font-weight: bold;
            margin-right: 10px;
            display: flex;
            align-items: center;
        }
        
        .category-filter-btn {
            padding: 8px 15px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .category-filter-btn:hover {
            border-color: #0066cc;
            color: #0066cc;
        }
        
        .category-filter-btn.active {
            background-color: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        .search-results {
            margin: 20px 0;
        }
        
        .search-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .search-results-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .search-results-count {
            color: #666;
            font-size: 14px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .product-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .brand-name {
            color: #666;
            font-size: 14px;
            margin: 0 0 5px;
        }
        
        .product-name {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .product-name a {
            color: #333;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .product-name a:hover {
            color: #0066cc;
        }
        
        .product-category {
            font-size: 13px;
            color: #666;
            margin: 0 0 10px;
        }
        
        .price {
            font-size: 18px;
            font-weight: 600;
            color: #0066cc;
            margin: 0 0 15px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        
        .btn-add-cart, .btn-wishlist {
            flex: 1;
            padding: 8px 0;
            text-align: center;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn-add-cart {
            background-color: #0066cc;
            color: white;
        }
        
        .btn-add-cart:hover {
            background-color: #0055aa;
        }
        
        .btn-wishlist {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-wishlist:hover {
            background-color: #eaecef;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .no-results h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .no-results p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .no-results .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .no-results .btn:hover {
            background-color: #0055aa;
        }
        
        .clear-filters {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            margin-left: auto;
        }
        
        .clear-filters:hover {
            background-color: #eaecef;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input {
                width: 100%;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="search-container">
        <div class="search-header">
            <h2>Search Products</h2>
            <a href="index.php" style="text-decoration: none; color: #0066cc;">Back to Home</a>
        </div>
        
        <!-- Search Form -->
        <form class="search-form" method="get">
            <!-- preserve selected category when submitting -->
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
            <div class="search-input">
                <input type="text" name="q" placeholder="Search for products, brands, or categories..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <!-- Price filter -->
            <div style="min-width:260px;">
                <div style="padding:8px 12px;border-radius:8px;border:1px solid #eee;background:#fff;">
                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Price</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input id="minPriceInput" name="min_price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars(number_format($req_min_price, 2, '.', '')); ?>" style="width:80px;padding:6px;border:1px solid #ddd;border-radius:4px;">
                        <span style="color:#666;">To</span>
                        <input id="maxPriceInput" name="max_price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars(number_format($req_max_price, 2, '.', '')); ?>" style="width:110px;padding:6px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <div style="margin-top:10px;">
                        <input id="minRange" type="range" min="<?php echo htmlspecialchars($global_min_price); ?>" max="<?php echo htmlspecialchars($global_max_price); ?>" value="<?php echo htmlspecialchars($req_min_price); ?>" style="width:100%;">
                        <input id="maxRange" type="range" min="<?php echo htmlspecialchars($global_min_price); ?>" max="<?php echo htmlspecialchars($global_max_price); ?>" value="<?php echo htmlspecialchars($req_max_price); ?>" style="width:100%;margin-top:6px;">
                    </div>
                </div>
            </div>
            <button type="submit">Search</button>
            <?php if (!empty($search_query) || !empty($selected_category)): ?>
                <a href="search.php" class="clear-filters">Clear All</a>
            <?php endif; ?>
        </form>
        
        <!-- Category Filters -->
            <div class="category-filters">
            <div class="category-filter-label">Filter by Category:</div>
            <?php
                $qp = urlencode($search_query);
                $minp = urlencode($req_min_price);
                $maxp = urlencode($req_max_price);
                function cat_link($q, $cat, $sel, $minp, $maxp) {
                    return "search.php?q={$q}&category=" . urlencode($cat) . "&min_price={$minp}&max_price={$maxp}";
                }
            ?>
            <a href="<?php echo cat_link($qp, 'Running', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Running' ? 'active' : ''; ?>">Running</a>
            <a href="<?php echo cat_link($qp, 'Fitness & Clothing', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Fitness & Clothing' ? 'active' : ''; ?>">Fitness &amp; Clothing</a>
            <a href="<?php echo cat_link($qp, 'Football', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Football' ? 'active' : ''; ?>">Football</a>
            <a href="<?php echo cat_link($qp, 'Badminton', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Badminton' ? 'active' : ''; ?>">Badminton</a>
            <a href="<?php echo cat_link($qp, 'Tennis', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Tennis' ? 'active' : ''; ?>">Tennis</a>
            <a href="<?php echo cat_link($qp, 'Cycling', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Cycling' ? 'active' : ''; ?>">Cycling</a>
            <a href="<?php echo cat_link($qp, 'Swimming', $selected_category, $minp, $maxp); ?>" class="category-filter-btn <?php echo $selected_category == 'Swimming' ? 'active' : ''; ?>">Swimming</a>
        </div>
        
        <!-- Search Results -->
        <?php if (!empty($search_query) || !empty($selected_category)): ?>
            <div class="search-results">
                <div class="search-results-header">
                    <h3>
                        <?php if (!empty($search_query) && !empty($selected_category)): ?>
                            Search Results for "<?php echo htmlspecialchars($search_query); ?>" in <?php echo htmlspecialchars($selected_category); ?>
                        <?php elseif (!empty($search_query)): ?>
                            Search Results for "<?php echo htmlspecialchars($search_query); ?>"
                        <?php else: ?>
                            Products in <?php echo htmlspecialchars($selected_category); ?>
                        <?php endif; ?>
                    </h3>
                    <div class="search-results-count"><?php echo $total_results; ?> result(s) found</div>
                </div>
                
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
                                <div class="product-info">
                                    <p class="brand-name"><?php echo htmlspecialchars($product['brand']); ?></p>
                                    <h3 class="product-name">
                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
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
                                        <p style="color: red; font-weight: bold; margin-top: 0.5rem; text-align: center;">Out of Stock</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h3>Search for Products</h3>
                <p>Enter keywords to search for products, brands, or categories, or use the category filters above.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Global price range sync script (always included so sliders work even when no results)
    (function(){
        function initPriceSync(){
            var minRange = document.getElementById('minRange');
            var maxRange = document.getElementById('maxRange');
            var minInput = document.getElementById('minPriceInput');
            var maxInput = document.getElementById('maxPriceInput');
            if (!minRange || !maxRange || !minInput || !maxInput) return;

            function sanitize(val){ var n = parseFloat(val); return isNaN(n) ? 0 : n; }

            function syncFromRanges(){
                var minV = sanitize(minRange.value);
                var maxV = sanitize(maxRange.value);
                if (minV > maxV) { var t = minV; minV = maxV; maxV = t; }
                minInput.value = minV.toFixed(2);
                maxInput.value = maxV.toFixed(2);
            }

            function syncFromInputs(){
                var minV = sanitize(minInput.value);
                var maxV = sanitize(maxInput.value);
                if (minV > maxV) { var t = minV; minV = maxV; maxV = t; }
                // clamp to ranges
                var lo = sanitize(minRange.min);
                var hi = sanitize(minRange.max);
                if (minV < lo) minV = lo; if (maxV > hi) maxV = hi;
                minRange.value = minV;
                maxRange.value = maxV;
                minInput.value = minV.toFixed(2);
                maxInput.value = maxV.toFixed(2);
            }

            // attach listeners if not already
            if (!minRange._priceSyncAttached) {
                minRange.addEventListener('input', syncFromRanges);
                maxRange.addEventListener('input', syncFromRanges);
                minInput.addEventListener('change', syncFromInputs);
                maxInput.addEventListener('change', syncFromInputs);
                minRange._priceSyncAttached = true;
            }

            // initialize display
            syncFromRanges();
        }

        // Run on DOMContentLoaded and also attempt to init immediately in case elements are already present
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPriceSync);
        } else {
            initPriceSync();
        }
    })();
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>