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
if ($order_id <= 0) { header('Location: orders.php'); exit; }

try {
    $pdo->beginTransaction();

    // Ensure the order belongs to the user and is cancelled before hard delete
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    if (!$order || $order['status'] !== 'cancelled') {
        $pdo->rollBack();
        header('Location: orders.php');
        exit;
    }

    // Delete child items then order
    $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$order_id]);
    $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$order_id]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}

header('Location: orders.php');
exit;
?>


