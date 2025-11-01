<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

// Check if ticket ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: support.php');
    exit();
}

$ticket_id = (int)$_GET['id'];

// Get ticket details and verify ownership
$stmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.email as user_email 
                       FROM support_tickets t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ? AND t.user_id = ?");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: support.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle message sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $error_message = "Please enter a message.";
    } elseif ($ticket['status'] === 'resolved') {
        $error_message = "This ticket is resolved. You cannot send more messages.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, message) VALUES (?, 'user', ?)");
            if ($stmt->execute([$ticket_id, $message])) {
                $success_message = "Message sent successfully!";
                // Clear the form
                $_POST['message'] = '';
            } else {
                $error_message = "Error sending message. Please try again.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error. Please try again.";
        }
    }
}

// Get all messages for this ticket
$stmt = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticket_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            min-height: 100vh;
        }
        
        .chat-header {
            background: #385060;
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.15);
            position: relative;
        }
        
        .chat-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .chat-header p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .back-button {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.18);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            z-index: 5;
            font-size: 0.95rem;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .ticket-info-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .ticket-info-header {
            background: #385060;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ticket-info-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .ticket-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .ticket-details {
            padding: 25px 30px;
        }
        
        .ticket-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .ticket-meta {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }
        
        .ticket-meta div {
            margin-bottom: 5px;
        }
        
        .ticket-description {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #385060;
            margin-bottom: 20px;
        }
        
        .ticket-description h4 {
            margin: 0 0 10px 0;
            color: #385060;
            font-size: 1rem;
        }
        
        .ticket-description p {
            margin: 0;
            color: #666;
            font-style: italic;
        }
        
        .chat-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .chat-header-section {
            background: #385060;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-header-section h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .chat-messages {
            padding: 30px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .message.admin {
            align-items: flex-start;
        }
        
        .message.user {
            align-items: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 15px 20px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message.admin .message-content {
            background: #385060;
            color: white;
            border-bottom-left-radius: 5px;
        }
        
        .message.user .message-content {
            background: #f1f3f4;
            color: #333;
            border-bottom-right-radius: 5px;
        }
        
        .message-sender {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 5px;
            opacity: 0.8;
        }
        
        .message-text {
            margin: 0;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: left;
        }
        
        .message.user .message-time {
            text-align: right;
        }
        
        .send-message-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .send-message-header {
            background: #385060;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .send-message-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .message-form {
            padding: 25px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #385060;
            box-shadow: 0 0 0 3px rgba(56, 80, 96, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn-send {
            background: #385060;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.3);
        }
        
        .btn-send:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .empty-chat {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-chat i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-chat h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-chat p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .chat-container {
                padding: 15px;
            }
            
            .chat-header h1 {
                font-size: 2rem;
            }
            
            .back-button {
                position: static;
                margin-bottom: 20px;
                width: fit-content;
                transform: none;
            }
            
            .ticket-info-header,
            .chat-header-section,
            .send-message-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .message-content {
                max-width: 85%;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="chat-container">
        <div class="chat-header">
            <a href="support.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <h1><i class="fa-solid fa-comments"></i> Support Chat</h1>
            <p>Support conversation for ticket #<?php echo $ticket_id; ?></p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fa-solid fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Ticket Information Section -->
        <div class="ticket-info-section">
            <div class="ticket-info-header">
                <h2><i class="fa-solid fa-ticket"></i> Ticket Information</h2>
                <div class="ticket-status">
                    <?php echo ucfirst($ticket['status']); ?>
                </div>
            </div>
            
            <div class="ticket-details">
                <div class="ticket-title"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                
                <div class="ticket-meta">
                    <div><strong>Ticket #<?php echo $ticket['id']; ?></strong></div>
                    <div>Created: <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></div>
                </div>
                
                <div class="ticket-description">
                    <h4><i class="fa-solid fa-info-circle"></i> Description</h4>
                    <p><?php echo htmlspecialchars($ticket['description']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Chat Messages Section -->
        <div class="chat-section">
            <div class="chat-header-section">
                <h2><i class="fa-solid fa-comments"></i> Conversation</h2>
            </div>
            
            <div class="chat-messages">
                <?php if (empty($messages)): ?>
                    <div class="empty-chat">
                        <i class="fa-solid fa-comment-slash"></i>
                        <h3>No Messages Yet</h3>
                        <p>Start the conversation by sending a message below.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['sender_type']; ?>">
                            <div class="message-content">
                                <div class="message-sender">
                                    <?php echo ucfirst($message['sender_type']); ?>
                                </div>
                                <div class="message-text">
                                    <?php echo htmlspecialchars($message['message']); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Send Message Section -->
        <div class="send-message-section">
            <div class="send-message-header">
                <h2><i class="fa-solid fa-paper-plane"></i> Send Message</h2>
            </div>
            
            <form method="POST" class="message-form">
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" placeholder="Type your message here..." required <?php echo ($ticket['status'] === 'resolved') ? 'disabled' : ''; ?>><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-send" <?php echo ($ticket['status'] === 'resolved') ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-paper-plane"></i> Send Message
                    </button>
                    
                    <?php if ($ticket['status'] === 'resolved'): ?>
                        <span style="color: #dc3545; font-size: 0.9rem;">
                            <i class="fa-solid fa-lock"></i> This ticket is resolved. No more messages can be sent.
                        </span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 