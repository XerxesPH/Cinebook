<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in, redirect to login if not
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth.php');
    exit;
}

// Include the data provider
require_once 'admin_files/admin_data_provider.php';

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get messages data
$messagesData = getMessages($limit, $offset, $filter);
$messages = $messagesData['messages'];
$totalPages = $messagesData['pages'];

// Get admin info
$admin = getAdminInfo();

// Mark message as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $messageId = intval($_GET['mark_read']);
    $conn = require 'includes/db.php';
    $updateQuery = "UPDATE admin_messages SET is_read = 1 WHERE id = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "i", $messageId);
    mysqli_stmt_execute($updateStmt);
    
    header("Location: admin-messages.php");
    exit();
}

// Delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $messageId = intval($_GET['delete']);
    $conn = require 'includes/db.php';
    $deleteQuery = "DELETE FROM admin_messages WHERE id = ?";
    $deleteStmt = mysqli_prepare($conn, $deleteQuery);
    mysqli_stmt_bind_param($deleteStmt, "i", $messageId);
    mysqli_stmt_execute($deleteStmt);
    
    header("Location: admin-messages.php");
    exit();
}

// Mark all messages as read
if (isset($_GET['mark_all_read'])) {
    $conn = require 'includes/db.php';
    $updateAllQuery = "UPDATE admin_messages SET is_read = 1";
    mysqli_query($conn, $updateAllQuery);
    
    header("Location: admin-messages.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messages - CineBook</title>
    <link rel="stylesheet" href="admin-styles/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>CineBook</h2>
                <p>Admin Panel</p>
            </div>

            <div class="admin-profile">
                <img src="<?php echo $admin['avatar']; ?>" alt="Admin Profile">
                <div class="profile-info">
                    <h3><?php echo $admin['name']; ?></h3>
                    <p><?php echo $admin['email']; ?></p>
                </div>
                <button id="slideToggle" class="slide-toggle">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div class="profile-slide" id="profileSlide">
                <a href="admin-dashboard.php?page=dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="admin-dashboard.php?page=reservations">
                    <i class="fas fa-ticket-alt"></i> Reservations
                </a>
                <a href="admin-dashboard.php?page=movies">
                    <i class="fas fa-film"></i> Movies
                </a>
                <a href="admin-dashboard.php?page=users">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="admin-messages.php" class="active">
                    <i class="fas fa-envelope"></i> Messages
                    <?php if ($messagesData['unread'] > 0): ?>
                    <span class="menu-badge"><?php echo $messagesData['unread']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin-dashboard.php?page=settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="index.php" class="back-to-site">
                    <i class="fas fa-home"></i> Back to Site
                </a>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="search-bar">
                    <input type="text" placeholder="Search messages...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="notification-icons">
                    <a href="admin-messages.php" class="message-icon">
                        <i class="fas fa-envelope"></i>
                        <?php if ($messagesData['unread'] > 0): ?>
                            <span class="badge"><?php echo $messagesData['unread']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Messages Content -->
            <div class="messages-content">
                <div class="content-header">
                    <h1>Messages</h1>
                    <div class="filter-options">
                        <div class="filter-item">
                            <label>Status:</label>
                            <select id="statusFilter" onchange="window.location.href='admin-messages.php?filter='+this.value">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Messages</option>
                                <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo $filter === 'read' ? 'selected' : ''; ?>>Read</option>
                            </select>
                        </div>
                        <button id="markAllRead" class="action-btn">Mark All as Read</button>
                    </div>
                </div>

                <div class="message-list">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>">
                                <div class="message-info">
                                    <h4>
                                        <?php if (!$message['is_read']): ?>
                                            <span class="unread-indicator"></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($message['subject']); ?>
                                    </h4>
                                    <p><?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) > 100 ? '...' : ''); ?></p>
                                    <div class="message-meta">
                                        <span>From: <?php echo htmlspecialchars($message['name']); ?> &lt;<?php echo htmlspecialchars($message['email']); ?>&gt;</span>
                                        <span><?php echo date('M d, Y h:i A', strtotime($message['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="message-actions">
                                    <a href="admin-message-view.php?id=<?php echo $message['id']; ?>" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!$message['is_read']): ?>
                                        <a href="admin-messages.php?mark_read=<?php echo $message['id']; ?>" title="Mark as Read">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="admin-messages.php?delete=<?php echo $message['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this message?');" 
                                       class="delete-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No messages found.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="admin-messages.php?page=<?php echo ($page - 1); ?>&filter=<?php echo $filter; ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="admin-messages.php?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" 
                           class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="admin-messages.php?page=<?php echo ($page + 1); ?>&filter=<?php echo $filter; ?>" class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Common admin scripts -->
    <script src="admin-js/admin-common.js"></script>
    
    <!-- Page specific scripts -->
    <script src="admin-js/admin-messages.js"></script>
</body>
</html> 