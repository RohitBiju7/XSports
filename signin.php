<?php
session_start();
require_once 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_logged_in'])) {
    if (isset($_SESSION['admin_logged_in'])) {
        header('Location: index.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$login_error = '';
$signup_error = '';
$signup_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which form was submitted
    if (isset($_POST['form_type']) && $_POST['form_type'] == 'login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $login_error = "Please fill in all fields.";
        } else {
            // First check if it's an admin login
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Admin login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_email'] = $admin['email'];
                header("Location: index.php");
                exit();
            } else {
                // Check if it's a regular user login
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // User login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
            header("Location: index.php");
            exit();
        } else {
                    $login_error = "Invalid email/username or password.";
                }
            }
        }

    } elseif (isset($_POST['form_type']) && $_POST['form_type'] == 'signup') {
        $name = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            $signup_error = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signup_error = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $signup_error = "Password must be at least 6 characters long.";
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $signup_error = "Email already registered. Please login instead.";
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    $signup_success = "Account created successfully! Please login.";
                } else {
                    $signup_error = "Error creating account. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login / Signup - XSports</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1.5rem;
      border-bottom: 1px solid #eee;
      background-color: white;
    }

    .back-link {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: #000;
      font-size: 0.95rem;
      flex: 1;
    }

    .back-link img {
      width: 20px;
      margin-right: 6px;
    }

    .decathlon-logo {
      flex: 1;
      display: flex;
      justify-content: center;
    }

    .decathlon-logo img {
      height: 75px;
    }

    .main-content {
      display: flex;
      flex: 1;
    }

    .login-left {
      flex: 1;
      background: url('images/sign_side.webp') no-repeat center center;
      background-size: cover;
    }

    .login-right {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 4rem;
    }

    .form-container {
      max-width: 500px;
      margin: auto;
    }

    .form-container h2 {
      margin-bottom: 1rem;
      font-size: 1.6rem;
    }

    .tab-switch {
      display: flex;
      margin-bottom: 1rem;
    }

    .tab-switch div {
      padding: 0.5rem 1rem;
      font-weight: bold;
      color: #2c2dc1;
      border-bottom: 2px solid #2c2dc1;
    }

    .form-container input {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }

    .form-container button {
      width: 100%;
      padding: 0.75rem;
      background-color: #2c2dc1;
      color: white;
      border: none;
      font-weight: bold;
      cursor: pointer;
      border-radius: 4px;
    }

    .info {
      font-size: 0.9rem;
      color: #555;
    }

    .info li {
      margin: 0.4rem 0;
    }

    .toggle-link {
      margin-top: 1rem;
      font-size: 0.9rem;
    }

    .toggle-link a {
      color: #2c2dc1;
      text-decoration: none;
      cursor: pointer;
    }

    .error-message {
        color: red;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .success-message {
        color: green;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .admin-note {
        background: #f0f8ff;
        border: 1px solid #005eb8;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
      .main-content {
        flex-direction: column;
      }

      .login-left {
        height: 200px;
      }
    }
  </style>
</head>
<body>
  <header>
    <a href="index.php" class="back-link">
      <img src="https://cdn-icons-png.flaticon.com/512/25/25694.png" alt="Home">
      Back
    </a>
    <div class="decathlon-logo">
      <img src="images/logo.png" alt="XSports Logo">
    </div>
    <div style="flex: 1;"></div> <!-- Spacer to balance flex -->
  </header>

  <div class="main-content">
    <div class="login-left"></div>
    <div class="login-right">
      <div class="form-container">
        
        <!-- Login Form -->
        <form id="login-form" method="POST">
          <input type="hidden" name="form_type" value="login">
          <h2>Login</h2>
          <div class="tab-switch">
            <div class="active">E-mail / Username</div>
          </div>

          <?php if ($login_error): ?>
            <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
          <?php endif; ?>
          <?php if ($signup_success): ?>
            <p class="success-message"><?php echo htmlspecialchars($signup_success); ?></p>
          <?php endif; ?>
          <input type="text" name="email" placeholder="Enter your email or admin username" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
          <input type="password" name="password" placeholder="Enter your password" required>
          <button type="submit">LOGIN</button>
          <div class="toggle-link">
            <span>No account? <a onclick="switchToSignup()">Create your XSports account</a></span>
          </div>
        </form>

        <!-- Signup Form -->
        <form id="signup-form" method="POST" style="display: none;">
          <input type="hidden" name="form_type" value="signup">
          <h2>Let's go!</h2>
          <div class="tab-switch">
            <div class="active">E-mail</div>
          </div>
           <?php if ($signup_error): ?>
            <p class="error-message"><?php echo htmlspecialchars($signup_error); ?></p>
          <?php endif; ?>
          <input type="text" name="username" placeholder="Enter your name" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
          <input type="email" name="email" placeholder="Enter your email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
          <input type="password" name="password" placeholder="Create a password" required>
          <button type="submit">CREATE ACCOUNT</button>
          <div class="toggle-link">
            <span>Already have an account? <a onclick="switchToLogin()">Login</a></span>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function switchToSignup() {
      document.getElementById('login-form').style.display = 'none';
      document.getElementById('signup-form').style.display = 'block';
    }

    function switchToLogin() {
      document.getElementById('signup-form').style.display = 'none';
      document.getElementById('login-form').style.display = 'block';
    }
  </script>
</body>
</html>