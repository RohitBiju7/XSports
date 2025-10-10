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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $stmt = $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
    $stmt->execute([$name, $phone, $user_id]);
    $msg = 'Profile updated successfully!';
}

// Handle address operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add address
    if (isset($_POST['add_address'])) {
        $address_line = $_POST['address_line'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $pincode = $_POST['pincode'];
        $selected = isset($_POST['selected']) ? 1 : 0;
        
        if ($selected) {
            // Unselect all other addresses
            $stmt = $pdo->prepare('UPDATE addresses SET selected = 0 WHERE user_id = ?');
            $stmt->execute([$user_id]);
        }
        
        $stmt = $pdo->prepare('INSERT INTO addresses (user_id, address_line, city, state, pincode, selected) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $address_line, $city, $state, $pincode, $selected]);
        $msg = 'Address added successfully!';
    }
    
    // Update address
    if (isset($_POST['update_address'])) {
        $address_id = $_POST['address_id'];
        $address_line = $_POST['address_line'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $pincode = $_POST['pincode'];
        $selected = isset($_POST['selected']) ? 1 : 0;
        
        if ($selected) {
            // Unselect all other addresses
            $stmt = $pdo->prepare('UPDATE addresses SET selected = 0 WHERE user_id = ?');
            $stmt->execute([$user_id]);
        }
        
        $stmt = $pdo->prepare('UPDATE addresses SET address_line = ?, city = ?, state = ?, pincode = ?, selected = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$address_line, $city, $state, $pincode, $selected, $address_id, $user_id]);
        $msg = 'Address updated successfully!';
    }
}

// Delete address
if (isset($_GET['delete_address'])) {
    $address_id = $_GET['delete_address'];
    $stmt = $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([$address_id, $user_id]);
    $msg = 'Address deleted successfully!';
}

// Fetch user data
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch user addresses
$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY selected DESC, id DESC');
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

// Fetch cart items count
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?');
$stmt->execute([$user_id]);
$cart_count = $stmt->fetch()['count'];

// Fetch wishlist count
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?');
$stmt->execute([$user_id]);
$wishlist_count = $stmt->fetch()['count'];

// If editing address, fetch it
$edit_address = null;
if (isset($_GET['edit_address'])) {
    $stmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([$_GET['edit_address'], $user_id]);
    $edit_address = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .dashboard-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        .address-form { margin: 10px 0; }
        .address-form input { margin: 5px 0; width: 100%; padding: 8px; }
        .address-item { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
        .selected-address { background: #e8f5e8; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { padding: 15px; border: 1px solid #ddd; text-align: center; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>My Dashboard</h2>
        <?php if ($msg) echo '<p style="color: green;">'.$msg.'</p>'; ?>
        
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo $cart_count; ?></h3>
                <p>Cart Items</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $wishlist_count; ?></h3>
                <p>Wishlist Items</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($addresses); ?></h3>
                <p>Saved Addresses</p>
            </div>
        </div>
        
        <!-- Profile Section -->
        <div class="dashboard-section">
            <h3>Profile Information</h3>
            <form method="post">
                <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled><br>
                <input type="text" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"><br>
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>
        
        <!-- Addresses Section -->
        <div class="dashboard-section">
            <h3>Manage Addresses</h3>
            
            <!-- Add/Edit Address Form -->
            <form class="address-form" method="post">
                <?php if ($edit_address) echo '<input type="hidden" name="address_id" value="'.$edit_address['id'].'">'; ?>
                <input type="text" name="address_line" placeholder="Address Line" value="<?php echo htmlspecialchars($edit_address['address_line'] ?? ''); ?>" required><br>
                <input type="text" name="city" placeholder="City" value="<?php echo htmlspecialchars($edit_address['city'] ?? ''); ?>" required><br>
                <input type="text" name="state" placeholder="State" value="<?php echo htmlspecialchars($edit_address['state'] ?? ''); ?>" required><br>
                <input type="text" name="pincode" placeholder="Pincode" value="<?php echo htmlspecialchars($edit_address['pincode'] ?? ''); ?>" required><br>
                <label><input type="checkbox" name="selected" <?php echo ($edit_address['selected'] ?? false) ? 'checked' : ''; ?>> Set as default address</label><br>
                <button type="submit" name="<?php echo $edit_address ? 'update_address' : 'add_address'; ?>">
                    <?php echo $edit_address ? 'Update' : 'Add'; ?> Address
                </button>
                <?php if ($edit_address) echo '<a href="dashboard.php">Cancel Edit</a>'; ?>
            </form>
            
            <!-- Display Addresses -->
            <h4>Your Addresses</h4>
            <?php if (empty($addresses)): ?>
                <p>No addresses saved yet.</p>
            <?php else: ?>
                <?php foreach ($addresses as $address): ?>
                    <div class="address-item <?php echo $address['selected'] ? 'selected-address' : ''; ?>">
                        <p><strong><?php echo htmlspecialchars($address['address_line']); ?></strong></p>
                        <p><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['pincode']); ?></p>
                        <?php if ($address['selected']) echo '<p><em>Default Address</em></p>'; ?>
                        <a href="dashboard.php?edit_address=<?php echo $address['id']; ?>">Edit</a> |
                        <a href="dashboard.php?delete_address=<?php echo $address['id']; ?>" onclick="return confirm('Delete this address?');">Delete</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Quick Links -->
        <div class="dashboard-section">
            <h3>Quick Actions</h3>
            <a href="cart.php" class="btn">View Cart (<?php echo $cart_count; ?>)</a>
            <a href="wishlist.php" class="btn">View Wishlist (<?php echo $wishlist_count; ?>)</a>
            <a href="index.php" class="btn">Continue Shopping</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 