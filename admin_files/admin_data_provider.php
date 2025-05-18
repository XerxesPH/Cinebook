<?php
// admin_data_provider.php
// This file handles all data retrieval for the admin dashboard

// Check if admin is logged in, redirect to login if not
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth.php');
    exit;
}

require_once 'includes/db.php';

/**
 * Get admin information
 * @return array Admin user data
 */
function getAdminInfo() {
    global $conn;
    $admin_id = $_SESSION['admin_id'];
    $admin_query = "SELECT * FROM users WHERE id = '$admin_id' AND is_admin = 1";
    $admin_result = mysqli_query($conn, $admin_query);
    return mysqli_fetch_assoc($admin_result);
}

/**
 * Get all cinemas
 * @return array List of cinemas
 */
function getCinemas() {
    global $conn;
    $cinemas_query = "SELECT * FROM cinemas";
    $cinemas_result = mysqli_query($conn, $cinemas_query);
    $cinemas = [];
    while ($row = mysqli_fetch_assoc($cinemas_result)) {
        $cinemas[] = $row;
    }
    return $cinemas;
}

/**
 * Get all reservations with user, cinema, movie and seat details
 * @return array List of reservations
 */
function getReservations() {
    global $conn;
    $reservations_query = "SELECT r.id, r.status, u.name as user_name, u.email, u.contact_number as contact, 
                          c.name as cinema, m.title as movie, GROUP_CONCAT(s.seat_number) as seats, 
                          sch.show_date, sch.start_time as time
                          FROM reservations r
                          JOIN users u ON r.user_id = u.id
                          JOIN schedules sch ON r.schedule_id = sch.id
                          JOIN cinemas c ON sch.cinema_id = c.id
                          JOIN movies m ON sch.movie_id = m.id
                          JOIN reservation_details rd ON r.id = rd.reservation_id
                          JOIN seats s ON rd.seat_id = s.id
                          GROUP BY r.id
                          ORDER BY r.created_at DESC";
    $reservations_result = mysqli_query($conn, $reservations_query);
    $reservations = [];
    while ($row = mysqli_fetch_assoc($reservations_result)) {
        $reservations[] = $row;
    }
    return $reservations;
}

/**
 * Get all movies with cinema and schedule details
 * @return array List of movies
 */
function getMovies() {
    global $conn;
    $movies_query = "SELECT m.id, m.title, m.poster as image, c.id as cinema_id, 
                    GROUP_CONCAT(DISTINCT sch.start_time ORDER BY sch.start_time SEPARATOR ', ') as time,
                    MIN(sch.show_date) as start_showing, MAX(sch.end_date) as end_showing
                    FROM movies m
                    JOIN schedules sch ON m.id = sch.movie_id
                    JOIN cinemas c ON sch.cinema_id = c.id
                    GROUP BY m.id, c.id
                    ORDER BY m.id";
    $movies_result = mysqli_query($conn, $movies_query);
    $movies = [];
    while ($row = mysqli_fetch_assoc($movies_result)) {
        $movies[] = $row;
    }
    return $movies;
}

/**
 * Get total users count
 * @return int Total number of non-admin users
 */
function getTotalUsers() {
    global $conn;
    $total_users_query = "SELECT COUNT(*) as total FROM users WHERE is_admin = 0";
    $total_users_result = mysqli_query($conn, $total_users_query);
    $total_users_row = mysqli_fetch_assoc($total_users_result);
    return $total_users_row['total'];
}

/**
 * Get today's reservations count
 * @return int Number of reservations for today
 */
function getTodayReservations() {
    global $conn;
    $today = date('Y-m-d');
    $today_reservations_query = "SELECT COUNT(*) as total FROM reservations r 
                                JOIN schedules sch ON r.schedule_id = sch.id 
                                WHERE sch.show_date = '$today'";
    $today_reservations_result = mysqli_query($conn, $today_reservations_query);
    $today_reservations_row = mysqli_fetch_assoc($today_reservations_result);
    return $today_reservations_row['total'];
}

/**
 * Get active movies count
 * @return int Number of active movies
 */
function getActiveMovies() {
    global $conn;
    $today = date('Y-m-d');
    $active_movies_query = "SELECT COUNT(DISTINCT m.id) as total 
                           FROM movies m 
                           JOIN schedules sch ON m.id = sch.movie_id 
                           WHERE m.is_available = 1 
                           AND sch.show_date <= '$today' 
                           AND sch.end_date >= '$today'";
    $active_movies_result = mysqli_query($conn, $active_movies_query);
    $active_movies_row = mysqli_fetch_assoc($active_movies_result);
    return $active_movies_row['total'];
}

/**
 * Get today's revenue
 * @return float Total revenue for today
 */
function getTodayRevenue() {
    global $conn;
    $today = date('Y-m-d');
    $today_revenue_query = "SELECT SUM(total_amount) as total 
                           FROM reservations
                           WHERE DATE(created_at) = '$today'";
    $today_revenue_result = mysqli_query($conn, $today_revenue_query);
    $today_revenue_row = mysqli_fetch_assoc($today_revenue_result);
    return $today_revenue_row['total'] ?? 0;
}


/**
 * Get upcoming movies
 * @return array List of upcoming movies
 */
function getUpcomingMovies() {
    global $conn;
    $today = date('Y-m-d');
    $upcoming_query = "SELECT DISTINCT m.id, m.title, m.poster, MIN(sch.show_date) as premiere_date
                      FROM movies m
                      JOIN schedules sch ON m.id = sch.movie_id
                      WHERE m.is_available = 1 AND sch.show_date > '$today'
                      GROUP BY m.id
                      ORDER BY premiere_date ASC
                      LIMIT 5";
    $upcoming_result = mysqli_query($conn, $upcoming_query);
    $upcoming = [];
    while ($row = mysqli_fetch_assoc($upcoming_result)) {
        $upcoming[] = $row;
    }
    return $upcoming;
}

/**
 * Get recent activity
 * @param int $limit Number of activities to return
 * @return array List of recent activities
 */
function getRecentActivity($limit = 3) {
    global $conn;
    $recent_activity_query = "SELECT r.id, u.name as user_name, m.title as movie, r.created_at, 'reservation' as type
                             FROM reservations r
                             JOIN users u ON r.user_id = u.id
                             JOIN schedules sch ON r.schedule_id = sch.id
                             JOIN movies m ON sch.movie_id = m.id
                             UNION
                             SELECT id, name, NULL as movie, created_at, 'user' as type
                             FROM users
                             WHERE is_admin = 0
                             ORDER BY created_at DESC
                             LIMIT $limit";
    $recent_activity_result = mysqli_query($conn, $recent_activity_query);
    $recent_activity = [];
    while ($row = mysqli_fetch_assoc($recent_activity_result)) {
        $recent_activity[] = $row;
    }
    return $recent_activity;
}

/**
 * Get all users except admins
 * @return array List of users
 */
function getUsers() {
    global $conn;
    $users_query = "SELECT id, name, email, contact_number, created_at, 
                   CASE WHEN is_admin = 1 THEN 'admin' ELSE 'active' END as status
                   FROM users
                   WHERE is_admin = 0
                   ORDER BY created_at DESC";
    $users_result = mysqli_query($conn, $users_query);
    $users = [];
    while ($row = mysqli_fetch_assoc($users_result)) {
        $users[] = $row;
    }
    return $users;
}

/**
 * Get schedule information by date range
 * @param string $start_date Beginning date in Y-m-d format
 * @param string $end_date Ending date in Y-m-d format
 * @return array List of schedules
 */
function getSchedulesByDateRange($start_date, $end_date) {
    global $conn;
    $schedules_query = "SELECT sch.id, c.name as cinema, m.title as movie, 
                       sch.show_date, sch.end_date, sch.start_time, sch.end_time,
                       CASE WHEN sch.is_available = 1 THEN 'Available' ELSE 'Unavailable' END as status
                       FROM schedules sch
                       JOIN cinemas c ON sch.cinema_id = c.id
                       JOIN movies m ON sch.movie_id = m.id
                       WHERE (sch.show_date BETWEEN '$start_date' AND '$end_date')
                       OR (sch.end_date BETWEEN '$start_date' AND '$end_date')
                       ORDER BY sch.show_date, sch.start_time";
    $schedules_result = mysqli_query($conn, $schedules_query);
    $schedules = [];
    while ($row = mysqli_fetch_assoc($schedules_result)) {
        $schedules[] = $row;
    }
    return $schedules;
}

/**
 * Get messages for admin panel
 * @param int $limit Number of messages per page
 * @param int $offset Offset for pagination
 * @param string $filter Filter by read status ('all', 'read', 'unread')
 * @return array List of messages with pagination info
 */
function getMessages($limit = 10, $offset = 0, $filter = 'all') {
    global $conn;
    
    // Base query
    $base_query = "FROM admin_messages";
    
    // Add filter condition if needed
    if ($filter === 'read') {
        $where_clause = " WHERE is_read = 1";
    } elseif ($filter === 'unread') {
        $where_clause = " WHERE is_read = 0";
    } else {
        $where_clause = "";
    }
    
    // Count total messages for pagination
    $count_query = "SELECT COUNT(*) as total " . $base_query . $where_clause;
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $total = $count_row['total'];
    
    // Count unread messages
    $unread_query = "SELECT COUNT(*) as unread FROM admin_messages WHERE is_read = 0";
    $unread_result = mysqli_query($conn, $unread_query);
    $unread_row = mysqli_fetch_assoc($unread_result);
    $unread = $unread_row['unread'];
    
    // Get messages with pagination
    $messages_query = "SELECT * " . $base_query . $where_clause . " ORDER BY created_at DESC LIMIT $offset, $limit";
    $messages_result = mysqli_query($conn, $messages_query);
    $messages = [];
    while ($row = mysqli_fetch_assoc($messages_result)) {
        $messages[] = $row;
    }
    
    return [
        'messages' => $messages,
        'total' => $total,
        'unread' => $unread,
        'pages' => ceil($total / $limit),
        'current_page' => floor($offset / $limit) + 1,
        'limit' => $limit
    ];
}

/**
 * Get unread messages count
 * @return int Number of unread messages
 */
function getUnreadMessagesCount() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM admin_messages WHERE is_read = 0";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Return data based on what's needed
function getAdminDashboardData() {
    global $conn;
    
    // Check for status updates before gathering data
    if (file_exists(__DIR__ . '/../includes/status_checker.php')) {
        require_once __DIR__ . '/../includes/status_checker.php';
        checkAndUpdateReservationStatuses($conn);
    }
    
    $today = date('Y-m-d');
    $next_week = date('Y-m-d', strtotime('+7 days'));
    
    return [
        'admin' => getAdminInfo(),
        'cinemas' => getCinemas(),
        'reservations' => getReservations(),
        'movies' => getMovies(),
        'total_users' => getTotalUsers(),
        'today_reservations' => getTodayReservations(),
        'active_movies' => getActiveMovies(),
        'today_revenue' => getTodayRevenue(),
        'recent_activity' => getRecentActivity(),
        'upcoming_movies' => getUpcomingMovies(),
        'current_schedules' => getSchedulesByDateRange($today, $next_week),
        'users' => getUsers(),
        'messages' => getMessages(),
        'unread_messages' => getUnreadMessagesCount()
    ];
}

// If this file is accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    echo json_encode(getAdminDashboardData());
    exit;
}