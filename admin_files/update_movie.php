
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

// Get and sanitize movie data
$movieId = mysqli_real_escape_string($conn, $_POST['movie_id']);
$title = mysqli_real_escape_string($conn, $_POST['title']);
$description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
$genre = isset($_POST['genre']) ? mysqli_real_escape_string($conn, $_POST['genre']) : '';
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
$releaseDate = isset($_POST['release_date']) ? mysqli_real_escape_string($conn, $_POST['release_date']) : '';
$cinemaId = isset($_POST['cinema_id']) ? (int)$_POST['cinema_id'] : 0;
$startShowing = isset($_POST['start_showing']) ? mysqli_real_escape_string($conn, $_POST['start_showing']) : '';
$endShowing = isset($_POST['end_showing']) ? mysqli_real_escape_string($conn, $_POST['end_showing']) : '';
$currentImage = isset($_POST['current_image']) ? $_POST['current_image'] : '';

// Validate required fields
if (empty($title) || empty($startShowing) || empty($endShowing)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Required fields cannot be empty']);
    exit;
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Handle image upload if a new image is provided
    $posterPath = $currentImage;
    if (isset($_FILES['poster_image']) && $_FILES['poster_image']['size'] > 0) {
        $uploadDir = '../images/movies/';
        $fileName = time() . '_' . basename($_FILES['poster_image']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        // Check if the file is an image
        $imageFileType = strtolower(pathinfo($uploadPath, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
        }
        
        // Upload the file
        if (!move_uploaded_file($_FILES['poster_image']['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload image');
        }
        
        $posterPath = 'images/movies/' . $fileName;
    }
    
    // Update movie record
    $updateMovieQuery = "UPDATE movies SET 
                        title = '$title', 
                        description = '$description', 
                        genre = '$genre', 
                        duration = $duration, 
                        release_date = '$releaseDate', 
                        poster = '$posterPath', 
                        updated_at = NOW() 
                        WHERE id = '$movieId'";
    
    if (!mysqli_query($conn, $updateMovieQuery)) {
        throw new Exception('Failed to update movie: ' . mysqli_error($conn));
    }
    
    // Update schedule records
    $updateScheduleQuery = "UPDATE schedules SET 
                           cinema_id = $cinemaId, 
                           show_date = '$startShowing', 
                           end_date = '$endShowing', 
                           updated_at = NOW() 
                           WHERE movie_id = '$movieId'";
    
    if (!mysqli_query($conn, $updateScheduleQuery)) {
        throw new Exception('Failed to update schedule: ' . mysqli_error($conn));
    }
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Movie updated successfully']);
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>