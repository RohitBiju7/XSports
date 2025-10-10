<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify order belongs to user and is cancellable
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order || $order['status'] === 'cancelled') {
        $pdo->rollBack();
        header('Location: orders.php');
        exit;
    }

    // Restore stock for each item
    $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll();

    $incrStmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
    foreach ($items as $it) {
        $incrStmt->execute([$it['quantity'], $it['product_id']]);
    }

    // Mark order cancelled
    $upd = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $upd->execute([$order_id]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}

header('Location: orders.php');
exit;
?>


