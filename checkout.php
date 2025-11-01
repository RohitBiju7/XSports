<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = '';

// Fetch cart items with product details
$stmt = $pdo->prepare('
    SELECT ci.*, p.name, p.brand, p.price, p.image_path 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    WHERE ci.user_id = ?
');
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Fetch user addresses
$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY selected DESC, id DESC');
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $selected_address_id = $_POST['shipping_address'];
    $payment_method = $_POST['payment_method'];
    
    if (empty($cart_items)) {
        $msg = 'Your cart is empty!';
    } elseif (empty($selected_address_id)) {
        $msg = 'Please select a shipping address!';
    } else {
        // Calculate summary
        $shipping_amount = 50.00;
        $tax_amount = $total * 0.18;
        $grand_total = $total + $shipping_amount + $tax_amount;

        try {
            $pdo->beginTransaction();

            // Create order
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, address_id, payment_method, subtotal, shipping, tax, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $selected_address_id, $payment_method, $total, $shipping_amount, $tax_amount, $grand_total, 'placed']);
            $order_id = $pdo->lastInsertId();

            // Insert order items and decrement stock
            $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, brand, price, quantity, image_path, size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $decrProductStmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');
            $decrSizeStmt = $pdo->prepare('UPDATE product_sizes SET stock = stock - ? WHERE product_id = ? AND size = ? AND stock >= ?');

            foreach ($cart_items as $item) {
                $sizeVal = isset($item['size']) ? $item['size'] : null;
                $itemStmt->execute([$order_id, $item['product_id'], $item['name'], $item['brand'], $item['price'], $item['quantity'], $item['image_path'], $sizeVal]);

                // If item has size selected, decrement size stock first
                if ($sizeVal) {
                    $decrSizeStmt->execute([$item['quantity'], $item['product_id'], $sizeVal, $item['quantity']]);
                    if ($decrSizeStmt->rowCount() === 0) {
                        $pdo->rollBack();
                        $msg = 'Insufficient stock for one or more items (size). Please update your cart.';
                        break;
                    }
                }

                // Decrement overall product quantity
                $decrProductStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                if ($decrProductStmt->rowCount() === 0) {
                    $pdo->rollBack();
                    $msg = 'Insufficient stock for one or more items. Please update your cart.';
                    break;
                }
            }

            if (!$msg) {
                // Clear cart
                $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
                $stmt->execute([$user_id]);

                $pdo->commit();
                header('Location: orders.php?placed=1');
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $msg = 'Failed to place order. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .checkout-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .checkout-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .checkout-section { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; }
        .checkout-section h3 { margin-top: 0; color: #005eb8; }
        .cart-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .cart-item img { width: 60px; height: 60px; object-fit: cover; margin-right: 15px; }
        .cart-item-details { flex: 1; }
        .address-option { border: 1px solid #ddd; padding: 15px; margin: 10px 0; cursor: pointer; }
        .address-option:hover { border-color: #005eb8; }
        .address-option.selected { border-color: #005eb8; background: #f0f8ff; }
        .order-summary { background: #f9f9f9; padding: 20px; }
        .order-summary h3 { margin-top: 0; }
        .summary-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .total-row { border-top: 2px solid #ddd; padding-top: 10px; font-weight: bold; font-size: 1.2em; }
        .place-order-btn { width: 100%; padding: 15px; background: #4CAF50; color: white; border: none; font-size: 1.1em; cursor: pointer; }
        .place-order-btn:hover { background: #45a049; }
        .empty-cart { text-align: center; padding: 50px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="checkout-container">
        <h2>Checkout</h2>
        <?php if ($msg) echo '<p style="color: red;">'.$msg.'</p>'; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h3>Your cart is empty</h3>
                <p>Add some products to your cart to proceed with checkout.</p>
                <a href="index.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="checkout-grid">
                    <!-- Left Column: Order Details -->
                    <div>
                        <!-- Cart Items -->
                        <div class="checkout-section">
                            <h3>Order Items</h3>
                                <?php
                                    // Group cart items by product so we can show multiple sizes per product clearly
                                    $groups = [];
                                    foreach ($cart_items as $item) {
                                        $pid = $item['product_id'];
                                        if (!isset($groups[$pid])) {
                                            $groups[$pid] = [
                                                'product' => $item,
                                                'items' => []
                                            ];
                                        }
                                        $groups[$pid]['items'][] = $item;
                                    }

                                    foreach ($groups as $g) {
                                        $p = $g['product'];
                                        // If there are multiple items for this product (different sizes) show them grouped
                                ?>
                                    <div class="cart-item">
                                        <img src="<?php echo $p['image_path'] ?: 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        <div class="cart-item-details">
                                            <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                                            <p><strong>Brand:</strong> <?php echo htmlspecialchars($p['brand']); ?></p>

                                            <?php if (count($g['items']) > 1): ?>
                                                <!-- Multiple entries (likely different sizes) -->
                                                <div style="margin-top:8px;">
                                                    <?php foreach ($g['items'] as $sub): ?>
                                                        <div style="padding:6px 0;border-bottom:1px dashed #eee;">
                                                            <?php if (!empty($sub['size'])): ?>
                                                                <p style="margin:0;"><strong>Size:</strong> <?php echo htmlspecialchars($sub['size']); ?></p>
                                                            <?php endif; ?>
                                                            <p style="margin:0;"><strong>Quantity:</strong> <?php echo $sub['quantity']; ?></p>
                                                            <p style="margin:0 0 4px 0;"><strong>Price:</strong> ₹<?php echo number_format($sub['price'], 2); ?></p>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <!-- Single entry -->
                                                <?php $only = $g['items'][0]; ?>
                                                <?php if (!empty($only['size'])): ?><p><strong>Size:</strong> <?php echo htmlspecialchars($only['size']); ?></p><?php endif; ?>
                                                <p><strong>Quantity:</strong> <?php echo $only['quantity']; ?></p>
                                                <p><strong>Price:</strong> ₹<?php echo number_format($only['price'], 2); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php } // end groups loop ?>
                        </div>
                        
                        <!-- Shipping Address -->
                        <div class="checkout-section">
                            <h3>Shipping Address</h3>
                            <?php if (empty($addresses)): ?>
                                <p>No addresses found. <a href="dashboard.php">Add an address</a></p>
                            <?php else: ?>
                                <?php foreach ($addresses as $address): ?>
                                    <div class="address-option">
                                        <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" id="address_<?php echo $address['id']; ?>" <?php echo $address['selected'] ? 'checked' : ''; ?>>
                                        <label for="address_<?php echo $address['id']; ?>">
                                            <strong><?php echo htmlspecialchars($address['address_line']); ?></strong><br>
                                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['pincode']); ?>
                                            <?php if ($address['selected']) echo ' <em>(Default)</em>'; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="checkout-section">
                            <h3>Payment Method</h3>
                            <div>
                                <input type="radio" name="payment_method" value="cod" id="cod" checked>
                                <label for="cod">Cash on Delivery</label>
                            </div>
                            <!-- <div>
                                <input type="radio" name="payment_method" value="card" id="card">
                                <label for="card">Credit/Debit Card</label>
                            </div>
                            <div>
                                <input type="radio" name="payment_method" value="upi" id="upi">
                                <label for="upi">UPI</label>
                            </div> -->
                        </div>
                    </div>
                    
                    <!-- Right Column: Order Summary -->
                    <div>
                        <div class="order-summary">
                            <h3>Order Summary</h3>
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping:</span>
                                <span>₹50.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Tax:</span>
                                <span>₹<?php echo number_format($total * 0.18, 2); ?></span>
                            </div>
                            <div class="summary-row total-row">
                                <span>Total:</span>
                                <span>₹<?php echo number_format($total + 50 + ($total * 0.18), 2); ?></span>
                            </div>
                            
                            <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 