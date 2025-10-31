<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch orders with item counts
$stmt = $pdo->prepare('SELECT o.*, (SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id = o.id) AS items_count FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<style>
    .orders-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
    .order-card { border: 1px solid #e5e5e5; margin-bottom: 16px; border-radius: 6px; overflow: hidden; }
    .order-header { background: #f7f9fc; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
    .order-body { padding: 16px; }
    .order-meta { color: #555; font-size: 14px; }
    .status { padding: 4px 10px; border-radius: 999px; font-size: 12px; text-transform: capitalize; }
    .status.placed { background: #e8f0fe; color: #1a73e8; }
    .status.processing { background: #fff4e5; color: #b26a00; }
    .status.shipped { background: #e6f4ea; color: #188038; }
    .status.delivered { background: #e6f4ea; color: #0f9d58; }
    .status.cancelled { background: #fde7e9; color: #c5221f; }
    .empty { text-align: center; padding: 60px 20px; }
    .btn { display: inline-block; padding: 10px 14px; background: #005eb8; color: #fff; text-decoration: none; border-radius: 4px; }
    .order-items { margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px; }
    .order-item { display: flex; align-items: center; padding: 8px 0; }
    .order-item img { width: 48px; height: 48px; object-fit: cover; margin-right: 12px; }
    .order-summary { font-weight: bold; }
</style>
<script>
    function toggleItems(id) {
        var el = document.getElementById('items_'+id);
        if (el) { el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none'; }
    }
</script>

<div class="orders-container">
    <h2>My Orders</h2>
    <?php if (isset($_GET['placed'])): ?>
        <p style="color: #188038;">Your order has been placed successfully.</p>
    <?php endif; ?>
    <?php if (isset($_GET['cannot_cancel'])): ?>
        <p style="color: #b26a00;">This order has already been confirmed by admin and cannot be cancelled.</p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty">
            <p>No orders yet.</p>
            <a href="index.php" class="btn">Start Shopping</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $itemsStmt->execute([$order['id']]);
                $items = $itemsStmt->fetchAll();
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <strong>Order #<?php echo $order['id']; ?></strong>
                        <div class="order-meta">Placed on <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?> • <?php echo (int)($order['items_count'] ?? 0); ?> item(s)</div>
                    </div>
                    <div>
                        <span class="status <?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                    </div>
                </div>
                <div class="order-body">
                        <div class="order-summary">Total: ₹<?php echo number_format($order['total'], 2); ?> (Subtotal ₹<?php echo number_format($order['subtotal'], 2); ?> • Shipping ₹<?php echo number_format($order['shipping'], 2); ?> • Tax ₹<?php echo number_format($order['tax'], 2); ?>)</div>
                    <a href="javascript:void(0)" onclick="toggleItems(<?php echo $order['id']; ?>)">View items</a>
                    <?php if ($order['status'] !== 'cancelled'): ?>
                        <?php if (!(isset($order['admin_confirmed']) && (int)$order['admin_confirmed'] === 1)): ?>
                            <form method="post" action="order_cancel.php" style="display:inline-block;margin-left:10px;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <button type="submit" class="btn" style="background:#c62828">Cancel order</button>
                            </form>
                        <?php else: ?>
                            <span style="margin-left:10px;color:#b26a00;">Admin confirmed — cannot cancel</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" action="order_delete.php" style="display:inline-block;margin-left:10px;">
                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                            <button type="submit" class="btn" style="background:#555">Remove</button>
                        </form>
                    <?php endif; ?>
                    <div id="items_<?php echo $order['id']; ?>" class="order-items" style="display:none;">
                        <?php foreach ($items as $it): ?>
                            <div class="order-item">
                                <img src="<?php echo $it['image_path'] ?: 'images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($it['product_name']); ?>">
                                <div>
                                    <div><?php echo htmlspecialchars($it['product_name']); ?> <span style="color:#666;">× <?php echo (int)$it['quantity']; ?></span></div>
                                    <div style="color:#666; font-size:14px;">Brand: <?php echo htmlspecialchars($it['brand']); ?> • ₹<?php echo number_format($it['price'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
 


