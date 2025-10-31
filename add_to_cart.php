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
        // if product uses sizes, ensure size selected and available
        if (!empty($product['has_sizes'])) {
            if (!$selected_size) {
                // no size selected; redirect back
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
        // Check if already in cart
        $stmt = $pdo->prepare('SELECT * FROM cart_items WHERE user_id = ? AND product_id = ? AND (size = ? OR (? IS NULL AND size IS NULL))');
        $stmt->execute([$user_id, $product_id, $selected_size, $selected_size]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity but ensure not exceeding size stock if applicable
            if (!empty($product['has_sizes'])) {
                $newQty = (int)$existing['quantity'] + 1;
                if ($newQty > (int)$sizeRow['stock']) {
                    // cannot add more than available
                    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
                    header('Location: ' . $redirect_url . '?error=insufficient_size_stock');
                    exit;
                }
                $stmt = $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ? AND size = ?');
                $stmt->execute([$user_id, $product_id, $selected_size]);
            } else {
                $stmt = $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$user_id, $product_id]);
            }
        } else {
            // Add new item (include size if provided)
            if (!empty($product['has_sizes'])) {
                $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity, size) VALUES (?, ?, 1, ?)');
                $stmt->execute([$user_id, $product_id, $selected_size]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)');
                $stmt->execute([$user_id, $product_id]);
            }
        }
    }
}

// Redirect back to referring page
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $redirect_url);
exit;
?> 