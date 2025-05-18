<?php
// Prevent PHP errors from being output
ini_set('display_errors', '0'); // Turn off display errors to prevent HTML in JSON response
ini_set('log_errors', '1');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For debugging only: check if session is working
// error_log('Session ID: ' . session_id());
// error_log('User ID: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set'));

// Include database connection
require_once '../includes/db.php';

// Check if the db.php file exists
if (!file_exists('../includes/db.php')) {
    echo json_encode(['error' => 'Database configuration file not found']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Include status checker utility (make this optional to isolate errors)
    if (file_exists('../includes/status_checker.php')) {
        require_once '../includes/status_checker.php';
        // Wrap in try-catch to prevent it from breaking the whole script
        try {
            checkAndUpdateReservationStatuses($conn);
        } catch (Exception $e) {
            error_log('Status checker error: ' . $e->getMessage());
            // Continue with the rest of the script
        }
    }

    // Verify connection is valid
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    }

    // Using MySQLi for database queries
    $query = "
        SELECT r.*, s.date, s.start_time, s.end_time, m.title as movie_title, m.poster, c.name as cinema_name
        FROM reservations r
        JOIN schedules s ON r.schedule_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN cinemas c ON s.cinema_id = c.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $reservations = [];
    
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    // Get seats for each reservation
    foreach ($reservations as &$reservation) {
        try {
            $seatQuery = "
                SELECT s.seat_number 
                FROM reservation_details rd
                JOIN seats s ON rd.seat_id = s.id
                WHERE rd.reservation_id = ?
            ";
            
            $seatStmt = $conn->prepare($seatQuery);
            
            if (!$seatStmt) {
                throw new Exception("Prepare failed for seat query: " . $conn->error);
            }
            
            $seatStmt->bind_param("i", $reservation['id']);
            
            if (!$seatStmt->execute()) {
                throw new Exception("Execute failed for seat query: " . $seatStmt->error);
            }
            
            $seatResult = $seatStmt->get_result();
            $seats = [];
            
            while ($seatRow = $seatResult->fetch_assoc()) {
                $seats[] = $seatRow['seat_number'];
            }
            
            $reservation['seats'] = $seats;
            
            $seatStmt->close();
            
            // Skip QR code generation if there's an error
            // Add basic QR path for frontend without trying to create it
            $reservation['qr_code'] = '../uploads/qrcodes/qr_' . $reservation['id'] . '_' . $user_id . '_' . time() . '.png';
            
        } catch (Exception $e) {
            error_log('Error processing seats for reservation ' . $reservation['id'] . ': ' . $e->getMessage());
            $reservation['seats'] = [];
            $reservation['qr_code'] = '/assets/images/qr-placeholder.png';
            // Continue with the next reservation
        }
    }
    
    echo json_encode(['reservations' => $reservations]);
    $stmt->close();
    
} catch (Exception $e) {
    error_log('Error in get_reservations.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Error retrieving reservations: ' . $e->getMessage()]);
}
?>