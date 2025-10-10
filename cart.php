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
        .cart-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-header h1 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        
        .cart-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            background-color: #fafafa;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 6px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-details h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .cart-item-details h3 a {
            color: #2c2dc1;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .cart-item-details h3 a:hover {
            color: #1e1e8f;
            text-decoration: underline;
        }
        
        .cart-item-details p {
            margin: 5px 0;
            color: #555;
        }
        
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .update-btn {
            background: #2c2dc1;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .update-btn:hover {
            background: #1e1e8f;
        }
        
        .remove-btn {
            background: #ff5252;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .remove-btn:hover {
            background: #e04141;
        }
        
        .cart-total {
            text-align: right;
            font-size: 1.2em;
            margin: 30px 0;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .checkout-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            display: inline-block;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        
        .checkout-btn:hover {
            background: #3d9140;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px;
        }
        
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column;">
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="min-height: calc(100vh - 200px); display: flex; flex-direction: column;">
        <div class="cart-container">
            <div class="cart-header">
                <h1>Your Shopping Cart</h1>
                <!-- <a href="index.php" style="color: #2c2dc1; text-decoration: none;">Continue Shopping</a> -->
            </div>
            
            <?php if ($msg): ?>
                <div class="success-message"><?php echo $msg; ?></div>
            <?php endif; ?>
            
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
                            <h3><a href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></h3>
                            <p><strong>Brand:</strong> <?php echo htmlspecialchars($item['brand']); ?></p>
                            <p><strong>Price:</strong> ₹<?php echo number_format($item['price'], 2); ?></p>
                            <p><strong>Subtotal:</strong> ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        </div>
                        <div class="cart-item-actions">
                            <form method="post" style="display: inline-flex; align-items: center; gap: 10px;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <label>Qty: <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input"></label>
                                <button type="submit" name="update_quantity" class="update-btn">Update</button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="remove_item" class="remove-btn" onclick="return confirm('Are you sure you want to remove this item?');">Remove</button>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
