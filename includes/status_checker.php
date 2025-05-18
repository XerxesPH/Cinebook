<?php
/**
 * Status Checker - Automatically updates reservation statuses
 * - Updates 'verified' status for reservations that have ended
 */

function checkAndUpdateReservationStatuses($conn) {
    try {
        // Get current date and time in server timezone
        $currentDateTime = date('Y-m-d H:i:s');
        
        // First, let's check if there are any pending reservations with schedules that have ended
        $query = "
            SELECT r.id, r.status, s.date, s.end_time,
                   CONCAT(s.date, ' ', s.end_time) AS end_datetime
            FROM reservations r
            JOIN schedules s ON r.schedule_id = s.id
            WHERE r.status = 'pending'
              AND CONCAT(s.date, ' ', s.end_time) < ?
        ";
        
        $stmt = $conn->prepare($query);
        
        // Check if prepare was successful
        if (!$stmt) {
            error_log("Error preparing statement in status checker: " . $conn->error);
            return; // Exit function if prepare fails
        }
        
        $stmt->bind_param('s', $currentDateTime);
        
        // Execute the query
        if (!$stmt->execute()) {
            error_log("Error executing statement in status checker: " . $stmt->error);
            $stmt->close();
            return; // Exit function if execute fails
        }
        
        $result = $stmt->get_result();
        
        // Check if we got a valid result
        if (!$result) {
            error_log("Error getting result in status checker: " . $stmt->error);
            $stmt->close();
            return; // Exit function if result is not valid
        }
        
        // Store the expired reservation IDs
        $expiredReservationIds = [];
        
        // Process each expired reservation
        while ($row = $result->fetch_assoc()) {
            $expiredReservationIds[] = $row['id'];
        }
        
        $stmt->close();
        
        // If we have expired reservations, update their status
        if (!empty($expiredReservationIds)) {
            // Convert array to comma-separated string for SQL IN clause
            $idsString = implode(',', $expiredReservationIds);
            
            // Update the status
            $updateQuery = "
                UPDATE reservations 
                SET status = 'verified', 
                    updated_at = NOW() 
                WHERE id IN ($idsString)
            ";
            
            // Use prepared statement with multiple parameters for better security
            $updateStmt = $conn->prepare($updateQuery);
            
            if (!$updateStmt) {
                error_log("Error preparing update statement in status checker: " . $conn->error);
                return; // Exit function if prepare fails
            }
            
            if (!$updateStmt->execute()) {
                error_log("Error executing update statement in status checker: " . $updateStmt->error);
                $updateStmt->close();
                return; // Exit function if execute fails
            }
            
            // Log the number of updated reservations
            $affectedRows = $updateStmt->affected_rows;
            if ($affectedRows > 0) {
                error_log("Updated $affectedRows expired reservation(s) to 'verified' status");
            }
            
            $updateStmt->close();
        }
        
        return true; // Return success
        
    } catch (Exception $e) {
        error_log("Exception in checkAndUpdateReservationStatuses: " . $e->getMessage());
        return false; // Return failure
    }
}