<?php
include('includes/db.php');
session_start();

// Get and sanitize inputs
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Input validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    header("Location: signin.php?signup_error=empty_fields");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: signin.php?signup_error=invalid_email");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: signin.php?signup_error=password_mismatch");
    exit();
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user using prepared statements
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $stmt->insert_id;
        header("Location: index.php");
        exit();
    } else {
        header("Location: signin.php?signup_error=duplicate_email");
        exit();
    }
} else {
    header("Location: signin.php?signup_error=db_error");
    exit();
}

$conn->close();
?>