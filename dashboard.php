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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #f8f9fa;
            --accent-color: #ff6b6b;
            --text-color: #333;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        
        .dashboard-title {
            font-size: 28px;
            color: var(--primary-color);
            margin: 0;
        }
        
        .notification {
            background-color: #e7f3ff;
            border-left: 4px solid var(--primary-color);
            padding: 12px 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 16px;
            margin: 0;
        }
        
        .stat-icon {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .dashboard-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            font-size: 20px;
            margin: 0;
            color: var(--text-color);
        }
        
        .section-icon {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.2);
        }
        
        .form-control:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: #0055aa;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-container input {
            margin-right: 10px;
        }
        
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .address-card {
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 15px;
            position: relative;
            transition: var(--transition);
        }
        
        .address-card:hover {
            box-shadow: var(--box-shadow);
        }
        
        .address-card.default {
            border-color: var(--primary-color);
            background-color: rgba(0, 102, 204, 0.05);
        }
        
        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .address-content {
            margin-bottom: 15px;
        }
        
        .address-actions {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
        }
        
        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .action-link i {
            margin-right: 5px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .action-link.delete {
            color: var(--accent-color);
        }
        
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            background-color: white;
            border: 1px solid #ddd;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--text-color);
            transition: var(--transition);
        }
        
        .action-btn:hover {
            background-color: var(--secondary-color);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .action-btn i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .action-btn.primary {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .action-btn.primary:hover {
            background-color: #0055aa;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .address-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">My Dashboard</h1>
        </div>
        
        <?php if ($msg): ?>
        <div class="notification">
            <i class="fas fa-info-circle"></i> <?php echo $msg; ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-number"><?php echo $cart_count; ?></div>
                <p class="stat-label">Cart Items</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-heart"></i></div>
                <div class="stat-number"><?php echo $wishlist_count; ?></div>
                <p class="stat-label">Wishlist Items</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-number"><?php echo count($addresses); ?></div>
                <p class="stat-label">Saved Addresses</p>
            </div>
        </div>
        
        <!-- Profile Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <i class="fas fa-user section-icon"></i>
                <h3 class="section-title">Profile Information</h3>
            </div>
            <form method="post">
                <div class="form-group">
                    <input type="text" class="form-control" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-primary" name="update_profile">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Addresses Section -->
        <div class="dashboard-section">
            <div class="section-header">
                <i class="fas fa-map-marked-alt section-icon"></i>
                <h3 class="section-title">Manage Addresses</h3>
            </div>
            
            <!-- Add/Edit Address Form -->
            <form method="post">
                <?php if ($edit_address) echo '<input type="hidden" name="address_id" value="'.$edit_address['id'].'">'; ?>
                <div class="form-group">
                    <input type="text" class="form-control" name="address_line" placeholder="Address Line" value="<?php echo htmlspecialchars($edit_address['address_line'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="city" placeholder="City" value="<?php echo htmlspecialchars($edit_address['city'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="state" placeholder="State" value="<?php echo htmlspecialchars($edit_address['state'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="pincode" placeholder="Pincode" value="<?php echo htmlspecialchars($edit_address['pincode'] ?? ''); ?>" required>
                </div>
                <div class="checkbox-container">
                    <input type="checkbox" id="default-address" name="selected" <?php echo ($edit_address['selected'] ?? false) ? 'checked' : ''; ?>>
                    <label for="default-address">Set as default address</label>
                </div>
                <button type="submit" class="btn-primary" name="<?php echo $edit_address ? 'update_address' : 'add_address'; ?>">
                    <i class="fas fa-<?php echo $edit_address ? 'edit' : 'plus'; ?>"></i> 
                    <?php echo $edit_address ? 'Update' : 'Add'; ?> Address
                </button>
                <?php if ($edit_address): ?>
                <a href="dashboard.php" class="btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </form>
            
            <!-- Display Addresses -->
            <h4>Your Addresses</h4>
            <?php if (empty($addresses)): ?>
                <p>No addresses saved yet.</p>
            <?php else: ?>
                <div class="address-grid">
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo $address['selected'] ? 'default' : ''; ?>">
                            <?php if ($address['selected']): ?>
                                <span class="default-badge">Default</span>
                            <?php endif; ?>
                            <div class="address-content">
                                <p><strong><?php echo htmlspecialchars($address['address_line']); ?></strong></p>
                                <p><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['pincode']); ?></p>
                            </div>
                            <div class="address-actions">
                                <a href="dashboard.php?edit_address=<?php echo $address['id']; ?>" class="action-link">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="dashboard.php?delete_address=<?php echo $address['id']; ?>" onclick="return confirm('Delete this address?');" class="action-link delete">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Links -->
        <div class="dashboard-section">
            <div class="section-header">
                <i class="fas fa-bolt section-icon"></i>
                <h3 class="section-title">Quick Actions</h3>
            </div>
            <div class="quick-actions">
                <a href="cart.php" class="action-btn">
                    <i class="fas fa-shopping-cart"></i> View Cart (<?php echo $cart_count; ?>)
                </a>
                <a href="wishlist.php" class="action-btn">
                    <i class="fas fa-heart"></i> View Wishlist (<?php echo $wishlist_count; ?>)
                </a>
                <a href="index.php" class="action-btn primary">
                    <i class="fas fa-store"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>