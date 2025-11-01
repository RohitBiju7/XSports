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
                $stock = (isset($_POST['stock']) && $_POST['stock'] !== '') ? (int)$_POST['stock'] : 0;
                $description = $_POST['description'] ?? '';
                // Support both shoe sizes and apparel sizes (S,M,L...)
                $has_sizes = (isset($_POST['has_sizes']) || isset($_POST['has_apparel_sizes'])) ? 1 : 0;

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
                $stock = (isset($_POST['stock']) && $_POST['stock'] !== '') ? (int)$_POST['stock'] : 0;
                $description = $_POST['description'] ?? '';
                // Support both shoe sizes and apparel sizes
                $has_sizes = (isset($_POST['has_sizes']) || isset($_POST['has_apparel_sizes'])) ? 1 : 0;

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

// Inventory filters: search, category, pagination
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterCategory = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

$baseSql = 'FROM products';
$conditions = [];
$params = [];

if ($searchQuery !== '') {
    $conditions[] = '(name LIKE ? OR brand LIKE ?)';
    $wild = '%' . $searchQuery . '%';
    $params[] = $wild;
    $params[] = $wild;
}

if ($filterCategory !== 'all' && $filterCategory !== '') {
    $conditions[] = 'category = ?';
    $params[] = $filterCategory;
}

if ($conditions) {
    $baseSql .= ' WHERE ' . implode(' AND ', $conditions);
}

// Count total filtered records
$countStmt = $pdo->prepare('SELECT COUNT(*) ' . $baseSql);
$countStmt->execute($params);
$totalProductsCount = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalProductsCount / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;

$listStmt = $pdo->prepare('SELECT * ' . $baseSql . ' ORDER BY id DESC LIMIT ? OFFSET ?');
foreach ($params as $idx => $val) {
    $listStmt->bindValue($idx + 1, $val);
}
$listStmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$listStmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$listStmt->execute();
$products = $listStmt->fetchAll();

$categoryListStmt = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category ASC');
$inventoryCategories = $categoryListStmt->fetchAll(PDO::FETCH_COLUMN);
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
            flex-direction: column;
            gap: 16px;
        }
        
        .inventory-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .inventory-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .inventory-filters input[type="text"],
        .inventory-filters select {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d9d9d9;
            font-size: 14px;
            min-width: 220px;
        }

        .inventory-filters button {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            background: #005eb8;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .inventory-filters button:hover {
            background: #024c95;
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
            margin: 20px auto;
            padding: 0; /* inner sections will handle padding */
            border-radius: 15px;
            width: 90%;
            max-width: 560px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
            overflow: hidden; /* body will scroll instead */
        }

        /* Ensure the immediate form child fills modal so .modal-body can flex/scroll */
        .modal-content > form {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
        }

        /* Modal body should scroll when content exceeds available space */
        .modal-body {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1 1 auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #f1f1f1;
            display: flex;
            gap: 10px;
            flex: 0 0 auto;
            background: #fff;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #f1f1f1;
            flex: 0 0 auto;
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
            .inventory-header {
                align-items: flex-start;
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

        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 12px 0;
            padding: 0 30px;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pagination-controls button {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #d0d7de;
            background: #fff;
            color: #2d3748;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .pagination-controls button:hover {
            background: #f0f6ff;
        }

        .pagination-controls button.active {
            background: #005eb8;
            color: #fff;
            border-color: #005eb8;
        }

        .pagination-input {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pagination-input input {
            width: 60px;
            padding: 6px 8px;
            border: 1px solid #d0d7de;
            border-radius: 6px;
        }

        .pagination-input button {
            padding: 6px 12px;
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
                            <option value="Running">Running</option>
                            <option value="Fitness &amp; Clothing">Fitness &amp; Clothing</option>
                            <option value="Football">Football</option>
                            <option value="Badminton">Badminton</option>
                            <option value="Tennis">Tennis</option>
                            <option value="Cycling">Cycling</option>
                            <option value="Swimming">Swimming</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" id="stock" name="stock" min="0" placeholder="Leave empty if using per-size stock">
                    </div>

                    <div class="form-group">
                        <label><input type="checkbox" id="has_sizes" name="has_sizes" value="1"> Has sizes (shoe)</label>
                        <div id="sizeInputs" style="margin-top:12px; display:none; border:1px dashed #e9ecef; padding:12px; border-radius:8px;">
                            <p style="margin:0 0 8px 0; font-weight:600;">Enter stock per shoe size:</p>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                <?php $shoe_sizes = ['6','6.5','7','7.5','8','8.5','9','9.5','10'];
                                foreach ($shoe_sizes as $s): ?>
                                    <div>
                                        <label style="display:block;font-size:13px;margin-bottom:6px;"><?php echo $s; ?></label>
                                        <input type="number" name="size_stock[<?php echo $s; ?>]" min="0" value="0" style="width:100%; padding:8px; border:1px solid #e9ecef; border-radius:6px;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin-top:10px;font-size:13px;color:#666;">When sizes are used, the total stock will be the sum of per-size stocks and will override the Stock Quantity field.</p>
                        </div>
                        
                        <label style="display:block;margin-top:12px;"><input type="checkbox" id="has_apparel_sizes" name="has_apparel_sizes" value="1"> Has sizes (shirt/pants)</label>
                        <div id="apparelSizeInputs" style="margin-top:12px; display:none; border:1px dashed #e9ecef; padding:12px; border-radius:8px;">
                            <p style="margin:0 0 8px 0; font-weight:600;">Enter stock per apparel size (S, M, L...):</p>
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                <?php $apparel_sizes = ['S','M','L','XL','2XL','3XL']; foreach ($apparel_sizes as $as): ?>
                                    <div>
                                        <label style="display:block;font-size:13px;margin-bottom:6px;"><?php echo $as; ?></label>
                                        <input type="number" name="size_stock[<?php echo $as; ?>]" min="0" value="0" style="width:100%; padding:8px; border:1px solid #e9ecef; border-radius:6px;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin-top:10px;font-size:13px;color:#666;">When sizes are used, the total stock will be the sum of per-size stocks and will override the Stock Quantity field.</p>
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
                <form class="inventory-filters" method="get">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by product or brand">
                    <select name="category">
                        <option value="all" <?php echo $filterCategory === 'all' ? 'selected' : ''; ?>>Show All Categories</option>
                        <?php foreach ($inventoryCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Apply</button>
                    <?php if ($searchQuery !== '' || ($filterCategory !== 'all' && $filterCategory !== '')): ?>
                        <a href="admin.php" style="color:#fff;text-decoration:underline;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
                
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No Products Yet</h3>
                        <p>Start by adding your first product using the form on the left.</p>
                    </div>
                <?php else: ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Showing
                            <?php
                                $start = $offset + 1;
                                $end = min($offset + $perPage, $totalProductsCount);
                                echo $totalProductsCount ? "{$start}-{$end}" : '0';
                            ?>
                            of <?php echo number_format($totalProductsCount); ?> product(s)
                        </div>
                        <?php include __DIR__ . '/partials/pagination.php'; ?>
                    </div>
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
                                                $productPayload = [
                                                    'id' => (int)$product['id'],
                                                    'name' => $product['name'],
                                                    'brand' => $product['brand'],
                                                    'price' => (float)$product['price'],
                                                    'category' => $product['category'],
                                                    'quantity' => (int)$product['quantity'],
                                                    'description' => $product['description'] ?? '',
                                                    'sizes' => $sizesArr
                                                ];
                                                $productJson = htmlspecialchars(json_encode($productPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <button type="button" class="btn-edit" data-product="<?php echo $productJson; ?>">
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
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Showing
                            <?php
                                $start = $offset + 1;
                                $end = min($offset + $perPage, $totalProductsCount);
                                echo $totalProductsCount ? "{$start}-{$end}" : '0';
                            ?>
                            of <?php echo number_format($totalProductsCount); ?> product(s)
                        </div>
                        <?php include __DIR__ . '/partials/pagination.php'; ?>
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
                <div class="modal-body" style="padding:20px 24px; overflow-y:auto;">
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
                        <option value="Running">Running</option>
                        <option value="Fitness &amp; Clothing">Fitness &amp; Clothing</option>
                        <option value="Football">Football</option>
                        <option value="Badminton">Badminton</option>
                        <option value="Tennis">Tennis</option>
                        <option value="Cycling">Cycling</option>
                        <option value="Swimming">Swimming</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_stock">Stock Quantity</label>
                    <input type="number" id="edit_stock" name="stock" min="0" placeholder="Leave empty if using per-size stock">
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
                    <label><input type="checkbox" id="edit_has_apparel_sizes" name="has_apparel_sizes" value="1"> Has sizes (shirt/pants)</label>
                    <div id="editApparelSizeInputs" style="margin-top:12px; display:none; border:1px dashed #e9ecef; padding:12px; border-radius:8px;">
                        <p style="margin:0 0 8px 0; font-weight:600;">Enter stock per apparel size:</p>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                            <?php foreach ($apparel_sizes as $as): ?>
                                <div>
                                    <label style="display:block;font-size:13px;margin-bottom:6px;">
                                        <?php echo $as; ?>
                                    </label>
                                    <input type="number" id="edit_size_<?php echo $as; ?>" name="size_stock[<?php echo $as; ?>]" min="0" value="0" style="width:100%; padding:8px; border:1px solid #e9ecef; border-radius:6px;">
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
                </div> <!-- /.modal-body -->
                <div class="modal-footer" style="padding:16px 24px;border-top:1px solid #f1f1f1;display:flex;gap:10px;flex:0 0 auto;">
                    <button type="submit" class="btn-primary" style="flex: 1;padding:12px 18px;">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                    <button type="button" onclick="closeModal()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 12px 18px; border-radius: 8px; cursor: pointer;">
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
            <div class="modal-body" style="padding:20px 24px; overflow-y:auto;">
                <p>Are you sure you want to delete "<span id="deleteProductName"></span>"?</p>
                <p style="color: #f44336; font-weight: 600;">This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-footer" style="padding:16px 24px;border-top:1px solid #f1f1f1;display:flex;gap:10px;flex:0 0 auto;">
                    <button type="submit" class="btn-delete" style="flex: 1; padding: 12px 18px; border-radius: 8px;">
                        <i class="fa-solid fa-trash"></i> Delete Product
                    </button>
                    <button type="button" onclick="closeDeleteModal()" style="flex: 1; background: #6c757d; color: white; border: none; padding: 12px 18px; border-radius: 8px; cursor: pointer;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Wire up edit buttons after DOM ready
        document.querySelectorAll('.btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var payload = btn.getAttribute('data-product');
                if (!payload) return;
                try {
                    var product = JSON.parse(payload);
                    openEditModal(product);
                } catch (e) {
                    console.error('Failed to parse product payload', e);
                }
            });
        });

        function openEditModal(product) {
            if (!product) return;
            document.getElementById('edit_id').value = product.id || '';
            document.getElementById('edit_name').value = product.name || '';
            document.getElementById('edit_brand').value = product.brand || '';
            document.getElementById('edit_price').value = product.price || '';
            document.getElementById('edit_category').value = product.category || '';
            document.getElementById('edit_stock').value = product.quantity || 0;
            document.getElementById('edit_description').value = product.description || '';

            // reset size inputs (shoe)
            document.getElementById('edit_has_sizes').checked = false;
            document.getElementById('editSizeInputs').style.display = 'none';
            <?php foreach ($shoe_sizes as $s): ?>
                document.getElementById('edit_size_<?php echo str_replace('.','_',$s); ?>').value = 0;
            <?php endforeach; ?>
            // reset apparel size inputs
            document.getElementById('edit_has_apparel_sizes').checked = false;
            document.getElementById('editApparelSizeInputs').style.display = 'none';
            <?php foreach ($apparel_sizes as $as): ?>
                document.getElementById('edit_size_<?php echo $as; ?>').value = 0;
            <?php endforeach; ?>

            // populate sizes if provided
            if (Array.isArray(product.sizes) && product.sizes.length > 0) {
                var shoeFound = false;
                var apparelFound = false;
                product.sizes.forEach(function(r){
                    if (!r || typeof r.size === 'undefined') return;
                    var key = r.size.toString();
                    var shoeId = 'edit_size_' + key.replace('.', '_');
                    var el = document.getElementById(shoeId);
                    if (el) {
                        el.value = r.stock || 0;
                        shoeFound = true;
                        return;
                    }
                    var apparelEl = document.getElementById('edit_size_' + key);
                    if (apparelEl) {
                        apparelEl.value = r.stock || 0;
                        apparelFound = true;
                    }
                });
                if (shoeFound) {
                    document.getElementById('edit_has_sizes').checked = true;
                    document.getElementById('editSizeInputs').style.display = 'block';
                }
                if (apparelFound) {
                    document.getElementById('edit_has_apparel_sizes').checked = true;
                    document.getElementById('editApparelSizeInputs').style.display = 'block';
                }
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
        
        // show/hide size inputs on add form (shoe)
        document.getElementById('has_sizes').addEventListener('change', function(){
            document.getElementById('sizeInputs').style.display = this.checked ? 'block' : 'none';
        });
        // show/hide apparel size inputs on add form
        document.getElementById('has_apparel_sizes').addEventListener('change', function(){
            document.getElementById('apparelSizeInputs').style.display = this.checked ? 'block' : 'none';
        });

        // show/hide size inputs on edit form (shoe)
        document.getElementById('edit_has_sizes').addEventListener('change', function(){
            document.getElementById('editSizeInputs').style.display = this.checked ? 'block' : 'none';
        });
        // show/hide apparel size inputs on edit form
        document.getElementById('edit_has_apparel_sizes').addEventListener('change', function(){
            document.getElementById('editApparelSizeInputs').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html> 