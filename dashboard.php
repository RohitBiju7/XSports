<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<main class="page-container">
    <div class="wishlist-container">
        <img src="images/account.png" alt="Welcome">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>You have successfully logged into your XSports account.</p>
        <a href="logout.php" class="btn-login-signup">Logout</a>
    </div>
</main>

<footer class="footer-light">
    <hr>
    <p>&copy; <?php echo date("Y"); ?> XSports. All rights reserved.</p>
</footer>

</body>
</html>
