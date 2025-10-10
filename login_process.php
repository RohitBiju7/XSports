<?php
include('includes/db.php');
session_start();

// Get and sanitize inputs
$email = trim($_POST['email']);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    header("Location: signin.php?login_error=empty_fields");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: signin.php?login_error=invalid_email");
    exit();
}

// Fetch user
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
if ($stmt) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit();
        } else {
            header("Location: signin.php?login_error=wrong_password");
            exit();
        }
    } else {
        header("Location: signin.php?login_error=no_user");
        exit();
    }
} else {
    header("Location: signin.php?login_error=db_error");
    exit();
}

$conn->close();
?>
