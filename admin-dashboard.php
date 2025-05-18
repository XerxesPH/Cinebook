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

// Get all data needed for the dashboard
$data = getAdminDashboardData();

// Extract variables from data array for easier access in templates
extract($data);

define('INCLUDED', true);

// Determine which page content to show based on GET parameter
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Get database connection
$conn = $GLOBALS['conn'];

// Handle message actions
if ($currentPage == 'messages') {
    // Mark message as read
    if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
        $messageId = intval($_GET['mark_read']);
        $updateQuery = "UPDATE admin_messages SET is_read = 1 WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "i", $messageId);
        mysqli_stmt_execute($updateStmt);
        
        header("Location: admin-dashboard.php?page=messages");
        exit();
    }
    
    // Delete message
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $messageId = intval($_GET['delete']);
        $deleteQuery = "DELETE FROM admin_messages WHERE id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "i", $messageId);
        mysqli_stmt_execute($deleteStmt);
        
        header("Location: admin-dashboard.php?page=messages");
        exit();
    }
    
    // Mark all messages as read
    if (isset($_GET['mark_all_read'])) {
        $updateAllQuery = "UPDATE admin_messages SET is_read = 1";
        mysqli_query($conn, $updateAllQuery);
        
        header("Location: admin-dashboard.php?page=messages");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CineBook</title>
    <link rel="stylesheet" href="admin-styles/admin-styles.css">
    <link rel="stylesheet" href="admin-styles/admin_movie_modal.css">
    <link rel="stylesheet" href="admin-styles/admin_movie_actions.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-chevron-right" id="toggleIcon"></i>
            </button>
            
            <div class="sidebar-header">
                <i class="fas fa-film logo-icon"></i>
                <h2>CineBook</h2>
            </div>

            <div class="admin-profile">
                <img src="<?php echo $admin['avatar']; ?>" alt="Admin Profile">
                <div class="profile-info">
                    <h3><?php echo $admin['name']; ?></h3>
                    <p><?php echo $admin['email']; ?></p>
                </div>
            </div>

            <div class="nav-menu">
                <a href="admin-dashboard.php?page=dashboard" class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
                <a href="admin-dashboard.php?page=reservations" class="nav-item <?php echo $currentPage == 'reservations' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i>
                    <span class="menu-text">Reservations</span>
                </a>
                <a href="admin-dashboard.php?page=movies" class="nav-item <?php echo $currentPage == 'movies' ? 'active' : ''; ?>">
                    <i class="fas fa-film"></i>
                    <span class="menu-text">Movies</span>
                </a>
                <a href="admin-dashboard.php?page=users" class="nav-item <?php echo $currentPage == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">Users</span>
                </a>
                <a href="admin-dashboard.php?page=messages" class="nav-item <?php echo $currentPage == 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span class="menu-text">Messages</span>
                    <?php if ($unread_messages > 0): ?>
                    <span class="menu-badge"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
                <a href="admin_files/verify_reservation.php" class="nav-item">
                    <i class="fas fa-qrcode"></i>
                    <span class="menu-text">Verify Reservation</span>
                </a>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Back to Site</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="search-bar">
                    <input type="text" placeholder="Search...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="notification-icons">
                    <a href="admin-dashboard.php?page=messages" class="message-icon">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread_messages > 0): ?>
                            <span class="badge"><?php echo $unread_messages; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <?php if ($currentPage == 'dashboard'): ?>
                <!-- Dashboard Content -->
                <div class="dashboard-content">
                    <h1>Admin Dashboard</h1>
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Users</h3>
                                <p><?php echo $total_users; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Today's Revenue</h3>
                                <p>₱<?php echo number_format($today_revenue, 2); ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-film"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Active Movies</h3>
                                <p><?php echo $active_movies; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Today's Reservations</h3>
                                <p><?php echo $today_reservations; ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Unread Messages</h3>
                                <p><?php echo $unread_messages; ?></p>
                            </div>
                        </div>

                        <div class="recent-activity">
                            <h2>Recent Activity</h2>
                            <div class="activity-list">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-<?php echo $activity['type'] == 'reservation' ? 'ticket-alt' : 'user-plus'; ?>"></i>
                                        </div>
                                        <div class="activity-info">
                                            <h4><?php echo $activity['type'] == 'reservation' ? 'New Reservation' : 'New User Registration'; ?></h4>
                                            <p>
                                                <?php
                                                if ($activity['type'] == 'reservation') {
                                                    echo $activity['user_name'] . ' reserved seats for ' . $activity['movie'];
                                                } else {
                                                    echo $activity['user_name'] . ' created an account';
                                                }
                                                ?>
                                            </p>
                                            <span class="activity-time">
                                                <?php
                                                $time_diff = time() - strtotime($activity['created_at']);
                                                if ($time_diff < 60) {
                                                    echo 'Just now';
                                                } elseif ($time_diff < 3600) {
                                                    echo floor($time_diff / 60) . ' minutes ago';
                                                } elseif ($time_diff < 86400) {
                                                    echo floor($time_diff / 3600) . ' hours ago';
                                                } else {
                                                    echo date('M j', strtotime($activity['created_at']));
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($currentPage == 'reservations'): ?>
                    <!-- Reservations Content -->
                    <div class="reservations-content">
                        <div class="content-header">
                            <h1>Reservations</h1>
                            <div class="filter-options">
                                <div class="filter-item">
                                    <label>Cinema:</label>
                                    <select>
                                        <option value="all">All Cinemas</option>
                                        <?php foreach ($cinemas as $cinema): ?>
                                            <option value="<?php echo $cinema['id']; ?>"><?php echo $cinema['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-item">
                                    <label>Date:</label>
                                    <input type="date" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <button class="filter-btn">Apply Filters</button>
                            </div>
                        </div>

                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Cinema</th>
                                        <th>Movie</th>
                                        <th>Seats</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <?php 
                                        // Determine reservation status based on actual database status
                                        $today = date('Y-m-d');
                                        $reservationDate = $reservation['show_date'];
                                        
                                        // Get status from database or determine based on date
                                        $status = isset($reservation['status']) ? ucfirst($reservation['status']) : "Pending";
                                        $statusClass = "status " . strtolower($status);
                                        
                                        // If status is not set in the database (for backward compatibility)
                                        if (!isset($reservation['status'])) {
                                            // Mark as expired if date has passed
                                            if (strtotime($reservationDate) < strtotime($today)) {
                                                $status = "Expired";
                                                $statusClass = "status expired";
                                            }
                                        }
                                        ?>
                                        <tr style="text-align: center;">
                                            <td><?php echo $reservation['user_name']; ?></td>
                                            <td><?php echo $reservation['email']; ?></td>
                                            <td><?php echo $reservation['contact']; ?></td>
                                            <td><?php echo $reservation['cinema']; ?></td>
                                            <td><?php echo $reservation['movie']; ?></td>
                                            <td><?php echo $reservation['seats']; ?></td>
                                            <td><?php echo $reservation['show_date']; ?></td>
                                            <td><?php echo $reservation['time']; ?></td>
                                            <td><span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination">
                            <?php
                            // Calculate pagination values
                            $totalReservations = count($reservations);
                            $resPerPage = 10; 
                            $totalResPages = ceil($totalReservations / $resPerPage);
                            $currentResPage = isset($_GET['res_page']) ? (int)$_GET['res_page'] : 1;

                            // Ensure current page is valid
                            if ($currentResPage < 1) $currentResPage = 1;
                            if ($currentResPage > $totalResPages) $currentResPage = $totalResPages;

                            // Previous button
                            $prevDisabled = ($currentResPage <= 1) ? 'disabled' : '';
                            $prevPage = max(1, $currentResPage - 1);
                            ?>
                            <button class="page-btn <?php echo $prevDisabled; ?>"
                                onclick="window.location='admin-dashboard.php?page=reservations&res_page=<?php echo $prevPage; ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>

                            <?php
                            // Calculate which page numbers to show
                            $startPage = max(1, $currentResPage - 2);
                            $endPage = min($totalResPages, $startPage + 4);

                            // Adjust if we're near the end
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }

                            // Display page numbers
                            for ($i = $startPage; $i <= $endPage; $i++):
                                $activeClass = ($i == $currentResPage) ? 'active' : '';
                            ?>
                                <button class="page-btn <?php echo $activeClass; ?>"
                                    onclick="window.location='admin-dashboard.php?page=reservations&res_page=<?php echo $i; ?>'">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>

                            <?php
                            // Next button
                            $nextDisabled = ($currentResPage >= $totalResPages) ? 'disabled' : '';
                            $nextPage = min($totalResPages, $currentResPage + 1);
                            ?>
                            <button class="page-btn <?php echo $nextDisabled; ?>"
                                onclick="window.location='admin-dashboard.php?page=reservations&res_page=<?php echo $nextPage; ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                <?php elseif ($currentPage == 'movies'): ?>
                    <!-- Movies Content -->
                    <div class="movies-content">
                        <div class="content-header">
                            <h1>Manage Movies</h1>
                            <button id="addMovieBtn" class="add-btn">Add Movie</button>
                        </div>

                        <?php
                        // Process movies data to merge movies with the same title
                        $mergedMovies = [];
                        foreach ($movies as $movie) {
                            $title = $movie['title'];
                            
                            // If this movie title doesn't exist yet in our merged array, add it
                            if (!isset($mergedMovies[$title])) {
                                $mergedMovies[$title] = [
                                    'id' => [$movie['id']], // Store IDs as an array
                                    'title' => $title,
                                    'image' => $movie['image'],
                                    'cinema_id' => [$movie['cinema_id']], // Store cinema IDs as an array
                                    'cinema_names' => ['Cinema ' . $movie['cinema_id']], // Store cinema names
                                    'start_showing' => $movie['start_showing'],
                                    'end_showing' => $movie['end_showing'],
                                    'time' => $movie['time']
                                ];
                            } else {
                                // This movie title already exists, so update the existing entry
                                $mergedMovies[$title]['id'][] = $movie['id']; // Add this ID
                                
                                // Only add cinema if it's not already in the list
                                if (!in_array($movie['cinema_id'], $mergedMovies[$title]['cinema_id'])) {
                                    $mergedMovies[$title]['cinema_id'][] = $movie['cinema_id'];
                                    $mergedMovies[$title]['cinema_names'][] = 'Cinema ' . $movie['cinema_id'];
                                }
                                
                                // Update dates if needed
                                if (strtotime($movie['start_showing']) < strtotime($mergedMovies[$title]['start_showing'])) {
                                    $mergedMovies[$title]['start_showing'] = $movie['start_showing'];
                                }
                                
                                if (strtotime($movie['end_showing']) > strtotime($mergedMovies[$title]['end_showing'])) {
                                    $mergedMovies[$title]['end_showing'] = $movie['end_showing'];
                                }
                                
                                // Merge time slots
                                $currentTimes = explode(', ', $mergedMovies[$title]['time']);
                                $newTimes = explode(', ', $movie['time']);
                                $allTimes = array_merge($currentTimes, $newTimes);
                                $uniqueTimes = array_unique($allTimes);
                                sort($uniqueTimes);
                                $mergedMovies[$title]['time'] = implode(', ', $uniqueTimes);
                            }
                        }
                        
                        // Convert associative array to indexed array
                        $mergedMoviesList = array_values($mergedMovies);
                        ?>

                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Poster</th>
                                        <th>Cinema</th>
                                        <th>Show Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mergedMoviesList as $movie): ?>
                                        <?php
                                        $today = date('Y-m-d');
                                        $status = "Showing";
                                        $statusClass = "status showing";

                                        if (strtotime($movie['start_showing']) > strtotime($today)) {
                                            $status = "Coming Soon";
                                            $statusClass = "status coming-soon";
                                        } elseif (strtotime($movie['end_showing']) < strtotime($today)) {
                                            $status = "Ended";
                                            $statusClass = "status ended";
                                        }
                                        
                                        // Format cinema list
                                        $cinemaText = implode(', ', $movie['cinema_names']);
                                        
                                        // Get the first ID for the edit/delete buttons
                                        $primaryId = $movie['id'][0];
                                        ?>
                                        <tr>
                                            <td><?php echo $movie['title']; ?></td>
                                            <td class="movie-poster">
                                                <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>">
                                            </td>
                                            <td><?php echo $cinemaText; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($movie['start_showing'])); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($movie['end_showing'])); ?></td>
                                            <td><span class="<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="edit-btn" data-id="<?php echo $primaryId; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="delete-btn" data-id="<?php echo $primaryId; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination">
                            <?php
                            // Calculate pagination values
                            $totalMovies = count($mergedMoviesList); // Use the merged count
                            $itemsPerPage = 5; // You can adjust this value
                            $totalPages = ceil($totalMovies / $itemsPerPage);
                            $currentPageNum = isset($_GET['movie_page']) ? (int)$_GET['movie_page'] : 1;

                            // Ensure current page is valid
                            if ($currentPageNum < 1) $currentPageNum = 1;
                            if ($currentPageNum > $totalPages) $currentPageNum = $totalPages;

                            // Previous button
                            $prevDisabled = ($currentPageNum <= 1) ? 'disabled' : '';
                            $prevPage = max(1, $currentPageNum - 1);
                            ?>
                            <button class="page-btn <?php echo $prevDisabled; ?>"
                                onclick="window.location='admin-dashboard.php?page=movies&movie_page=<?php echo $prevPage; ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>

                            <?php
                            // Calculate which page numbers to show
                            $startPage = max(1, $currentPageNum - 2);
                            $endPage = min($totalPages, $startPage + 4);

                            // Adjust if we're near the end
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }

                            // Display page numbers
                            for ($i = $startPage; $i <= $endPage; $i++):
                                $activeClass = ($i == $currentPageNum) ? 'active' : '';
                            ?>
                                <button class="page-btn <?php echo $activeClass; ?>"
                                    onclick="window.location='admin-dashboard.php?page=movies&movie_page=<?php echo $i; ?>'">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>

                            <?php
                            // Next button
                            $nextDisabled = ($currentPageNum >= $totalPages) ? 'disabled' : '';
                            $nextPage = min($totalPages, $currentPageNum + 1);
                            ?>
                            <button class="page-btn <?php echo $nextDisabled; ?>"
                                onclick="window.location='admin-dashboard.php?page=movies&movie_page=<?php echo $nextPage; ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                <?php elseif ($currentPage == 'users'): ?>
                    <!-- Users Content -->
                    <div class="users-content">
                        <div class="content-header">
                            <h1>Manage Users</h1>
                            <button id="addUserBtn" class="add-btn">Add User</button>
                        </div>

                        <div class="data-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Registration Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo $user['name']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo $user['contact_number']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td><span class="status <?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="view-btn" data-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="edit-btn" data-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="delete-btn" data-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination">
                            <button class="page-btn"><i class="fas fa-chevron-left"></i></button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>

                <?php elseif ($currentPage == 'messages'): ?>
                    <!-- Messages Content -->
                    <div class="messages-content">
                        <div class="content-header">
                            <h1>Messages</h1>
                            <div class="filter-options">
                                <div class="filter-item">
                                    <label>Status:</label>
                                    <select id="statusFilter" onchange="window.location.href='admin-dashboard.php?page=messages&filter='+this.value">
                                        <option value="all" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'all' ? 'selected' : ''; ?>>All Messages</option>
                                        <option value="unread" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                        <option value="read" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                    </select>
                                </div>
                                <a href="admin-dashboard.php?page=messages&mark_all_read=1" class="action-btn" onclick="return confirm('Are you sure you want to mark all messages as read?');">Mark All as Read</a>
                            </div>
                        </div>

                        <?php
                        // Get filter and pagination parameters
                        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
                        $msg_page = isset($_GET['msg_page']) ? max(1, intval($_GET['msg_page'])) : 1;
                        $limit = 10;
                        $offset = ($msg_page - 1) * $limit;
                        
                        // Get messages with pagination
                        $messagesData = getMessages($limit, $offset, $filter);
                        $messagesList = $messagesData['messages'];
                        $totalPages = $messagesData['pages'];
                        $unread_count = $messagesData['unread']; // Store unread count
                        ?>

                        <div class="message-list">
                            <?php if (count($messagesList) > 0): ?>
                                <?php foreach ($messagesList as $message): ?>
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
                                                <a href="admin-dashboard.php?page=messages&mark_read=<?php echo $message['id']; ?>" title="Mark as Read">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="admin-dashboard.php?page=messages&delete=<?php echo $message['id']; ?>" 
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
                            <?php if ($msg_page > 1): ?>
                                <a href="admin-dashboard.php?page=messages&msg_page=<?php echo ($msg_page - 1); ?>&filter=<?php echo $filter; ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $msg_page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="admin-dashboard.php?page=messages&msg_page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" 
                                   class="page-btn <?php echo ($i == $msg_page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($msg_page < $totalPages): ?>
                                <a href="admin-dashboard.php?page=messages&msg_page=<?php echo ($msg_page + 1); ?>&filter=<?php echo $filter; ?>" class="page-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
        </main>
    </div>

    <?php include 'admin-modals/add-movie-modal.php'; ?>

    <!-- Common admin scripts -->
    <script src="admin-js/admin-common.js"></script>
    
    <!-- Page specific scripts -->
    <script src="admin-js/admin-dashboard.js"></script>
    <script src="admin-js/admin_movie_modal.js"></script>
    <script src="admin-js/admin_movie_actions.js"></script>
</body>

</html>