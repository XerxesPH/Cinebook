<?php
// api/get_available_times.php
// Returns available times for a movie in a specific cinema on a specific date

header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Get parameters
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Validate parameters
if ($cinema_id <= 0 || $movie_id <= 0 || empty($date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

try {
    // Query to get available times for this movie/cinema/date combination
    $stmt = $conn->prepare("
        SELECT id, start_time, end_time
        FROM schedules 
        WHERE cinema_id = ? 
        AND movie_id = ? 
        AND date = ? 
        AND is_available = 1
        ORDER BY start_time ASC
    ");
    
    $stmt->bind_param("iis", $cinema_id, $movie_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $times = [];
    while ($row = $result->fetch_assoc()) {
        $times[] = [
            'id' => $row['id'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'times' => $times
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>