<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reservation_id']) || !is_numeric($data['reservation_id'])) {
    echo json_encode(['error' => 'Invalid reservation ID']);
    exit;
}

$reservation_id = (int)$data['reservation_id'];
$user_id = $_SESSION['user_id'];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Check if reservation exists and belongs to this user
    $query = "
        SELECT * FROM reservations 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $reservation_id, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    
    if (!$reservation) {
        $conn->rollback();
        echo json_encode(['error' => 'Reservation not found, does not belong to this user, or cannot be cancelled']);
        exit;
    }
    
    // Get the seat IDs associated with this reservation
    $seatQuery = "
        SELECT seat_id FROM reservation_details 
        WHERE reservation_id = ?
    ";
    
    $seatStmt = $conn->prepare($seatQuery);
    $seatStmt->bind_param("i", $reservation_id);
    $seatStmt->execute();
    $seatResult = $seatStmt->get_result();
    
    $seatIds = [];
    while ($row = $seatResult->fetch_assoc()) {
        $seatIds[] = $row['seat_id'];
    }
    
    // Update reservation status to cancelled
    $updateQuery = "
        UPDATE reservations 
        SET status = 'cancelled' 
        WHERE id = ?
    ";
    
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $reservation_id);
    $success1 = $updateStmt->execute();
    
    if (!$success1) {
        throw new Exception("Failed to update reservation status: " . $updateStmt->error);
    }
    
    // Now update the reservation details status
    $detailsQuery = "
        UPDATE reservation_details 
        SET status = 'cancelled' 
        WHERE reservation_id = ?
    ";
    
    $detailsStmt = $conn->prepare($detailsQuery);
    $detailsStmt->bind_param("i", $reservation_id);
    $success2 = $detailsStmt->execute();
    
    if (!$success2) {
        // If there's an error, log it but don't stop the transaction
        // This might happen if the status column doesn't exist yet
        error_log("Warning: Could not update reservation details status: " . $detailsStmt->error);
    }
    
    // Update the seat status to 'available' for all seats in this reservation
    if (!empty($seatIds)) {
        foreach ($seatIds as $seatId) {
            $seatUpdateQuery = "
                UPDATE seats 
                SET status = 'available' 
                WHERE id = ?
            ";
            
            $seatUpdateStmt = $conn->prepare($seatUpdateQuery);
            $seatUpdateStmt->bind_param("i", $seatId);
            $seatUpdateStmt->execute();
            $seatUpdateStmt->close();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
    
    // Close statements
    if (isset($seatStmt)) {
        $seatStmt->close();
    }
    if (isset($detailsStmt)) {
        $detailsStmt->close();
    }
    $updateStmt->close();
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['error' => 'Error cancelling reservation: ' . $e->getMessage()]);
}
?>