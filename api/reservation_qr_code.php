<?php
// Prevent PHP errors from being output
ini_set('display_errors', '0'); // Turn off display errors to prevent HTML in JSON response
ini_set('log_errors', '1');

// Suppress deprecated warnings which cause issues with QR code generation
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Include database connection
require_once __DIR__ . '/../includes/db.php';
// Include QR code library
require_once '../includes/phpqrcode/qrlib.php';

/**
 * Generates a QR code for a reservation
 * 
 * @param string $reservation_code Unique reservation code
 * @param int $user_id User ID
 * @param int $reservation_id Reservation ID
 * @return string Path to the generated QR code image
 */
function generateReservationQR($reservation_code, $user_id, $reservation_id) {
    // Create directory if it doesn't exist
    $qr_dir = 'C:/xampp/htdocs/Cinema_Reservation/uploads/qrcodes/';
    if (!file_exists($qr_dir)) {
        mkdir($qr_dir, 0755, true);
    }
    
    // QR code filename - using reservation ID and code to ensure uniqueness
    $qr_filename = $qr_dir . 'qr_' . $reservation_id . '_' . $user_id . '_' . time() . '.png';
    
    // Data to encode in QR code
    // Include reservation details that can be verified
    $qr_data = json_encode([
        'reservation_code' => $reservation_code,
        'user_id' => $user_id,
        'reservation_id' => $reservation_id,
        'timestamp' => time()
    ]);
    
    // Generate QR code - capture and suppress any output
    ob_start();
    QRcode::png($qr_data, $qr_filename, QR_ECLEVEL_H, 10);
    ob_end_clean();
    
    // Return path relative to website root for use in HTML
    return '/Cinema_Reservation/uploads/qrcodes/qr_' . $reservation_id . '_' . $user_id . '_' . time() . '.png';
}

/**
 * Verifies a reservation using the QR code data
 * 
 * @param string $qr_data QR code data (typically from scanning)
 * @param mysqli $conn Database connection
 * @return array Result of verification with status and message
 */
function verifyReservationQR($qr_data, $conn) {
    try {
        // Decode QR data
        $data = json_decode($qr_data, true);
        
        if (!$data || !isset($data['reservation_code']) || !isset($data['reservation_id'])) {
            return ['status' => false, 'message' => 'Invalid QR code data'];
        }
        
        // Verify reservation exists and is valid
        $query = "SELECT r.*, s.date, s.start_time, s.end_time, m.title, 
                TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration
                FROM reservations r
                JOIN schedules s ON r.schedule_id = s.id
                JOIN movies m ON s.movie_id = m.id
                WHERE r.id = ? AND r.reservation_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $data['reservation_id'], $data['reservation_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        
        if (!$reservation) {
            return ['status' => false, 'message' => 'Reservation not found'];
        }
        
        // Check reservation status
        if ($reservation['status'] === 'verified') {
            return ['status' => false, 'message' => 'Reservation already verified', 'reservation' => $reservation];
        } else if ($reservation['status'] === 'cancelled') {
            return ['status' => false, 'message' => 'Reservation has been cancelled', 'reservation' => $reservation];
        } else if ($reservation['status'] === 'expired') {
            return ['status' => false, 'message' => 'Reservation has expired', 'reservation' => $reservation];
        }
        
        // Import the checkVerificationTiming function if it's not already available
        if (!function_exists('checkVerificationTiming')) {
            // Use the same timing check logic as in verify_reservation.php
            $showDateTime = strtotime($reservation['date'] . ' ' . $reservation['start_time']);
            $currentTime = time();
            $timeUntilShow = $showDateTime - $currentTime;
            $twoHoursInSeconds = 2 * 60 * 60;
            
            // Calculate grace period (2/3 of movie duration)
            $movieDurationMinutes = isset($reservation['duration']) ? $reservation['duration'] : 120; // Default 2 hours
            $gracePeriodMinutes = ceil($movieDurationMinutes * 2 / 3);
            $gracePeriodSeconds = $gracePeriodMinutes * 60;
            
            if ($timeUntilShow > $twoHoursInSeconds) {
                // Too early to verify
                $movieDateTime = date('F d, Y', strtotime($reservation['date'])) . ' at ' . date('h:i A', $showDateTime);
                return [
                    'status' => false, 
                    'message' => "Too early to verify. This reservation is for $movieDateTime, which is " . 
                                ceil($timeUntilShow / 3600) . " hours away. Please verify within 2 hours of showtime.",
                    'reservation' => $reservation
                ];
            } else if ($timeUntilShow < 0) {
                // Movie has already started
                if ($timeUntilShow > -$gracePeriodSeconds) { // Within grace period after start time
                    // Update will happen below - this is okay
                } else {
                    // Beyond grace period
                    return [
                        'status' => false, 
                        'message' => "Verification failed. Movie started " . ceil(abs($timeUntilShow) / 60) . 
                                    " minutes ago, which exceeds the grace period of $gracePeriodMinutes minutes.",
                        'reservation' => $reservation
                    ];
                }
            }
            // Otherwise, timing is perfect - continue verification
        } else {
            // The function exists, so use it
            require_once __DIR__ . '/../admin_files/verify_reservation.php';
            $timingCheck = checkVerificationTiming($reservation);
            if ($timingCheck['status'] === false) {
                return [
                    'status' => false,
                    'message' => $timingCheck['message'],
                    'reservation' => $reservation
                ];
            }
        }
        
        // Update reservation status to verified
        $query = "UPDATE reservations SET status = 'verified' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $data['reservation_id']);
        $stmt->execute();
        
        return [
            'status' => true, 
            'message' => 'Reservation verified successfully', 
            'reservation' => $reservation
        ];
        
    } catch (Exception $e) {
        return ['status' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
?>