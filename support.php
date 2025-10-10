<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

// Database connection
require_once 'includes/db.php';

$success_message = '';
$error_message = '';

// Handle ticket creation and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete_ticket') {
        // Handle ticket deletion
        $ticket_id = (int)$_POST['ticket_id'];
        
        // Verify ticket ownership
        $check_stmt = $pdo->prepare('SELECT id FROM support_tickets WHERE id = ? AND user_id = ?');
        $check_stmt->execute([$ticket_id, $_SESSION['user_id']]);
        
        if ($check_stmt->fetch()) {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Delete all messages for this ticket first
                $stmt = $pdo->prepare('DELETE FROM support_messages WHERE ticket_id = ?');
                $stmt->execute([$ticket_id]);
                
                // Delete the ticket itself
                $stmt = $pdo->prepare('DELETE FROM support_tickets WHERE id = ?');
                $stmt->execute([$ticket_id]);
                
                // Commit transaction
                $pdo->commit();
                $success_message = "Support ticket deleted successfully!";
            } catch (PDOException $e) {
                // Rollback on error
                $pdo->rollBack();
                $error_message = "Error deleting ticket. Please try again.";
            }
        } else {
            $error_message = "You don't have permission to delete this ticket.";
        }
    } else {
        // Handle ticket creation
        $subject = trim($_POST['subject']);
        $description = trim($_POST['description']);
        
        if (empty($subject) || empty($description)) {
            $error_message = "Please fill in all required fields.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, description, status) VALUES (?, ?, ?, 'pending')");
                if ($stmt->execute([$_SESSION['user_id'], $subject, $description])) {
                    $ticket_id = $pdo->lastInsertId();
                    
                    // Insert the first message
                    $stmt = $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, message) VALUES (?, 'user', ?)");
                    $stmt->execute([$ticket_id, $description]);
                    
                    $success_message = "Support ticket created successfully!";
                    // Clear form
                    $_POST['subject'] = '';
                    $_POST['description'] = '';
                } else {
                    $error_message = "Error creating ticket. Please try again.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error. Please try again.";
            }
        }
    }
}

// Get user's tickets
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) as message_count,
           (SELECT message FROM support_messages WHERE ticket_id = t.id ORDER BY created_at ASC LIMIT 1) as first_message
    FROM support_tickets t 
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .support-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .support-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #385060;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .support-header p {
            font-size: 1.1rem;
            color: #666;
            margin: 0;
        }
        
        .support-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .ticket-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .ticket-form h3 {
            margin-bottom: 25px;
            color: #385060;
            font-size: 1.4rem;
            font-weight: 600;
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
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #385060;
            box-shadow: 0 0 0 3px rgba(56, 80, 96, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: #385060;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.3);
        }
        
        .tickets-list {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .tickets-list h3 {
            margin-bottom: 25px;
            color: #385060;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .ticket-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .ticket-item:hover {
            border-color: #385060;
            box-shadow: 0 4px 15px rgba(56, 80, 96, 0.1);
        }
        
        .ticket-item:last-child {
            margin-bottom: 0;
        }
        
        .ticket-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .ticket-subject {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }
        
        .ticket-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-active {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .ticket-date {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .ticket-description {
            color: #555;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 15px;
        }
        
        .ticket-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-view:hover {
            background: #218838;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
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
        
        .no-tickets {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px 20px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .support-container {
                padding: 20px 15px;
            }
            
            .support-header h1 {
                font-size: 2rem;
            }
            
            .support-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .ticket-title {
                flex-direction: column;
                gap: 10px;
            }
            
            .ticket-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="support-container">
        <div class="support-header">
            <h1>Support Center</h1>
            <p>Create a new support ticket or view your existing tickets</p>
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
        
        <div class="support-content">
            <!-- Create New Ticket -->
            <div class="ticket-form">
                <h3>Create New Support Ticket</h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" placeholder="Brief description of your issue" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" placeholder="Please provide detailed information about your issue..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Create Ticket</button>
                </form>
            </div>
            
            <!-- Tickets List -->
            <div class="tickets-list">
                <h3>Your Support Tickets</h3>
                
                <?php if (empty($tickets)): ?>
                    <div class="no-tickets">
                        <p>No support tickets yet.</p>
                        <p>Create your first ticket to get help!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-item" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                            <div class="ticket-title">
                                <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                <div class="ticket-status status-<?php echo $ticket['status']; ?>">
                                    <?php echo ucfirst($ticket['status']); ?>
                                </div>
                            </div>
                            <div class="ticket-date">
                                Created: <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
                            </div>
                            <div class="ticket-description">
                                <?php echo htmlspecialchars(substr($ticket['first_message'] ?? $ticket['description'] ?? 'No message content', 0, 100)); ?>
                                <?php if (strlen($ticket['first_message'] ?? $ticket['description'] ?? '') > 100): ?>...<?php endif; ?>
                            </div>
                            <div class="ticket-actions">
                                <a href="ticket_chat.php?id=<?php echo $ticket['id']; ?>" class="btn-view">
                                    <i class="fa-solid fa-comments"></i> View Chat
                                </a>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="action" value="delete_ticket">
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <button type="submit" class="btn-delete">
                                        <i class="fa-solid fa-trash"></i> Delete Chat
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function viewTicket(ticketId) {
            window.location.href = 'ticket_chat.php?id=' + ticketId;
        }
        
        // Confirm delete action
        function confirmDelete() {
            return confirm('Are you sure you want to delete this support ticket? This action cannot be undone and will permanently remove all messages and ticket data.');
        }
    </script>
</body>
</html>