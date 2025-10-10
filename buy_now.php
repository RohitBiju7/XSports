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
    
    // Check if product exists and has stock
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND quantity > 0');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        try {
            $pdo->beginTransaction();
            
            // Clear existing cart items
            $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $stmt->execute([$user_id]);
            
            // Add the selected product to cart with quantity 1
            $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)');
            $stmt->execute([$user_id, $product_id]);
            
            $pdo->commit();
            
            // Redirect to checkout page
            header('Location: checkout.php');
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
