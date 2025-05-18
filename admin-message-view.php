<?php
$pageTitle = "CineBook Admin - View Message";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'includes/db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not admin
    header("Location: index.php");
    exit();
}

// Get message ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-messages.php");
    exit();
}

$messageId = intval($_GET['id']);

// Get message details
$query = "SELECT m.*, u.name as user_name, u.email as user_email 
          FROM admin_messages m 
          LEFT JOIN users u ON m.user_id = u.id 
          WHERE m.id = ?";
          
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $messageId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if message exists
if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: admin-messages.php");
    exit();
}

$message = mysqli_fetch_assoc($result);

// Mark message as read if it's not already
if (!$message['is_read']) {
    $updateQuery = "UPDATE admin_messages SET is_read = 1 WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "i", $messageId);
    mysqli_stmt_execute($updateStmt);
    
    // Update in current data
    $message['is_read'] = 1;
}

// Send reply to user
if (isset($_POST['send_reply'])) {
    $replyMessage = trim($_POST['reply_message']);
    
    if (!empty($replyMessage)) {
        // Save reply to database
        $replyQuery = "UPDATE admin_messages SET admin_reply = ?, reply_date = NOW() WHERE id = ?";
        $replyStmt = mysqli_prepare($conn, $replyQuery);
        mysqli_stmt_bind_param($replyStmt, "si", $replyMessage, $messageId);
        
        if (mysqli_stmt_execute($replyStmt)) {
            // In a real application, send an email to the user
            // For this example, we'll just show a success message
            $success = "Reply sent successfully to " . htmlspecialchars($message['email']);
            
            // Update the message data to include the reply
            $message['admin_reply'] = $replyMessage;
            $message['reply_date'] = date('Y-m-d H:i:s');
        } else {
            $error = "Failed to save reply. Please try again.";
        }
    } else {
        $error = "Reply message cannot be empty";
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="css/admin.css">

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="admin-dashboard.php">Dashboard</a></li>
            <li><a href="admin-movies.php">Movies</a></li>
            <li><a href="admin-schedules.php">Schedules</a></li>
            <li><a href="admin-reservations.php">Reservations</a></li>
            <li><a href="admin-users.php">Users</a></li>
            <li class="active"><a href="admin-messages.php">Messages</a></li>
            <li><a href="index.php">Back to Site</a></li>
        </ul>
    </div>
    
    <div class="admin-content">
        <div class="admin-header">
            <h2>View Message</h2>
            <div class="admin-actions">
                <a href="admin-messages.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Messages</a>
            </div>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert success">
                <span><?php echo $success; ?></span>
                <button class="alert-close"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert error">
                <span><?php echo $error; ?></span>
                <button class="alert-close"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        
        <div class="message-view">
            <div class="message-header">
                <div class="message-meta">
                    <div class="meta-item">
                        <span class="meta-label">From:</span>
                        <span class="meta-value">
                            <?php 
                            if ($message['user_id']) {
                                echo '<i class="fas fa-user"></i> ';
                                echo htmlspecialchars($message['user_name'] ?? $message['name']);
                                echo ' (Registered User)';
                            } else {
                                echo htmlspecialchars($message['name']);
                                echo ' (Guest)';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Email:</span>
                        <span class="meta-value">
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>">
                                <?php echo htmlspecialchars($message['email']); ?>
                            </a>
                        </span>
                    </div>
                    <?php if (!empty($message['phone'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Phone:</span>
                            <span class="meta-value">
                                <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>">
                                    <?php echo htmlspecialchars($message['phone']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span class="meta-label">Subject:</span>
                        <span class="meta-value">
                            <?php echo htmlspecialchars($message['subject']); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Date:</span>
                        <span class="meta-value">
                            <?php echo date('F d, Y h:i A', strtotime($message['created_at'])); ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Status:</span>
                        <span class="meta-value">
                            <span class="status-badge <?php echo $message['is_read'] ? 'read' : 'unread'; ?>">
                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="message-body">
                <h3>Message:</h3>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
            
            <?php if (!empty($message['admin_reply'])): ?>
            <div class="message-body">
                <h3>Your Previous Reply:</h3>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?>
                    <div class="reply-date">
                        <small>Sent: <?php echo date('F d, Y h:i A', strtotime($message['reply_date'])); ?></small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="message-reply">
                <h3><?php echo !empty($message['admin_reply']) ? 'Send Another Reply' : 'Reply to this Message'; ?></h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="reply_message">Your Reply</label>
                        <textarea id="reply_message" name="reply_message" rows="5" required></textarea>
                    </div>
                    <button type="submit" name="send_reply" class="btn btn-primary">
                        <i class="fas fa-reply"></i> Send Reply
                    </button>
                </form>
            </div>
            
            <div class="message-actions">
                <a href="admin-messages.php?delete=<?php echo $message['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this message?');">
                    <i class="fas fa-trash"></i> Delete Message
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle alert close buttons
    const closeButtons = document.querySelectorAll('.alert-close');
    closeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            this.parentElement.remove();
        });
    });
});
</script>

<style>
.reply-date {
    text-align: right;
    font-style: italic;
    color: #999;
    margin-top: 10px;
}
</style>

<?php include 'includes/footer.php'; ?> 