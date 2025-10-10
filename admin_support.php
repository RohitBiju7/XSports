<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: signin.php');
    exit();
}

// Database connection
require_once 'includes/db.php';

// Handle ticket status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'accept_ticket':
                $ticket_id = $_POST['ticket_id'];
                
                // Debug: Check current status
                $check_stmt = $pdo->prepare('SELECT status FROM support_tickets WHERE id = ?');
                $check_stmt->execute([$ticket_id]);
                $current_status = $check_stmt->fetch()['status'];
                
                // Try to update to active
                $stmt = $pdo->prepare('UPDATE support_tickets SET status = "active" WHERE id = ?');
                $result = $stmt->execute([$ticket_id]);
                
                if ($result) {
                    // Verify the update worked
                    $verify_stmt = $pdo->prepare('SELECT status FROM support_tickets WHERE id = ?');
                    $verify_stmt->execute([$ticket_id]);
                    $updated_status = $verify_stmt->fetch()['status'];
                    
                    // Debug: Log the change
                    error_log("Ticket #{$ticket_id} status changed from '{$current_status}' to '{$updated_status}'");
                } else {
                    // Debug: Log the error
                    error_log("Failed to update ticket #{$ticket_id} to active status");
                }
                break;
                
            case 'resolve_ticket':
                $ticket_id = $_POST['ticket_id'];
                $stmt = $pdo->prepare('UPDATE support_tickets SET status = "resolved" WHERE id = ?');
                $stmt->execute([$ticket_id]);
                break;
                
            case 'delete_ticket':
                $ticket_id = $_POST['ticket_id'];
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Delete all messages for this ticket first (due to foreign key constraint)
                    $stmt = $pdo->prepare('DELETE FROM support_messages WHERE ticket_id = ?');
                    $stmt->execute([$ticket_id]);
                    
                    // Delete the ticket itself
                    $stmt = $pdo->prepare('DELETE FROM support_tickets WHERE id = ?');
                    $stmt->execute([$ticket_id]);
                    
                    // Commit transaction
                    $pdo->commit();
                } catch (PDOException $e) {
                    // Rollback on error
                    $pdo->rollBack();
                    error_log("Error deleting ticket #{$ticket_id}: " . $e->getMessage());
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: admin_support.php');
        exit();
    }
}

// Get ticket statistics (after any POST updates)
$stmt = $pdo->query('SELECT COUNT(*) as total FROM support_tickets');
$total_tickets = $stmt->fetch()['total'];

$stmt = $pdo->query('SELECT COUNT(*) as pending FROM support_tickets WHERE status = "pending"');
$pending_tickets = $stmt->fetch()['pending'];

$stmt = $pdo->query('SELECT COUNT(*) as active FROM support_tickets WHERE status = "active"');
$active_tickets = $stmt->fetch()['active'];

$stmt = $pdo->query('SELECT COUNT(*) as resolved FROM support_tickets WHERE status = "resolved"');
$resolved_tickets = $stmt->fetch()['resolved'];

// Build the query with filters
$where_conditions = [];
$params = [];

// Status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where_conditions[] = 't.status = ?';
    $params[] = $_GET['status'];
}

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_conditions[] = '(t.subject LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR t.description LIKE ?)';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get filtered tickets with user information
$query = "
    SELECT t.*, u.name as user_name, u.email as user_email,
           (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) as message_count,
           (SELECT message FROM support_messages WHERE ticket_id = t.id ORDER BY created_at ASC LIMIT 1) as first_message
    FROM support_tickets t
    LEFT JOIN users u ON t.user_id = u.id
    $where_clause
    ORDER BY t.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Management - XSports</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            min-height: 100vh;
        }
        
        .admin-header {
            background: #385060;
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.15);
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .admin-header p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .back-button {
            position: absolute;
            top: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border: 2px solid #e9ecef;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #385060;
            font-size: 1.2rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #385060;
            margin: 0;
        }
        
        .filters-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px solid #e9ecef;
        }
        
        .filters-section h2 {
            color: #385060;
            margin-bottom: 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filters-row {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            min-width: 80px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #385060;
            box-shadow: 0 0 0 3px rgba(56, 80, 96, 0.1);
        }
        
        .btn-primary {
            background: #385060;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(56, 80, 96, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .tickets-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .tickets-header {
            background: #385060;
            color: white;
            padding: 20px 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tickets-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .ticket-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-status {
            position: absolute;
            top: 15px;
            right: 15px;
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
        
        .ticket-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 15px 0;
        }
        
        .ticket-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .ticket-meta div {
            margin-bottom: 5px;
        }
        
        .ticket-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-style: italic;
            color: #666;
        }
        
        .ticket-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-accept {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-accept:hover {
            background: #218838;
        }
        
        .btn-resolve {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-resolve:hover {
            background: #c82333;
        }
        
        .btn-chat {
            background: #6f42c1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-chat:hover {
            background: #5a32a3;
            color: white;
            text-decoration: none;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 15px;
            }
            
            .admin-header h1 {
                font-size: 2rem;
            }
            
            .back-button {
                position: static;
                margin-bottom: 20px;
                width: fit-content;
            }
            
            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .tickets-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            
            .ticket-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header" style="position: relative;">
            <h1><i class="fa-solid fa-headset"></i> Support Management</h1>
            <p>Manage customer support tickets and provide excellent service</p>
            <!-- <a href="admin.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i> Back to Admin
            </a> -->
        </div>
        
        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="stat-card">
                <h3><i class="fa-solid fa-ticket"></i> Total Tickets</h3>
                <p class="stat-number"><?php echo $total_tickets; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fa-solid fa-clock"></i> Pending</h3>
                <p class="stat-number"><?php echo $pending_tickets; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fa-solid fa-comments"></i> Active</h3>
                <p class="stat-number"><?php echo $active_tickets; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fa-solid fa-check-circle"></i> Resolved</h3>
                <p class="stat-number"><?php echo $resolved_tickets; ?></p>
            </div>
        </div>
        
        <!-- Filters Section -->
        <div class="filters-section">
            <h2><i class="fa-solid fa-filter"></i> Search & Filter</h2>
            <form method="GET" id="filterForm">
                <div class="filters-row">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Search:</label>
                        <input type="text" name="search" id="searchInput" placeholder="Search tickets..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-search"></i> Filter
                        </button>
                        <button type="button" class="btn-secondary" onclick="clearFilters()">
                            <i class="fa-solid fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tickets Section -->
        <div class="tickets-section">
            <div class="tickets-header">
                <h2><i class="fa-solid fa-list"></i> Support Tickets</h2>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <h3>No Support Tickets</h3>
                    <p>
                        <?php 
                        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
                        if ($status_filter === 'active') {
                            echo 'All clear! No active support requests at the moment.';
                        } elseif ($status_filter === 'resolved') {
                            echo 'All clear! No resolved support requests at the moment.';
                        } elseif ($status_filter === 'pending') {
                            echo 'All clear! No pending support requests at the moment.';
                        } else {
                            echo 'All clear! No support requests at the moment.';
                        }
                        ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="tickets-grid">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-card">
                            <div class="ticket-status status-<?php echo $ticket['status']; ?>">
                                <?php echo ucfirst($ticket['status']); ?>
                            </div>
                            
                            <div class="ticket-title"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                            
                            <div class="ticket-meta">
                                <div><strong>Ticket #<?php echo $ticket['id']; ?></strong> • <?php echo $ticket['message_count']; ?> messages</div>
                                <div>By: <?php echo htmlspecialchars($ticket['user_name']); ?> (<?php echo htmlspecialchars($ticket['user_email']); ?>)</div>
                                <div>Created: <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></div>
                            </div>
                            
                                                         <div class="ticket-preview">
                                 "<?php echo htmlspecialchars(substr($ticket['first_message'] ?? $ticket['description'] ?? 'No message content', 0, 100)); ?><?php echo strlen($ticket['first_message'] ?? $ticket['description'] ?? '') > 100 ? '...' : ''; ?>"
                             </div>
                            
                            <div class="ticket-actions">
                                <?php if ($ticket['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="accept_ticket">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <button type="submit" class="btn-accept">
                                            <i class="fa-solid fa-check"></i> Accept Ticket
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($ticket['status'] !== 'resolved'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="resolve_ticket">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <button type="submit" class="btn-resolve">
                                            <i class="fa-solid fa-check-double"></i> Mark Resolved
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="admin_ticket_chat.php?id=<?php echo $ticket['id']; ?>" class="btn-chat">
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
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchInput').value = '';
            document.getElementById('filterForm').submit();
        }
        
        // Auto-submit form when status changes
        document.getElementById('statusFilter').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        // Confirm delete action
        function confirmDelete() {
            return confirm('Are you sure you want to delete this support ticket? This action cannot be undone and will permanently remove all messages and ticket data.');
        }
    </script>
</body>
</html> 