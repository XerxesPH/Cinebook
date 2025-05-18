
<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Check if ID parameter exists
if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Movie ID is required']);
    exit;
}

// Get movie ID and sanitize
$movieId = mysqli_real_escape_string($conn, $_GET['id']);

// Query to get movie details
$query = "SELECT m.*, MIN(sch.show_date) as start_showing, MAX(sch.end_date) as end_showing, sch.cinema_id 
          FROM movies m
          JOIN schedules sch ON m.id = sch.movie_id
          WHERE m.id = '$movieId'
          GROUP BY m.id";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'Movie not found']);
    exit;
}

// Fetch movie data
$movie = mysqli_fetch_assoc($result);

// Return movie data as JSON
header('Content-Type: application/json');
echo json_encode([
    'id' => $movie['id'],
    'title' => $movie['title'],
    'description' => $movie['description'] ?? '',
    'genre' => $movie['genre'] ?? '',
    'duration' => $movie['duration'] ?? '',
    'release_date' => $movie['release_date'] ?? '',
    'image' => $movie['poster'] ?? '',
    'cinema_id' => $movie['cinema_id'] ?? '',
    'start_showing' => $movie['start_showing'] ?? '',
    'end_showing' => $movie['end_showing'] ?? '',
    'success' => true
]);
exit;
?>