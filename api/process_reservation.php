<?php
// Prevent PHP errors from being output
// Set these at the very top of the file
ini_set('display_errors', '0'); // Turn off display errors to prevent HTML in JSON response
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Function to send error response and exit
function sendErrorResponse($message, $status_code = 400) {
    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit();
}

// Include database connection
try {
    require_once __DIR__ . '/../includes/db.php';
    // Include QR code generator
    require_once './reservation_qr_code.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
    sendErrorResponse("System error. Please try again later.");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendErrorResponse('You must be logged in to make a reservation.', 401);
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON data from request
        $json_data = file_get_contents('php://input');
        if (!$json_data) {
            sendErrorResponse("No data received.");
        }
        
        $data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            sendErrorResponse("Invalid JSON data: " . json_last_error_msg());
        }
        
        // Log the received data for debugging
        error_log("Received reservation data: " . print_r($data, true));
        
        // Get data from request
        $user_id = $_SESSION['user_id'];
        $schedule_id = isset($data['schedule_id']) ? (int)$data['schedule_id'] : null;
        $payment_method_id = $data['payment_method_id'] ?? null;
        $seats = $data['seats'] ?? [];
        $total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0;
        $movie_id = isset($data['movie_id']) ? (int)$data['movie_id'] : null;
        $cinema_id = isset($data['cinema_id']) ? (int)$data['cinema_id'] : null;
        $show_date = $data['show_date'] ?? null;
        $show_time = $data['start_time'] ?? $data['show_time'] ?? null;
        
        // Basic validation
        if (empty($seats) || !is_array($seats)) {
            sendErrorResponse("No seats selected.");
        }
        
        if (!$payment_method_id) {
            sendErrorResponse("Payment method is required.");
        }
        
        if (!$schedule_id && (!$movie_id || !$cinema_id || !$show_date || !$show_time)) {
            sendErrorResponse("Scheduling information is incomplete.");
        }
        
        // Validate or lookup schedule_id
        try {
            if ($schedule_id) {
                $current_date = date('Y-m-d');
                $query = "SELECT id FROM schedules WHERE id = ? AND is_available = 1";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $stmt->bind_param("i", $schedule_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // If schedule_id is invalid, try lookup from other details
                    if ($movie_id && $cinema_id && $show_date && $show_time) {
                        $schedule_id = lookupScheduleId($conn, $movie_id, $cinema_id, $show_date, $show_time);
                        
                        if (!$schedule_id) {
                            throw new Exception("Invalid schedule ID and unable to find a matching schedule.");
                        }
                    } else {
                        throw new Exception("Invalid schedule selected. Please try again with a valid schedule.");
                    }
                }
            } else {
                // No schedule_id provided, try to look it up from other details
                $schedule_id = lookupScheduleId($conn, $movie_id, $cinema_id, $show_date, $show_time);
                
                if (!$schedule_id) {
                    throw new Exception("Unable to find a valid schedule for the selected movie, cinema, date and time.");
                }
            }
        } catch (Exception $e) {
            error_log("Schedule validation error: " . $e->getMessage());
            sendErrorResponse($e->getMessage());
        }
        
        // Log the found schedule_id
        error_log("Using schedule_id: " . $schedule_id);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Generate unique reservation code
            $reservation_code = 'RSV-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
            
            // Insert reservation record (without QR code first)
            $query = "INSERT INTO reservations (user_id, schedule_id, reservation_code, 
                     payment_method_id, total_amount, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("iissd", $user_id, $schedule_id, $reservation_code, $payment_method_id, $total_amount);
            $stmt->execute();
            
            // Get the reservation ID
            $reservation_id = $conn->insert_id;
            
            // Now generate QR code
            $qr_code_path = generateReservationQR($reservation_code, $user_id, $reservation_id);
            
            // Update reservation with QR code path
            $query = "UPDATE reservations SET qr_code = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("si", $qr_code_path, $reservation_id);
            $stmt->execute();
            
            // Process each selected seat
            foreach ($seats as $seat_number) {
                // First, check if the seat exists for this cinema
                $query = "SELECT id FROM seats WHERE cinema_id = ? AND seat_number = ?";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $stmt->bind_param("is", $cinema_id, $seat_number);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Seat exists, get its ID
                    $seat = $result->fetch_assoc();
                    $seat_id = $seat['id'];
                    
                    // Check if this seat is already reserved for this screening
                    $query = "SELECT rd.id 
                              FROM reservation_details rd 
                              JOIN reservations r ON rd.reservation_id = r.id 
                              WHERE rd.seat_id = ? 
                              AND r.schedule_id = ? 
                              AND r.status IN ('pending', 'confirmed', 'verified')";
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    $stmt->bind_param("ii", $seat_id, $schedule_id);
                    $stmt->execute();
                    $reservationCheck = $stmt->get_result();
                    
                    if ($reservationCheck->num_rows > 0) {
                        // This seat is already reserved for this screening
                        throw new Exception("Seat $seat_number is already reserved. Please select another seat.");
                    }
                } else {
                    // Seat doesn't exist, create it
                    $query = "INSERT INTO seats (cinema_id, seat_number, status) VALUES (?, ?, 'available')";
                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    $stmt->bind_param("is", $cinema_id, $seat_number);
                    $stmt->execute();
                    $seat_id = $conn->insert_id;
                }
                
                // Add reservation detail
                $query = "INSERT INTO reservation_details (reservation_id, seat_id) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                $stmt->bind_param("ii", $reservation_id, $seat_id);
                $stmt->execute();
            }
            
            // Create notification for user
            $notification_title = 'Reservation Confirmed';
            $notification_message = 'Your reservation with code ' . $reservation_code . ' has been confirmed.';
            $notification_type = 'reservation';
            
            $query = "INSERT INTO user_notifications (user_id, reservation_id, title, message, type) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("iisss", $user_id, $reservation_id, $notification_title, $notification_message, $notification_type);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => "Reservation successful! Your reservation code is: " . $reservation_code,
                'reservation_id' => $reservation_id,
                'reservation_code' => $reservation_code
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Transaction error: " . $e->getMessage());
            sendErrorResponse($e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log("Reservation error: " . $e->getMessage());
        sendErrorResponse($e->getMessage());
    }
} else {
    sendErrorResponse("Invalid request method", 405);
}

/**
 * Function to look up schedule_id from provided details
 * 
 * @param mysqli $conn Database connection
 * @param int $movie_id Movie ID
 * @param int $cinema_id Cinema ID
 * @param string $show_date Show date
 * @param string $start_time Start time
 * @return int|null Schedule ID or null if not found
 */
function lookupScheduleId($conn, $movie_id, $cinema_id, $show_date, $start_time) {
    // Format date to match database format if needed
    try {
        $formatted_date = date('Y-m-d', strtotime($show_date));
        
        // Revised query to simplify schedule lookup
        $query = "SELECT id FROM schedules 
                  WHERE movie_id = ? 
                  AND cinema_id = ? 
                  AND date = ?
                  AND start_time = ?
                  AND is_available = 1";
                  
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("iiss", $movie_id, $cinema_id, $formatted_date, $start_time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Log the failed lookup
            error_log("Schedule lookup failed for movie_id: $movie_id, cinema_id: $cinema_id, date: $formatted_date, time: $start_time");
            return null; // No matching schedule found
        }
        
        $schedule = $result->fetch_assoc();
        return $schedule['id'];
    } catch (Exception $e) {
        error_log("Schedule lookup error: " . $e->getMessage());
        return null;
    }
}
?>