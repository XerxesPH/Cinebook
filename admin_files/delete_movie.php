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

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if movie ID is provided
if (!isset($_POST['movie_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Movie ID is required']);
    exit;
}

// Get and sanitize movie ID
$movieId = mysqli_real_escape_string($conn, $_POST['movie_id']);

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // First get the movie title for the given ID
    $getTitleQuery = "SELECT title FROM movies WHERE id = '$movieId'";
    $titleResult = mysqli_query($conn, $getTitleQuery);
    
    if (!$titleResult || mysqli_num_rows($titleResult) === 0) {
        throw new Exception('Movie not found');
    }
    
    $movieData = mysqli_fetch_assoc($titleResult);
    $movieTitle = mysqli_real_escape_string($conn, $movieData['title']);
    
    // Get all movie IDs with the same title
    $getIdsQuery = "SELECT id FROM movies WHERE title = '$movieTitle'";
    $idsResult = mysqli_query($conn, $getIdsQuery);
    
    if (!$idsResult) {
        throw new Exception('Failed to find movies with the same title: ' . mysqli_error($conn));
    }
    
    $movieIds = [];
    while ($row = mysqli_fetch_assoc($idsResult)) {
        $movieIds[] = $row['id'];
    }
    
    // Check for existing reservations for any of these movies
    $movieIdList = "'" . implode("','", $movieIds) . "'";
    $checkReservationsQuery = "SELECT COUNT(*) as count FROM reservations r
                              JOIN schedules sch ON r.schedule_id = sch.id
                              WHERE sch.movie_id IN ($movieIdList)";
    $result = mysqli_query($conn, $checkReservationsQuery);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        // If there are reservations, soft delete all movies with this title by updating is_available flag
        $softDeleteQuery = "UPDATE movies SET is_available = 0, updated_at = NOW() WHERE title = '$movieTitle'";
        if (!mysqli_query($conn, $softDeleteQuery)) {
            throw new Exception('Failed to soft delete movies: ' . mysqli_error($conn));
        }
    } else {
        // If no reservations, we can safely delete related schedules and then all movies with this title
        
        // Delete schedules for all movies with this title
        $deleteSchedulesQuery = "DELETE FROM schedules WHERE movie_id IN ($movieIdList)";
        if (!mysqli_query($conn, $deleteSchedulesQuery)) {
            throw new Exception('Failed to delete schedules: ' . mysqli_error($conn));
        }
        
        // Get the movie poster paths before deleting the records
        $posterQuery = "SELECT id, poster FROM movies WHERE title = '$movieTitle'";
        $posterResult = mysqli_query($conn, $posterQuery);
        $posterPaths = [];
        while ($posterRow = mysqli_fetch_assoc($posterResult)) {
            if ($posterRow['poster']) {
                $posterPaths[$posterRow['id']] = $posterRow['poster'];
            }
        }
        
        // Delete all movies with this title
        $deleteMovieQuery = "DELETE FROM movies WHERE title = '$movieTitle'";
        if (!mysqli_query($conn, $deleteMovieQuery)) {
            throw new Exception('Failed to delete movies: ' . mysqli_error($conn));
        }
        
        // Delete the poster files if they exist
        foreach ($posterPaths as $id => $path) {
            if (file_exists('../' . $path)) {
                unlink('../' . $path);
            }
        }
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'All instances of movie "' . htmlspecialchars($movieTitle) . '" deleted successfully']);
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>