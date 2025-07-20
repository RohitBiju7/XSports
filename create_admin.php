<?php
require_once 'includes/db.php';

// Create admin user (DELETE THIS FILE AFTER USE)
$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin already exists
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    
    if (!$stmt->fetch()) {
        // Insert admin user
        $stmt = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
        $stmt->execute([$username, $hashed_password]);
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<strong>IMPORTANT: Delete this file (create_admin.php) after use!</strong>";
    } else {
        echo "Admin user already exists!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 