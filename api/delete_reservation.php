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
    
    // Check if reservation exists, belongs to this user, and is already cancelled
    $query = "
        SELECT * FROM reservations 
        WHERE id = ? AND user_id = ? AND (status = 'cancelled' OR status = 'verified')
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $reservation_id, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    
    if (!$reservation) {
        $conn->rollback();
        echo json_encode(['error' => 'Reservation not found, does not belong to this user, or is not in a deletable state']);
        exit;
    }
    
    // Delete from reservation_details first (foreign key constraint)
    $detailsQuery = "DELETE FROM reservation_details WHERE reservation_id = ?";
    $detailsStmt = $conn->prepare($detailsQuery);
    $detailsStmt->bind_param("i", $reservation_id);
    $detailsStmt->execute();
    
    // Delete the reservation
    $deleteQuery = "DELETE FROM reservations WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $reservation_id);
    $deleteStmt->execute();
    
    // Check if any QR codes exist for this reservation and delete them
    $qr_code_file = 'C:/xampp/htdocs/Cinema_Reservation/uploads/qrcodes/qr_' . $reservation_id . '_' . $user_id . '_*.png';
    $matching_files = glob($qr_code_file);
    
    if (!empty($matching_files)) {
        foreach ($matching_files as $file) {
            if (file_exists($file)) {
                unlink($file); // Delete the QR code file
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully']);
    
    if (isset($detailsStmt)) {
        $detailsStmt->close();
    }
    if (isset($deleteStmt)) {
        $deleteStmt->close();
    }
    $stmt->close();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['error' => 'Error deleting reservation: ' . $e->getMessage()]);
}
?> 