<?php
// Prevent PHP errors from being output
ini_set('display_errors', '0'); // Turn off display errors to prevent HTML in JSON response
ini_set('log_errors', '1');

// Suppress deprecated warnings which cause issues with QR code generation
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// First, let's capture any output that might be happening before headers are sent
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db.php';

// Include status checker utility and run it
require_once '../includes/status_checker.php';
checkAndUpdateReservationStatuses($conn);

// Capture and discard any output up to this point
$output = ob_get_clean();
if (!empty($output)) {
    // Log the unexpected output for debugging
    error_log("Unexpected output before JSON in get_reservation_details.php: " . $output);
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get reservation ID from query string
$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reservation_id <= 0) {
    echo json_encode(['error' => 'Invalid reservation ID']);
    exit;
}

try {
    // Modified query to handle payment_method correctly
    // Removed reference to p.name since it's causing an error
    $query = "
        SELECT r.*, s.date, s.start_time, s.end_time, m.title as movie_title, m.poster, c.name as cinema_name,
               r.payment_method_id
        FROM reservations r
        JOIN schedules s ON r.schedule_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN cinemas c ON s.cinema_id = c.id
        WHERE r.id = ? AND r.user_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $reservation_id, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Reservation not found']);
        exit;
    }
    
    $reservation = $result->fetch_assoc();
    
    // If payment_method_id exists, try to get the payment method name separately
    if (!empty($reservation['payment_method_id'])) {
        try {
            // Check if payment_methods table and name column exist
            $paymentMethodQuery = "SHOW COLUMNS FROM payment_methods LIKE 'name'";
            $paymentResult = $conn->query($paymentMethodQuery);
            
            if ($paymentResult && $paymentResult->num_rows > 0) {
                // The column exists, so we can query it
                $paymentQuery = "SELECT name FROM payment_methods WHERE id = ?";
                $paymentStmt = $conn->prepare($paymentQuery);
                $paymentStmt->bind_param("i", $reservation['payment_method_id']);
                $paymentStmt->execute();
                $paymentResult = $paymentStmt->get_result();
                
                if ($paymentResult && $paymentResult->num_rows > 0) {
                    $paymentRow = $paymentResult->fetch_assoc();
                    $reservation['payment_method'] = $paymentRow['name'];
                } else {
                    $reservation['payment_method'] = 'Unknown';
                }
                $paymentStmt->close();
            } else {
                // The column doesn't exist, use a placeholder
                $reservation['payment_method'] = 'Payment #' . $reservation['payment_method_id'];
            }
        } catch (Exception $e) {
            // If there's any error with payment method, just use a fallback
            $reservation['payment_method'] = 'Payment #' . $reservation['payment_method_id'];
            error_log('Error retrieving payment method: ' . $e->getMessage());
        }
    } else {
        $reservation['payment_method'] = 'Not specified';
    }
    
    // Get seats for this reservation
    $seatQuery = "
        SELECT s.seat_number 
        FROM reservation_details rd
        JOIN seats s ON rd.seat_id = s.id
        WHERE rd.reservation_id = ?
    ";
    
    $seatStmt = $conn->prepare($seatQuery);
    $seatStmt->bind_param("i", $reservation_id);
    $seatStmt->execute();
    
    $seatResult = $seatStmt->get_result();
    $seats = [];
    
    while ($seatRow = $seatResult->fetch_assoc()) {
        $seats[] = $seatRow['seat_number'];
    }
    
    $reservation['seats'] = $seats;
    
    // Check for QR code, generate if missing
    if (empty($reservation['qr_code']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . $reservation['qr_code'])) {
        // Generate a new QR code
        try {
            require_once './reservation_qr_code.php';
            // Use output buffering to capture any output generated during QR code creation
            ob_start();
            $qr_path = generateReservationQR($reservation['reservation_code'], $user_id, $reservation_id);
            ob_end_clean();
            $reservation['qr_code'] = $qr_path;
            
            // Update the reservation with the new QR code path
            $updateQuery = "UPDATE reservations SET qr_code = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $qr_path, $reservation_id);
            $updateStmt->execute();
            $updateStmt->close();
        } catch (Exception $e) {
            // If QR generation fails, use a fallback image
            $reservation['qr_code'] = '/assets/images/qr-placeholder.png';
            error_log('QR code generation failed: ' . $e->getMessage());
        }
    }
    
    echo json_encode(['reservation' => $reservation]);
    
    $stmt->close();
    $seatStmt->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error retrieving reservation details: ' . $e->getMessage()]);
}
?>