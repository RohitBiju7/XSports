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
    
    // Check if product exists
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Check if already in wishlist
        $stmt = $pdo->prepare('SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();
        
        if (!$existing) {
            // Add to wishlist
            $stmt = $pdo->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)');
            $stmt->execute([$user_id, $product_id]);
        }
    }
}

// Redirect back to referring page
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $redirect_url);
exit;
?> 