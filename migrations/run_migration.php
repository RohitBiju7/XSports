<?php
// migrations/run_migration.php
// Ensure 'admin_confirmed' exists on orders table
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../includes/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'admin_confirmed'");
    $col = $stmt->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN admin_confirmed TINYINT(1) DEFAULT 0");
        echo "Added admin_confirmed column to orders\n";
    } else {
        echo "admin_confirmed column already exists on orders\n";
    }
} catch (PDOException $e) {
    echo "Error checking/adding admin_confirmed: " . $e->getMessage() . "\n";
}

echo "Done.\n";

exit;
