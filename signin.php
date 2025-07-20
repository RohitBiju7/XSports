<?php
// Start the session to store user data
session_start();

// Placeholder for database connection
// require_once 'includes/db_connect.php'; 

$login_error = '';
$signup_error = '';
$signup_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which form was submitted
    if (isset($_POST['form_type']) && $_POST['form_type'] == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // --- DATABASE LOGIN LOGIC ---
        // Here you would query your database to find the user
        // For example: $sql = "SELECT id, password_hash FROM users WHERE email = ?";
        // If user is found and password_verify($password, $password_hash) is true:
        // $_SESSION['user_id'] = $user_id;
        // header("Location: index.php");
        // exit();
        // -----------------------------

        // Placeholder logic
        if ($email === 'test@example.com' && $password === 'password') {
            $_SESSION['user_email'] = $email;
            header("Location: index.php");
            exit();
        } else {
            $login_error = "Invalid email or password.";
        }

    } elseif (isset($_POST['form_type']) && $_POST['form_type'] == 'signup') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        if (!empty($name) && !empty($email) && !empty($password)) {
            $signup_success = "Account created successfully! Please login.";
        } else {
            $signup_error = "Please fill in all fields.";
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
        <form id="login-form" method="POST" action="login_process.php">
          <input type="hidden" name="form_type" value="login">
          <h2>Login</h2>
          <div class="tab-switch">
            <div class="active">E-mail</div>
          </div>
          <?php if ($login_error): ?>
            <p class="error-message"><?php echo $login_error; ?></p>
          <?php endif; ?>
          <?php if ($signup_success): ?>
            <p class="success-message"><?php echo $signup_success; ?></p>
          <?php endif; ?>
          <input type="email" name="email" placeholder="Enter your email address" required>
          <input type="password" name="password" placeholder="Enter your password" required>
          <button type="submit">LOGIN</button>
          <div class="toggle-link">
            <span>No account? <a onclick="switchToSignup()">Create your XSports account</a></span>
          </div>
        </form>

        <!-- Signup Form -->
        <form id="signup-form" method="POST" action="signup_process.php" style="display: none;">
          <input type="hidden" name="form_type" value="signup">
          <h2>Let's go!</h2>
          <div class="tab-switch">
            <div class="active">E-mail</div>
          </div>
           <?php if ($signup_error): ?>
            <p class="error-message"><?php echo $signup_error; ?></p>
          <?php endif; ?>
          <input type="text" name="username" placeholder="Enter your name" required>
          <input type="email" name="email" placeholder="Enter your email address" required>
          <input type="password" name="password" placeholder="Create a password" required>
    <div class="form-group">
        <input type="password" name="confirm_password" placeholder="Confirm your password" required>
    </div>
          <button type="submit">CREATE ACCOUNT</button>
          <div class="toggle-link">
            <span>Already have an account? <a onclick="switchToLogin()">Login</a></span>
          </div>
        </form>

        <ul class="info">
          <li>✔ Exclusive Deals and Sporty Rewards</li>
          <li>✔ Personalised Experiences</li>
          <li>✔ Faster Checkout</li>
          <li>✔ Easy Return/Exchange</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');

    function switchToSignup() {
      loginForm.style.display = 'none';
      signupForm.style.display = 'block';
    }

    function switchToLogin() {
      signupForm.style.display = 'none';
      loginForm.style.display = 'block';
    }

    // If there was a signup error, show the signup form on page load
    <?php if ($signup_error): ?>
      switchToSignup();
    <?php endif; ?>
  </script>
</body>
</html>