<?php
// api/get_seats.php
// Returns seats for a specific screening

// Enable error logging
ini_set('display_errors', '0'); // Don't show errors to users
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Get parameters
$screening_id = isset($_GET['screening_id']) ? intval($_GET['screening_id']) : 0;

// Log the request
error_log("get_seats.php accessed with screening_id: $screening_id");

// Validate parameters
if ($screening_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid screening ID'
    ]);
    exit;
}

try {
    // First get the cinema_id for this screening
    $stmtScreening = $conn->prepare("
        SELECT cinema_id, movie_id, date, start_time 
        FROM schedules 
        WHERE id = ?
    ");
    
    $stmtScreening->bind_param("i", $screening_id);
    $stmtScreening->execute();
    $resultScreening = $stmtScreening->get_result();
    
    if ($resultScreening->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Screening not found'
        ]);
        exit;
    }
    
    $screeningData = $resultScreening->fetch_assoc();
    $cinema_id = $screeningData['cinema_id'];
    
    // Log screening data for debugging
    error_log("Screening data: " . json_encode($screeningData));
    
    // Get all existing reservations for this screening to verify
    $stmtCheckReservations = $conn->prepare("
        SELECT r.id, r.status, r.schedule_id, rd.seat_id, s.seat_number
        FROM reservations r
        JOIN reservation_details rd ON r.id = rd.reservation_id
        JOIN seats s ON rd.seat_id = s.id
        WHERE r.schedule_id = ?
    ");
    
    $stmtCheckReservations->bind_param("i", $screening_id);
    $stmtCheckReservations->execute();
    $resultReservations = $stmtCheckReservations->get_result();
    
    $existingReservations = [];
    while ($row = $resultReservations->fetch_assoc()) {
        $existingReservations[] = $row;
    }
    
    // Log all existing reservations to debug
    error_log("Found " . count($existingReservations) . " reservation records for screening ID $screening_id: " . json_encode($existingReservations));
    
    // Improved query to get seats for this cinema with more accurate reservation detection
    $stmtSeats = $conn->prepare("
        SELECT 
            s.id as seat_id,
            s.seat_number, 
            CASE 
                -- Check if the seat has ANY reservation for this screening
                WHEN EXISTS (
                    SELECT 1 
                    FROM reservation_details rd 
                    JOIN reservations r ON rd.reservation_id = r.id 
                    WHERE rd.seat_id = s.id AND r.schedule_id = ?
                ) THEN 'reserved'
                ELSE s.status 
            END as status
        FROM seats s
        WHERE s.cinema_id = ?
        ORDER BY s.seat_number
    ");
    
    $stmtSeats->bind_param("ii", $screening_id, $cinema_id);
    $stmtSeats->execute();
    $resultSeats = $stmtSeats->get_result();
    
    $seats = [];
    while ($row = $resultSeats->fetch_assoc()) {
        $seats[] = [
            'seat_id' => $row['seat_id'],
            'seat_number' => $row['seat_number'],
            'status' => $row['status']
        ];
    }
    
    // Get cinema details to know how many rows/seats
    $stmtCinema = $conn->prepare("
        SELECT total_seats
        FROM cinemas
        WHERE id = ?
    ");
    
    $stmtCinema->bind_param("i", $cinema_id);
    $stmtCinema->execute();
    $resultCinema = $stmtCinema->get_result();
    $cinemaData = $resultCinema->fetch_assoc();
    
    // For this example, let's assume all cinemas have 8 rows with 15 seats per row
    $rows = 8;
    $seatsPerRow = 15;
    
    // If there are no seats in the database yet, return default layout
    if (count($seats) === 0) {
        // Create default seat layout (all available)
        $seats = [];
        for ($row = 1; $row <= $rows; $row++) {
            $rowLetter = chr(64 + $row); // A=65, B=66, etc.
            for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                $seatId = $rowLetter . $seat;
                $seats[] = [
                    'seat_number' => $seatId,
                    'status' => 'available'
                ];
            }
        }
    }
    
    // Log detailed info about reserved seats for debugging
    $reservedSeats = array_filter($seats, function($seat) {
        return $seat['status'] === 'reserved';
    });
    error_log("Returning " . count($reservedSeats) . " reserved seats for screening ID $screening_id: " . json_encode($reservedSeats));
    
    echo json_encode([
        'success' => true,
        'seatLayout' => [
            'rows' => $rows,
            'seatsPerRow' => $seatsPerRow
        ],
        'seats' => $seats
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_seats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>