<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: signin.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: signin.php');
    exit();
}

// Database connection
require_once 'includes/db.php';
// Database migrations have been moved to a dedicated SQL migration file:
//   migrations/2025-10-31-add-product-sizes.sql
// Run that migration manually (or via your deployment process) instead of using automatic migrations here.

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $brand = $_POST['brand'];
                $price = $_POST['price'];
                $category = $_POST['category'];
                $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
                $description = $_POST['description'] ?? '';
                $has_sizes = isset($_POST['has_sizes']) ? 1 : 0;

                // If sizes provided, compute total stock as sum of per-size stocks
                $size_stock = [];
                if ($has_sizes && isset($_POST['size_stock']) && is_array($_POST['size_stock'])) {
                    foreach ($_POST['size_stock'] as $sz => $val) {
                        $size_stock[$sz] = max(0, (int)$val);
                    }
                    $stock = array_sum($size_stock);
                }
                
                // Handle single image upload
                $upload_dir = 'images/products/';
                if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
                $image_path = '';
                if (isset($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('', true) . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    if (@move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_path = $upload_path;
                    }
                }

                $stmt = $pdo->prepare('INSERT INTO products (name, brand, price, category, quantity, description, image_path, has_sizes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $brand, $price, $category, $stock, $description, $image_path, $has_sizes]);
                $newProductId = $pdo->lastInsertId();

                // Insert per-size stocks if applicable
                if ($has_sizes && !empty($size_stock)) {
                    $insertSize = $pdo->prepare('INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)');
                    foreach ($size_stock as $sz => $val) {
                        if ($val > 0) {
                            $insertSize->execute([$newProductId, $sz, $val]);
                        }
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $brand = $_POST['brand'];
                $price = $_POST['price'];
                $category = $_POST['category'];
                $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
                $description = $_POST['description'] ?? '';
                $has_sizes = isset($_POST['has_sizes']) ? 1 : 0;

                // collect size stocks if provided
                $size_stock = [];
                if ($has_sizes && isset($_POST['size_stock']) && is_array($_POST['size_stock'])) {
                    foreach ($_POST['size_stock'] as $sz => $val) {
                        $size_stock[$sz] = max(0, (int)$val);
                    }
                    // override total stock with sum
                    $stock = array_sum($size_stock);
                }

                $stmt = $pdo->prepare('UPDATE products SET name = ?, brand = ?, price = ?, category = ?, quantity = ?, description = ?, has_sizes = ? WHERE id = ?');
                $stmt->execute([$name, $brand, $price, $category, $stock, $description, $has_sizes, $id]);

                // remove existing size rows and insert new ones
                $pdo->prepare('DELETE FROM product_sizes WHERE product_id = ?')->execute([$id]);
                if ($has_sizes && !empty($size_stock)) {
                    $insertSize = $pdo->prepare('INSERT INTO product_sizes (product_id, size, stock) VALUES (?, ?, ?)');
                    foreach ($size_stock as $sz => $val) {
                        if ($val > 0) {
                            $insertSize->execute([$id, $sz, $val]);
                        }
                    }
                }

                // Optional single image upload on edit
                $upload_dir = 'images/products/';
                if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
                if (isset($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('', true) . '.' . $ext;
                    $path = $upload_dir . $file_name;
                    if (@move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                        $stmt = $pdo->prepare('UPDATE products SET image_path = ? WHERE id = ?');
                        $stmt->execute([$path, $id]);
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
                $stmt->execute([$id]);
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: admin.php');
        exit();
    }
}

// Get all products
$stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            min-height: 100vh;
        }
        
        .admin-header {
            background: #385060;
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.15);
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .admin-header p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .admin-sections {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .add-product-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }
        
        .add-product-section h2 {
            color: #385060;
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #005eb8;
            box-shadow: 0 0 0 3px rgba(0, 94, 184, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-primary {
            background: #385060;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.3);
        }
        
        .inventory-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .inventory-header {
            background: #385060;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .inventory-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .products-table tr:hover {
            background: #f8f9fa;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .product-name {
            font-weight: 600;
            color: #333;
        }
        
        .product-brand {
            color: #666;
            font-size: 0.9rem;
        }
        
        .product-price {
            font-weight: 600;
            color: #005eb8;
        }
        
        .product-category {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stock-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stock-in {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .stock-low {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .stock-out {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit,
        .btn-delete {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: #2196f3;
            color: white;
        }
        
        .btn-edit:hover {
            background: #1976d2;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
        }
        
        .btn-delete:hover {
            background: #d32f2f;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #005eb8;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        /* Responsive design */
        @media (max-width: 1024px) {
            .admin-sections {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }
            
            .admin-header h1 {
                font-size: 2rem;
            }
            
            .products-table {
                font-size: 14px;
            }
            
            .products-table th,
            .products-table td {
                padding: 10px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fa-solid fa-boxes-stacked"></i> Product Management</h1>
            <p>Manage your XSports product inventory with ease</p>
        </div>
        
        <div class="admin-sections">
            <!-- Add Product Section -->
            <div class="add-product-section">
                <h2><i class="fa-solid fa-plus-circle"></i> Add New Product</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="brand">Brand *</label>
                        <input type="text" id="brand" name="brand" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (₹) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Cricket">Cricket</option>
                            <option value="Football">Football</option>
                            <option value="Basketball">Basketball</option>
                            <option value="Tennis">Tennis</option>
                            <option value="Badminton">Badminton</option>
                            <option value="Running">Running</option>
                            <option value="Gym">Gym</option>
                            <option value="Cycling">Cycling</option>
                            <option value="Swimming">Swimming</option>
                            <option value="Yoga">Yoga</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock" min="0" required>
                    </div>

                    <div class="form-group">
                        <label><input type="checkbox" id="has_sizes" name="has_sizes" value="1"> Has sizes (shoe)</label>
                        <div id="sizeInputs" style="margin-top:12px; display:none; border:1px dashed #e9ecef; padding:12px; border-radius:8px;">
                            <p style="margin:0 0 8px 0; font-weight:600;">Enter stock per size:</p>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                <?php $shoe_sizes = ['6','6.5','7','7.5','8','8.5','9','9.5','10'];
                                foreach ($shoe_sizes as $s): ?>
                                    <div>
                                        <label style="display:block;font-size:13px;margin-bottom:6px;"><?php echo $s; ?></label>
                                        <input type="number" name="size_stock[<?php echo $s; ?>]" min="0" value="0" style="width:100%; padding:8px; border:1px solid #e9ecef; border-radius:6px;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin-top:10px;font-size:13px;color:#666;">When sizes are checked, the total stock will be the sum of per-size stocks and will override the Stock Quantity field.</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Product description..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Add Product
                    </button>
                </form>
            </div>
            
            <!-- Inventory Section -->
            <div class="inventory-section">
                <div class="inventory-header">
                    <h2><i class="fa-solid fa-list"></i> Current Inventory</h2>
                </div>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No Products Yet</h3>
                        <p>Start by adding your first product using the form on the left.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                                                    <i class="fa-solid fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                                        </td>
                                        <td class="product-price">₹<?php echo number_format($product['price'], 2); ?></td>
                                        <td><span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span></td>
                                        <td>
                                            <?php
                                                                $stock = $product['quantity'];
                    if ($stock > 10) {
                        echo '<span class="stock-status stock-in">In Stock (' . $stock . ')</span>';
                    } elseif ($stock > 0) {
                        echo '<span class="stock-status stock-low">Low Stock (' . $stock . ')</span>';
                    } else {
                        echo '<span class="stock-status stock-out">Out of Stock</span>';
                    }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php
                                                // fetch sizes for this product to pass to edit modal
                                                $sizeRows = $pdo->prepare('SELECT size, stock FROM product_sizes WHERE product_id = ?');
                                                $sizeRows->execute([$product['id']]);
                                                $sizesArr = $sizeRows->fetchAll();
                                                $sizesJson = htmlspecialchars(json_encode($sizesArr), ENT_QUOTES);
                                                ?>
                                                <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', '<?php echo addslashes($product['brand']); ?>', '<?php echo $product['price']; ?>', '<?php echo addslashes($product['category']); ?>', '<?php echo $product['quantity']; ?>', '<?php echo addslashes($product['description'] ?? ''); ?>', '<?php echo $sizesJson; ?>')">
                                                    <i class="fa-solid fa-edit"></i> Edit
                                                </button>
                                                <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-edit"></i> Edit Product</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_name">Product Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_brand">Brand *</label>
                    <input type="text" id="edit_brand" name="brand" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_price">Price (₹) *</label>
                    <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_category">Category *</label>
                    <select id="edit_category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Cricket">Cricket</option>
                        <option value="Football">Football</option>
                        <option value="Basketball">Basketball</option>
                        <option value="Tennis">Tennis</option>
                        <option value="Badminton">Badminton</option>
                        <option value="Running">Running</option>
                        <option value="Gym">Gym</option>
                        <option value="Cycling">Cycling</option>
                        <option value="Swimming">Swimming</option>
                        <option value="Yoga">Yoga</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_stock">Stock Quantity *</label>
                    <input type="number" id="edit_stock" name="stock" min="0" required>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" id="edit_has_sizes" name="has_sizes" value="1"> Has sizes (shoe)</label>
                    <div id="editSizeInputs" style="margin-top:12px; display:none; border:1px dashed #e9ecef; padding:12px; border-radius:8px;">
                        <p style="margin:0 0 8px 0; font-weight:600;">Enter stock per size:</p>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                            <?php foreach ($shoe_sizes as $s): ?>
                                <div>
                                    <label style="display:block;font-size:13px;margin-bottom:6px;"><?php echo $s; ?></label>
                                    <input type="number" id="edit_size_<?php echo str_replace('.','_',$s); ?>" name="size_stock[<?php echo $s; ?>]" min="0" value="0" style="width:100%; padding:8px; border:1px solid #e9ecef; border-radius:6px;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p style="margin-top:10px;font-size:13px;color:#666;">When sizes are used, total stock will be the sum of sizes and will override Stock Quantity.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_image">Edit Image (leave empty to keep current)</label>
                    <input type="file" id="edit_image" name="image" accept="image/*">
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-primary" style="flex: 1;">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                    <button type="button" onclick="closeModal()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-exclamation-triangle"></i> Confirm Delete</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p>Are you sure you want to delete "<span id="deleteProductName"></span>"?</p>
            <p style="color: #f44336; font-weight: 600;">This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-delete" style="flex: 1; padding: 15px 30px; border-radius: 8px;">
                        <i class="fa-solid fa-trash"></i> Delete Product
                    </button>
                    <button type="button" onclick="closeDeleteModal()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Edit product function
        function editProduct(id, name, brand, price, category, quantity, description, sizesJson) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_brand').value = brand;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_stock').value = quantity;
            document.getElementById('edit_description').value = description;

            // reset size inputs
            document.getElementById('edit_has_sizes').checked = false;
            document.getElementById('editSizeInputs').style.display = 'none';
            <?php foreach ($shoe_sizes as $s): ?>
                document.getElementById('edit_size_<?php echo str_replace('.','_',$s); ?>').value = 0;
            <?php endforeach; ?>

            // populate sizes if provided
            if (sizesJson) {
                try {
                    var sizes = JSON.parse(sizesJson);
                    if (sizes && sizes.length > 0) {
                        document.getElementById('edit_has_sizes').checked = true;
                        document.getElementById('editSizeInputs').style.display = 'block';
                        sizes.forEach(function(r){
                            var id = r.size.toString().replace('.','_');
                            var el = document.getElementById('edit_size_' + id);
                            if (el) el.value = r.stock;
                        });
                    }
                } catch(e) { /* ignore parse errors */ }
            }

            document.getElementById('editModal').style.display = 'block';
        }
        
        // Delete product function
        function deleteProduct(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Close modal functions
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
        
        // show/hide size inputs on add form
        document.getElementById('has_sizes').addEventListener('change', function(){
            document.getElementById('sizeInputs').style.display = this.checked ? 'block' : 'none';
        });

        // show/hide size inputs on edit form
        document.getElementById('edit_has_sizes').addEventListener('change', function(){
            document.getElementById('editSizeInputs').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html> 