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
        <title>Shopping Cart - XSports</title>
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
            <img src="images/missingcart.svg" alt="Empty Cart" class="empty-state-image">
            <h1 class="empty-state-title">Missing Cart Items?</h1>
            <p class="empty-state-subtitle">Login to see items you added previously</p>
            
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

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantity
    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = max(1, intval($_POST['quantity'])); // Ensure quantity is at least 1
        
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$quantity, $user_id, $product_id]);
        $msg = 'Cart updated!';
    }
    
    // Remove item
    if (isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user_id, $product_id]);
        $msg = 'Item removed from cart!';
    }
}

// Fetch cart items with product details
$stmt = $pdo->prepare('
    SELECT ci.*, p.name, p.brand, p.price, p.image_path 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.user_id = ?
');
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart - XSports</title>
<link rel="stylesheet" href="assets/style.css">
    <style>
        .cart-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; display: flex; align-items: center; }
        .cart-item img { width: 80px; height: 80px; object-fit: cover; margin-right: 15px; }
        .cart-item-details { flex: 1; }
        .cart-item-actions { display: flex; align-items: center; gap: 10px; }
        .quantity-input { width: 60px; padding: 5px; }
        .cart-total { text-align: right; font-size: 1.2em; margin: 20px 0; }
        .checkout-btn { background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; }
        .empty-cart { text-align: center; padding: 50px; }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column;">
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="min-height: calc(100vh - 200px); display: flex; flex-direction: column;">
        <?php if ($msg) echo '<p style="color: green;">'.$msg.'</p>'; ?>
        
        <?php if (empty($cart_items)): ?>
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px;">
                <img src="images/missingcart.svg" alt="Empty Cart" style="width: 300px; height: 300px; margin-bottom: 30px;">
                <h1 style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 15px;">Your Cart is Empty</h1>
                <p style="font-size: 16px; color: #666; margin-bottom: 30px; line-height: 1.5;">Add some products to your cart to get started!</p>
                
                <div>
                    <a href="index.php" style="background: #2c2dc1; color: white; padding: 15px 40px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; text-decoration: none; display: inline-block; cursor: pointer; transition: background-color 0.3s ease;">CONTINUE SHOPPING</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <img src="<?php echo $item['image_path'] ?: 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="cart-item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><strong>Brand:</strong> <?php echo htmlspecialchars($item['brand']); ?></p>
                        <p><strong>Price:</strong> ₹<?php echo number_format($item['price'], 2); ?></p>
                        <p><strong>Subtotal:</strong> ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                    </div>
                    <div class="cart-item-actions">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <label>Qty: <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input"></label>
                            <button type="submit" name="update_quantity">Update</button>
                        </form>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <button type="submit" name="remove_item" onclick="return confirm('Remove this item?');">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="cart-total">
                <h3>Total: ₹<?php echo number_format($total, 2); ?></h3>
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
