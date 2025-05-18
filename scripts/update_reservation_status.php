<?php
/**
 * Script to update reservation statuses to 'ended' after movies have finished
 * This script should be run via a cron job or scheduled task
 */

// Suppress deprecated warnings which can cause issues
error_reporting(E_ALL & ~E_DEPRECATED);

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Function to log messages
function logMessage($message) {
    echo date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    error_log('[RESERVATION_UPDATE] ' . $message);
}

logMessage('Starting reservation status update process');

try {
    // Get all pending and verified reservations that have showtime in the past
    $query = "
        SELECT r.id, r.reservation_code, r.status, r.schedule_id, r.user_id,
               s.date as show_date, s.start_time, s.end_time, m.title as movie_title
        FROM reservations r
        JOIN schedules s ON r.schedule_id = s.id
        JOIN movies m ON s.movie_id = m.id
        WHERE r.status IN ('pending', 'verified')
        AND CONCAT(s.date, ' ', s.end_time) < NOW()
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    $updateCount = 0;
    
    // Begin transaction for bulk updates
    $conn->begin_transaction();
    
    try {
        while ($reservation = mysqli_fetch_assoc($result)) {
            logMessage("Processing reservation ID: {$reservation['id']} (Code: {$reservation['reservation_code']}) for movie: {$reservation['movie_title']}");
            
            // Update reservation status
            $updateQuery = "UPDATE reservations SET status = 'ended' WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $reservation['id']);
            $stmt->execute();
            
            // Update reservation details status if column exists
            $checkStatusColumn = "SHOW COLUMNS FROM reservation_details LIKE 'status'";
            $statusResult = mysqli_query($conn, $checkStatusColumn);
            
            if (mysqli_num_rows($statusResult) > 0) {
                $detailsQuery = "UPDATE reservation_details SET status = 'ended' WHERE reservation_id = ?";
                $detailsStmt = $conn->prepare($detailsQuery);
                $detailsStmt->bind_param("i", $reservation['id']);
                $detailsStmt->execute();
            }
            
            // Optional: Create notification for user
            $notificationQuery = "INSERT INTO user_notifications (user_id, reservation_id, title, message, type) 
                               VALUES (?, ?, 'Reservation Ended', 'Your reservation for {$reservation['movie_title']} has ended.', 'reservation')";
            $notificationStmt = $conn->prepare($notificationQuery);
            $notificationStmt->bind_param("ii", $reservation['user_id'], $reservation['id']);
            $notificationStmt->execute();
            
            $updateCount++;
        }
        
        // Commit all changes
        $conn->commit();
        logMessage("Successfully updated $updateCount reservations to 'ended' status");
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw new Exception("Transaction failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    exit(1);
}

logMessage('Reservation status update process completed'); 