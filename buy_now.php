<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $selected_size = isset($_POST['selected_size']) ? trim($_POST['selected_size']) : null;
    
    // Check if product exists and has stock
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND quantity > 0');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // if product has sizes ensure selected_size present and available
        if (!empty($product['has_sizes'])) {
            if (!$selected_size) {
                $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                header('Location: ' . $redirect_url . '?error=select_size');
                exit;
            }
            $sStmt = $pdo->prepare('SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?');
            $sStmt->execute([$product_id, $selected_size]);
            $sizeRow = $sStmt->fetch();
            if (!$sizeRow || (int)$sizeRow['stock'] <= 0) {
                $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                header('Location: ' . $redirect_url . '?error=out_of_stock_size');
                exit;
            }
        }
        try {
            $pdo->beginTransaction();

            // Do not clear the cart. Instead add/update the selected product (respecting size if any)
            if (!empty($product['has_sizes'])) {
                // check for existing cart row with same size
                $check = $pdo->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? AND size = ?');
                $check->execute([$user_id, $product_id, $selected_size]);
                $existing = $check->fetch();

                // ensure we have latest stock for this size
                $sStmt = $pdo->prepare('SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?');
                $sStmt->execute([$product_id, $selected_size]);
                $sizeRow = $sStmt->fetch();
                $available = $sizeRow ? (int)$sizeRow['stock'] : 0;

                if ($existing) {
                    $newQty = (int)$existing['quantity'] + 1;
                    if ($newQty > $available) {
                        $pdo->rollBack();
                        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                        header('Location: ' . $redirect_url . '?error=insufficient_size_stock');
                        exit;
                    }
                    $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ? AND size = ?');
                    $upd->execute([$user_id, $product_id, $selected_size]);
                } else {
                    if ($available <= 0) {
                        $pdo->rollBack();
                        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                        header('Location: ' . $redirect_url . '?error=out_of_stock_size');
                        exit;
                    }
                    $ins = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity, size) VALUES (?, ?, 1, ?)');
                    $ins->execute([$user_id, $product_id, $selected_size]);
                }
            } else {
                // product without sizes
                $check = $pdo->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? AND (size IS NULL OR size = "")');
                $check->execute([$user_id, $product_id]);
                $existing = $check->fetch();

                $pQty = (int)$product['quantity'];
                if ($existing) {
                    $newQty = (int)$existing['quantity'] + 1;
                    if ($newQty > $pQty) {
                        $pdo->rollBack();
                        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                        header('Location: ' . $redirect_url . '?error=insufficient_stock');
                        exit;
                    }
                    $upd = $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ? AND (size IS NULL OR size = "")');
                    $upd->execute([$user_id, $product_id]);
                } else {
                    if ($pQty <= 0) {
                        $pdo->rollBack();
                        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                        header('Location: ' . $redirect_url . '?error=out_of_stock');
                        exit;
                    }
                    $ins = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)');
                    $ins->execute([$user_id, $product_id]);
                }
            }

            $pdo->commit();

            // Redirect to cart page
            header('Location: cart.php');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Redirect back with error
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
            header('Location: ' . $redirect_url . '?error=buy_now_failed');
            exit;
        }
    } else {
        // Product not found or out of stock
        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header('Location: ' . $redirect_url . '?error=out_of_stock');
        exit;
    }
}

// If not a POST request or missing product_id, redirect to home
header('Location: index.php');
exit;
?>
