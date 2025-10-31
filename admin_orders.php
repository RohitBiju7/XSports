<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    $action = $_POST['action'];
    $order_id = (int)$_POST['order_id'];

    try {
        $pdo->beginTransaction();

        // Lock the order
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        if (!$order) {
            $pdo->rollBack();
            header('Location: admin_orders.php?msg=notfound');
            exit;
        }

        // Helper to restore stock (used for cancel/reject)
        $restoreStock = function($pdo, $order_id) {
            $itemsStmt = $pdo->prepare('SELECT product_id, quantity, size FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$order_id]);
            $items = $itemsStmt->fetchAll();

            $incrStmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
            $incrSizeStmt = $pdo->prepare('UPDATE product_sizes SET stock = stock + ? WHERE product_id = ? AND size = ?');
            foreach ($items as $it) {
                $incrStmt->execute([(int)$it['quantity'], (int)$it['product_id']]);
                if (!empty($it['size'])) {
                    $incrSizeStmt->execute([(int)$it['quantity'], (int)$it['product_id'], $it['size']]);
                }
            }
        };

        if ($action === 'confirm') {
            // Mark admin_confirmed
            $upd = $pdo->prepare("UPDATE orders SET admin_confirmed = 1, status = 'confirmed' WHERE id = ?");
            $upd->execute([$order_id]);
            $pdo->commit();
            header('Location: admin_orders.php?msg=confirmed');
            exit;
        }

        if ($action === 'reject' || $action === 'cancel') {
            // Mark cancelled and restore stock
            $upd = $pdo->prepare("UPDATE orders SET status = 'cancelled', admin_confirmed = 0 WHERE id = ?");
            $upd->execute([$order_id]);
            // restore stock
            $restoreStock($pdo, $order_id);
            $pdo->commit();
            header('Location: admin_orders.php?msg=cancelled');
            exit;
        }

        if ($action === 'clear') {
            // Permanently remove cancelled order and its items
            // Only allow clearing cancelled orders
            if ($order['status'] !== 'cancelled') {
                $pdo->rollBack();
                header('Location: admin_orders.php?msg=not_cancelled');
                exit;
            }

            $delItems = $pdo->prepare('DELETE FROM order_items WHERE order_id = ?');
            $delItems->execute([$order_id]);
            $delOrder = $pdo->prepare('DELETE FROM orders WHERE id = ?');
            $delOrder->execute([$order_id]);
            $pdo->commit();
            header('Location: admin_orders.php?msg=cleared');
            exit;
        }

        $pdo->rollBack();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header('Location: admin_orders.php?msg=error');
        exit;
    }
}

// Fetch recent orders including user email
$ordersStmt = $pdo->query("SELECT o.*, u.email AS user_email, (SELECT SUM(quantity) FROM order_items oi WHERE oi.order_id = o.id) AS items_count FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$orders = $ordersStmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<style>
    .admin-container { max-width: 1100px; margin: 20px auto; padding: 16px; }
    .order-card { border:1px solid #e5e5e5; margin-bottom:12px; border-radius:6px; }
    .order-head { padding:12px 16px; background:#f7f9fc; display:flex; justify-content:space-between; align-items:center; }
    .order-body { padding:12px 16px; }
    .btn { padding:8px 12px; border-radius:4px; color:#fff; text-decoration:none; }
    .btn-confirm { background:#188038; }
    .btn-reject { background:#c62828; }
    .btn-cancel { background:#666; }
</style>
<div class="admin-container">
    <h2>Incoming Orders</h2>
    <?php if (empty($orders)): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $itemsStmt->execute([$order['id']]);
                $items = $itemsStmt->fetchAll();
            ?>
            <div class="order-card">
                <div class="order-head">
                    <div>
                        <strong>Order #<?php echo $order['id']; ?></strong>
                        <div style="font-size:13px;color:#666;">
                            Placed: <?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?> • <?php echo (int)$order['items_count']; ?> item(s)
                        </div>
                        <div style="font-size:13px;color:#666; margin-top:4px;">By: <?php echo htmlspecialchars($order['user_email'] ?? 'Unknown'); ?></div>
                    </div>
                    <div>
                        <?php if (isset($order['admin_confirmed']) && (int)$order['admin_confirmed'] === 1): ?>
                            <span style="background:#fff4e5;color:#b26a00;padding:4px 8px;border-radius:12px;margin-right:8px;">Confirmed</span>
                        <?php endif; ?>
                        <span style="background:#e8f0fe;color:#1a73e8;padding:4px 8px;border-radius:12px;"><?php echo htmlspecialchars($order['status']); ?></span>
                    </div>
                </div>
                <div class="order-body">
                    <div style="margin-bottom:8px;">Total: ₹<?php echo number_format($order['total'],2); ?></div>
                    <div style="margin-bottom:8px;">Items:</div>
                    <ul>
                        <?php foreach ($items as $it): ?>
                            <li><?php echo htmlspecialchars($it['product_name']); ?> × <?php echo (int)$it['quantity']; ?> <?php echo !empty($it['size']) ? '(Size: '.htmlspecialchars($it['size']).')' : ''; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top:10px;">
                        <?php if (!(isset($order['admin_confirmed']) && (int)$order['admin_confirmed'] === 1)): ?>
                            <form method="post" style="display:inline-block;margin-right:8px;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button class="btn btn-confirm" type="submit">Confirm</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($order['status'] !== 'cancelled'): ?>
                            <form method="post" style="display:inline-block;margin-right:8px;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-reject" type="submit">Reject</button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button class="btn btn-cancel" type="submit">Cancel</button>
                            </form>
                        <?php else: ?>
                            <!-- If cancelled, allow admin to clear the order from the system -->
                            <form method="post" style="display:inline-block;margin-left:8px;">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <input type="hidden" name="action" value="clear">
                                <button class="btn btn-cancel" type="submit">Clear</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
