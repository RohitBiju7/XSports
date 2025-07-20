<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Show login prompt instead of redirecting
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Wishlist - XSports</title>
        <link rel="stylesheet" href="assets/style.css">
        <style>
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            .main-content {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            .empty-state-container {
                max-width: 600px;
                margin: auto;
                text-align: center;
                padding: 40px 20px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .empty-state-image {
                width: 300px;
                height: 300px;
                margin: 0 auto 30px;
                display: block;
            }
            
            .empty-state-title {
                font-size: 28px;
                font-weight: bold;
                color: #333;
                margin-bottom: 15px;
            }
            
            .empty-state-subtitle {
                font-size: 16px;
                color: #666;
                margin-bottom: 30px;
                line-height: 1.5;
            }
            
            .login-btn {
                background: #2c2dc1;
                color: white;
                padding: 15px 40px;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }
            
            .login-btn:hover {
                background: #1e1e8f;
            }
            
            .continue-shopping-btn {
                background: white;
                color: #333;
                padding: 15px 40px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 16px;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                margin-left: 15px;
                transition: background-color 0.3s ease;
            }
            
            .continue-shopping-btn:hover {
                background: #f8f9fa;
            }
            
            .button-container {
                margin-top: 30px;
            }
        </style>
    </head>
    <body>
<?php include 'includes/header.php'; ?>

        <div class="main-content">
            <div class="empty-state-container">
            <img src="images/wishlistlogin.svg" alt="Login for Wishlist" class="empty-state-image">
            <h1 class="empty-state-title">Login to Add/View Wishlist</h1>
            <p class="empty-state-subtitle">Sign in to save your favorite products and view your wishlist</p>
            
            <div class="button-container">
                <a href="signin.php" class="login-btn">LOGIN / SIGNUP</a>
                <a href="index.php" class="continue-shopping-btn">CONTINUE SHOPPING</a>
            </div>
        </div>
        </div>
        
        <?php include 'includes/footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';

// Handle wishlist operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove from wishlist
    if (isset($_POST['remove_wishlist'])) {
        $product_id = $_POST['product_id'];
        $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user_id, $product_id]);
        $msg = 'Item removed from wishlist!';
    }
    
    // Add to cart from wishlist
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        
        // Check if already in cart
        $stmt = $pdo->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $stmt = $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$user_id, $product_id]);
        } else {
            // Add new item
            $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)');
            $stmt->execute([$user_id, $product_id]);
        }
        
        $msg = 'Item added to cart!';
    }
}

// Fetch wishlist items with product details
$stmt = $pdo->prepare('
    SELECT w.*, p.name, p.brand, p.price, p.image_path, p.quantity as stock_quantity
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    WHERE w.user_id = ?
');
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wishlist - XSports</title>
<link rel="stylesheet" href="assets/style.css">
    <style>
        .wishlist-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; display: flex; align-items: center; }
        .wishlist-item img { width: 80px; height: 80px; object-fit: cover; margin-right: 15px; }
        .wishlist-item-details { flex: 1; }
        .wishlist-item-actions { display: flex; align-items: center; gap: 10px; }
        .out-of-stock { color: red; font-weight: bold; }
        .empty-wishlist { text-align: center; padding: 50px; }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column;">
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="min-height: calc(100vh - 200px); display: flex; flex-direction: column;">
        <?php if ($msg) echo '<p style="color: green;">'.$msg.'</p>'; ?>
        
        <?php if (empty($wishlist_items)): ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
                <img src="images/wishlistlogin.svg" alt="Empty Wishlist" style="width: 300px; height: 300px; margin-bottom: 30px;">
                <h1 style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 15px;">Your Wishlist is Empty</h1>
                <p style="font-size: 16px; color: #666; margin-bottom: 30px; line-height: 1.5;">Add some products to your wishlist to save them for later!</p>
                
                <div>
                    <a href="index.php" style="background: #2c2dc1; color: white; padding: 15px 40px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block; cursor: pointer; transition: background-color 0.3s ease;">CONTINUE SHOPPING</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($wishlist_items as $item): ?>
                <div class="wishlist-item">
                    <img src="<?php echo $item['image_path'] ?: 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="wishlist-item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><strong>Brand:</strong> <?php echo htmlspecialchars($item['brand']); ?></p>
                        <p><strong>Price:</strong> ₹<?php echo number_format($item['price'], 2); ?></p>
                        <?php if ($item['stock_quantity'] <= 0): ?>
                            <p class="out-of-stock">Out of Stock</p>
                        <?php else: ?>
                            <p><strong>Stock:</strong> <?php echo $item['stock_quantity']; ?> available</p>
                        <?php endif; ?>
                    </div>
                    <div class="wishlist-item-actions">
                        <?php if ($item['stock_quantity'] > 0): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="add_to_cart">Add to Cart</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <button type="submit" name="remove_wishlist" onclick="return confirm('Remove from wishlist?');">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="cart.php" class="btn">View Cart</a>
                <a href="index.php" class="btn">Continue Shopping</a>
            </div>
        <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
