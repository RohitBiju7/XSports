<?php
$host = 'localhost';
$db   = 'xsports';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Normalize legacy category labels so UI filters stay in sync
    $pdo->exec("UPDATE products SET category = 'Fitness & Clothing' WHERE LOWER(category) IN ('gym','fitness','fitness & clothing')");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>