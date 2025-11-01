<?php
session_start();
require_once 'includes/db.php';

// Only admin can access
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: signin.php');
    exit;
}

// Fetch statistics
try {
    $totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalProductsInStock = $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM products")->fetchColumn();
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalOrderedProducts = $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM order_items")->fetchColumn();
    $distinctProductsOrdered = (int) $pdo->query("SELECT COUNT(DISTINCT product_id) FROM order_items")->fetchColumn();
    $pendingTickets = (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'pending'")->fetchColumn();

    // Recent orders
    $recentOrdersStmt = $pdo->query("SELECT o.*, (SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id = o.id) AS items_count FROM orders o ORDER BY o.created_at DESC LIMIT 6");
    $recentOrders = $recentOrdersStmt->fetchAll();
} catch (Exception $e) {
    $totalOrders = $totalRevenue = $totalProducts = $totalProductsInStock = $totalOrderedProducts = $distinctProductsOrdered = $pendingTickets = 0;
    $recentOrders = [];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-dashboard {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .admin-title { font-size:24px; color:#1a6fb3; margin:0; }
        .stats-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:22px; }
        .stat-card { background:#fff; padding:18px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.06); text-align:center; }
        .stat-number { font-size:28px; font-weight:700; color:#1a6fb3; }
        .stat-label { color:#555; margin-top:6px; }
        .recent-table { width:100%; border-collapse:collapse; margin-top:12px; }
        .recent-table th, .recent-table td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
        .status-pill { padding:6px 10px; border-radius:999px; font-size:13px; color:#fff; display:inline-block; }
        .status-placed { background:#f0ad4e; }
        .status-processing { background:#3498db; }
        .status-shipped { background:#17a2b8; }
    .status-delivered { background:#28a745; }
    .status-confirmed { background:#2ea44f; }
        .status-cancelled { background:#6c757d; }
        .small-muted { color:#777; font-size:13px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-dashboard">
        <div class="admin-header">
            <h1 class="admin-title"><i class="fa-solid fa-chart-simple"></i> Admin Dashboard</h1>
            <div class="small-muted">Welcome back, Admin</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalOrders); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?php echo number_format((float)$totalRevenue,2); ?></div>
                <div class="stat-label">Total Revenue (non-cancelled)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalProducts); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format((int)$totalOrderedProducts); ?></div>
                <div class="stat-label">Products Ordered (units)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format((int)$distinctProductsOrdered); ?></div>
                <div class="stat-label">Distinct Products Ordered</div>
            </div>
        </div>

        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); margin-bottom:30px;">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format((int)$pendingTickets); ?></div>
                <div class="stat-label">Pending Support Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format((int)($totalOrders ? $totalRevenue / max(1,$totalOrders) : 0),2); ?></div>
                <div class="stat-label">Average Order Value</div>
            </div>
        <!-- Report Date removed as requested -->
        </div>

        <div class="admin-section">
            <h3 style="margin-top:0;">Recent Orders</h3>
            <?php if (empty($recentOrders)): ?>
                <p class="small-muted">No recent orders found.</p>
            <?php else: ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><a href="admin_orders.php?view=<?php echo $o['id']; ?>">#<?php echo $o['id']; ?></a></td>
                                <td><?php echo htmlspecialchars($o['user_id']); ?></td>
                                <td><?php echo (int)$o['items_count']; ?></td>
                                <td>₹<?php echo number_format((float)$o['total'],2); ?></td>
                                <td>
                                    <?php
                                    // If admin has confirmed the order, show Confirmed regardless of status
                                    if (isset($o['admin_confirmed']) && (int)$o['admin_confirmed'] === 1) {
                                        $class = 'status-pill status-confirmed';
                                        $label = 'Confirmed';
                                    } else {
                                        $s = $o['status'];
                                        $class = 'status-pill ' . (
                                            $s === 'placed' ? 'status-placed' : (
                                            $s === 'processing' ? 'status-processing' : (
                                            $s === 'shipped' ? 'status-shipped' : (
                                            $s === 'delivered' ? 'status-delivered' : 'status-cancelled'))));
                                        $label = ucfirst($s);
                                    }
                                    ?>
                                    <span class="<?php echo $class; ?>"><?php echo $label; ?></span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($o['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
