<?php
// api/get_movie_cinema_info.php
// Returns details about a specific movie and cinema

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
    // Get cinema details
    $stmtCinema = $conn->prepare("
        SELECT id, name, total_seats
        FROM cinemas
        WHERE id = ?
    ");
    
    $stmtCinema->bind_param("i", $cinema_id);
    $stmtCinema->execute();
    $resultCinema = $stmtCinema->get_result();
    
    if ($resultCinema->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cinema not found'
        ]);
        exit;
    }
    
    $cinema = $resultCinema->fetch_assoc();
    
    // Get movie details
    $stmtMovie = $conn->prepare("
        SELECT id, title, synopsis, genre, duration, rating
        FROM movies
        WHERE id = ?
    ");
    
    $stmtMovie->bind_param("i", $movie_id);
    $stmtMovie->execute();
    $resultMovie = $stmtMovie->get_result();
    
    if ($resultMovie->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Movie not found'
        ]);
        exit;
    }
    
    $movie = $resultMovie->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'cinema' => $cinema,
        'movie' => $movie
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>