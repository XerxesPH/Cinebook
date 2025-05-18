<?php
// Returns available dates for a movie in a specific cinema

header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Get parameters
$cinema_id = isset($_GET['cinema_id']) ? intval($_GET['cinema_id']) : 0;
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// Validate parameters
if ($cinema_id <= 0 || $movie_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid cinema or movie ID'
    ]);
    exit;
}

try {
    // Query to get available dates for this movie/cinema combination
    $stmt = $conn->prepare("
        SELECT DISTINCT date 
        FROM schedules 
        WHERE cinema_id = ? 
        AND movie_id = ? 
        AND date >= CURDATE() 
        AND is_available = 1
        ORDER BY date ASC
    ");
    
    $stmt->bind_param("ii", $cinema_id, $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = [
            'date' => $row['date']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'dates' => $dates
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>