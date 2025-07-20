<?php
// includes/header.php is expected to start the session
include 'includes/header.php';

$message_sent = false;
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($_POST["message"]));

    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // In a real application, you would send an email or save the data.
        // For this example, we'll just simulate a successful submission.
        // Example: mail("support@xsports.com", "Support Request from $name", $message, "From: $email");
        $message_sent = true;
    }
}
?>

<div class="support-container">
    <h2>Contact Support</h2>
    <p>Have a question or need help? Fill out the form below and we'll get back to you as soon as possible.</p>

    <?php if ($message_sent): ?>
        <div class="alert alert-success">
            <strong>Thank you!</strong> Your message has been sent successfully. We will get back to you shortly.
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!$message_sent): ?>
    <form action="support.php" method="post">
        <div class="form-group">
            <label for="name">Your Name:</label>
            <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="email">Your Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="message">Your Message:</label>
            <textarea id="message" name="message" rows="6" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn">Send Message</button>
    </form>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>