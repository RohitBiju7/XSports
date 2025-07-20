<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
<a href="/index.php">
<img alt="XSports Logo" class="logo-img" src="/images/logo.png"/>
</a>
</div>
<!-- Center: Search Bar (optional) -->
<?php if(basename($_SERVER['PHP_SELF']) != 'support.php'): ?>
<div class="search-section">
<input class="search-bar" placeholder='Search for "shoes", "cricket", "cycle"...' type="text"/>
</div>
<?php endif; ?>
<!-- Right: Navigation Icons -->
<div class="right-section">
<div class="nav-icons">
<?php if (isset($_SESSION['username'])): ?>
<div class="nav-item account-wrapper">
<a class="nav-item" href="#">
<i class="fa-regular fa-user"></i>
<span>Account</span>
</a>
<div class="account-dropdown">
    <div style="padding: 10px 16px; font-weight: bold; font-size: 14px; color: #1a1a1a;">
    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
  </div>
  <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
<a href="#"><i class="fa-regular fa-user"></i><span>My Profile</span></a>
<a href="#"><i class="fa-solid fa-box"></i><span>Orders</span></a>
<a href="#"><i class="fa-regular fa-envelope"></i><span>My Addresses</span></a>
<a class="logout-link" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></a>
</div>
</div>
<?php else: ?>
<a class="nav-item" href="signin.php">
<i class="fa-regular fa-user"></i>
<span>Sign In</span>
</a>
<?php endif; ?>
<a class="nav-item" href="support.php">
<i class="fa-solid fa-headset"></i>
<span>Support</span>
</a>
<a class="nav-item" href="wishlist.php">
<i class="fa-regular fa-heart"></i>
<span>Wishlist</span>
</a>
<a class="nav-item" href="cart.php">
<i class="fa-solid fa-cart-shopping"></i>
<span>Cart</span>
</a>
</div>
</div>
</div>
</header>
</body>
</html>
