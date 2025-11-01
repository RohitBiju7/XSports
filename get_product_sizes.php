<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'missing_id']);
    exit;
}

$product_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare('SELECT size, stock FROM product_sizes WHERE product_id = ? ORDER BY id ASC');
    $stmt->execute([$product_id]);
    $rows = $stmt->fetchAll();

    // Return sizes and stock
    echo json_encode(['sizes' => $rows]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['error' => 'db_error']);
    exit;
}

?>
