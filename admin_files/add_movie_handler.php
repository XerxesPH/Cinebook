<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); // Don't display errors directly in the output
error_reporting(E_ALL); // Report all PHP errors

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Process form data
try {
    // Get form data
    $title = mysqli_real_escape_string($conn, $_POST['movie-title']);
    $synopsis = mysqli_real_escape_string($conn, $_POST['movie-description']);
    $genre = mysqli_real_escape_string($conn, $_POST['movie-genre']);
    $duration = (int)$_POST['movie-duration'];
    $release_date = $_POST['release-date'];
    $rating = mysqli_real_escape_string($conn, $_POST['movie-rating']);
    $cinema_id = (int)$_POST['movie-cinema'];
    $show_start_date = $_POST['show-start-date'];
    $show_end_date = $_POST['show-end-date'];
    $showtimes = $_POST['showtimes'] ?? [];


    $hours = isset($_POST['movie-duration-hours']) ? (int)$_POST['movie-duration-hours'] : 0;
    $minutes = isset($_POST['movie-duration-minutes']) ? (int)$_POST['movie-duration-minutes'] : 0;
    $duration = ($hours * 60) + $minutes;
    
    // Validate duration
    if ($duration < 30 || $duration > 300) {
        throw new Exception('Movie duration must be between 30 and 300 minutes.');
    }
    
    // New fields
    $trailer_url = isset($_POST['movie-trailer']) ? mysqli_real_escape_string($conn, $_POST['movie-trailer']) : '';
    $is_featured = isset($_POST['is-featured']) ? 1 : 0;

    // Auto-determine availability based on dates - only shows for the current week
    $today = date('Y-m-d');
    $is_available = 0;

    // Get current week boundaries (Monday to Sunday)
    $currentWeekStart = date('Y-m-d', strtotime('monday this week'));
    $currentWeekEnd = date('Y-m-d', strtotime('sunday this week'));

    // Movie is available if and only if its showing period overlaps with the current week
    if (($show_start_date <= $currentWeekEnd) && ($show_end_date >= $currentWeekStart)) {
        $is_available = 1;
    }

    // Also update schedule availability with the same weekly logic
    $schedule_is_available = 0;
    if (($current_date >= $currentWeekStart) && ($current_date <= $currentWeekEnd)) {
        $schedule_is_available = 1;
    }

    // Check if any showtimes were selected
    if (empty($showtimes)) {
        throw new Exception('Please select at least one showtime.');
    }

    // Handle the movie poster upload
    $poster_path = '';
    if (isset($_FILES['movie-poster']) && $_FILES['movie-poster']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/posters/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Get file info
        $file_tmp = $_FILES['movie-poster']['tmp_name'];
        $file_name = $_FILES['movie-poster']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Allow only specific image extensions
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed.');
        }

        // Generate unique filename
        $new_file_name = uniqid() . '_' . preg_replace('/[^A-Za-z0-9]/', '', $title) . '.' . $file_ext;
        $destination = $upload_dir . $new_file_name;

        // Move uploaded file
        if (move_uploaded_file($file_tmp, $destination)) {
            $poster_path = 'uploads/posters/' . $new_file_name; // Store relative path in database
        } else {
            throw new Exception('Failed to upload poster image.');
        }
    } else {
        throw new Exception('Movie poster is required.');
    }

    // Start transaction to ensure data integrity
    mysqli_begin_transaction($conn);

    // Insert into movies table
    $movie_query = "INSERT INTO movies (title, synopsis, genre, duration, release_date, poster, rating, trailer_url, is_featured, is_available, created_at) 
                   VALUES ('$title', '$synopsis', '$genre', $duration, '$release_date', '$poster_path', '$rating', '$trailer_url', $is_featured, $is_available, NOW())";

    if (!mysqli_query($conn, $movie_query)) {
        throw new Exception('Error adding movie: ' . mysqli_error($conn));
    }

    // Get the inserted movie ID
    $movie_id = mysqli_insert_id($conn);

    // Update current_movie_id in cinemas table if needed
    if ($is_available && $is_featured) {
        $update_cinema_query = "UPDATE cinemas SET current_movie_id = $movie_id WHERE id = $cinema_id";
        mysqli_query($conn, $update_cinema_query);
    }

    // Insert schedules for each day between start and end date
    $start = new DateTime($show_start_date);
    $end = new DateTime($show_end_date);
    $interval = new DateInterval('P1D'); // 1 day interval
    $date_range = new DatePeriod($start, $interval, $end->modify('+1 day')); // Include end date

    foreach ($date_range as $date) {
        $current_date = $date->format('Y-m-d');

        // Determine availability for this specific date
        $schedule_is_available = ($current_date >= $today && $current_date <= $show_end_date) ? 1 : 0;

        // For each showtime, insert a schedule
        foreach ($showtimes as $start_time) {
            // Calculate end time based on movie duration
            $start_time_obj = new DateTime($start_time);
            $end_time_obj = clone $start_time_obj;
            $end_time_obj->add(new DateInterval('PT' . $duration . 'M')); // Add duration in minutes

            $start_time_display = $start_time_obj->format('H:i');
            $end_time_display = $end_time_obj->format('H:i');

            // Insert schedule with the date-specific availability
            $schedule_query = "INSERT INTO schedules (cinema_id, movie_id, date, show_date, end_date, start_time, end_time, is_available, created_at) 
                              VALUES ($cinema_id, $movie_id, '$current_date', '$show_start_date', '$show_end_date', '$start_time_display', '$end_time_display', $schedule_is_available, NOW())";

            if (!mysqli_query($conn, $schedule_query)) {
                throw new Exception('Error adding schedule: ' . mysqli_error($conn));
            }
        }
    }

    // Commit the transaction
    mysqli_commit($conn);

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Movie and schedules added successfully!',
        'movie_id' => $movie_id
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);

    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
mysqli_close($conn);
