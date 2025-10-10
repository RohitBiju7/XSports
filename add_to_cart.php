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
    }
}

// Redirect back to referring page
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $redirect_url);
exit;
?> 