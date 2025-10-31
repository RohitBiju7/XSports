<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart and wishlist counts if user is logged in
$cart_count = 0;
$wishlist_count = 0;

if (isset($_SESSION['user_id'])) {
    // Check if database connection is available
    if (isset($pdo) && $pdo) {
        // Get cart count
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = $stmt->fetch()['count'];
        
        // Get wishlist count
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $wishlist_count = $stmt->fetch()['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>XSports</title>
<link href="images/favicon.ico" rel="icon" type="image/x-icon"/>
<link href="assets/style.css" rel="stylesheet"/>
<link crossorigin="anonymous" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer" rel="stylesheet"/>
</head>
<body>
<header class="top-header">
<div class="header-container">
<!-- Left: Logo and All Sports -->
<div class="left-section">
<button class="hamburger">☰</button>
<span class="all-sports">ALL<br/>SPORTS</span>
<a href="index.php">
<img alt="XSports Logo" class="logo-img" src="images/logo.png"/>
</a>
</div>
<!-- Center: Search Bar -->
<?php if(basename($_SERVER['PHP_SELF']) != 'support.php'): ?>
<div class="search-section">
<form action="search.php" method="get" style="display: flex; width: 100%;">
    <input class="search-bar" name="q" placeholder='Search for "shoes", "cricket", "cycle"...' type="text" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"/>
</form>
</div>
<?php endif; ?>
<!-- Right: Navigation Icons -->
<div class="right-section">
<div class="nav-icons">
<?php if (isset($_SESSION['admin_logged_in'])): ?>
<!-- Admin is logged in -->
<div class="nav-item account-wrapper">
<a class="nav-item" href="#">
<i class="fa-solid fa-user-shield"></i>
<span>Admin</span>
</a>
<div class="account-dropdown">
    <div style="padding: 10px 16px; font-weight: bold; font-size: 14px; color: #1a1a1a;">
    Welcome, Admin!
  </div>
  <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
<a href="admin.php"><i class="fa-solid fa-tachometer-alt"></i><span>Dashboard</span></a>
<a href="admin_support.php"><i class="fa-solid fa-headset"></i><span>Support Management</span></a>
<a class="logout-link" href="admin.php?logout=1"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></a>
</div>
</div>
<a class="nav-item" href="admin.php">
<i class="fa-solid fa-boxes-stacked"></i>
<span>Product Management</span>
</a>
<!-- Incoming Orders for admin -->
<a class="nav-item" href="admin_orders.php">
        <i class="fa-solid fa-receipt"></i>
        <span>Incoming Orders</span>
</a>
<?php elseif (isset($_SESSION['user_id'])): ?>
<!-- Regular user is logged in -->
<div class="nav-item account-wrapper">
<a class="nav-item" href="#">
<i class="fa-regular fa-user"></i>
<span>Account</span>
</a>
<div class="account-dropdown">
    <div style="padding: 10px 16px; font-weight: bold; font-size: 14px; color: #1a1a1a;">
    Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!
  </div>
  <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
<a href="dashboard.php"><i class="fa-regular fa-user"></i><span>My Profile</span></a>
<a href="orders.php"><i class="fa-solid fa-box"></i><span>Orders</span></a>
<a href="dashboard.php"><i class="fa-regular fa-envelope"></i><span>My Addresses</span></a>
<a class="logout-link" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></a>
</div>
</div>
<?php else: ?>
<!-- No user logged in -->
<a class="nav-item" href="signin.php">
<i class="fa-regular fa-user"></i>
<span>Sign In</span>
</a>
<?php endif; ?>
<a class="nav-item" href="<?php echo isset($_SESSION['admin_logged_in']) ? 'admin_support.php' : 'support.php'; ?>">
<i class="fa-solid fa-headset"></i>
<span>Support</span>
</a>
<?php if (!isset($_SESSION['admin_logged_in'])): ?>
<a class="nav-item" href="wishlist.php">
<i class="fa-regular fa-heart"></i>
<span>Wishlist</span>
<?php if ($wishlist_count > 0): ?>
    <span class="badge"><?php echo $wishlist_count; ?></span>
<?php endif; ?>
</a>
<a class="nav-item" href="cart.php">
<i class="fa-solid fa-cart-shopping"></i>
<span>Cart</span>
<?php if ($cart_count > 0): ?>
    <span class="badge"><?php echo $cart_count; ?></span>
<?php endif; ?>
</a>
<?php endif; ?>
</div>
</div>
</div>
</header>
