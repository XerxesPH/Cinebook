<?php

require_once __DIR__ . '/../includes/db.php';

// Set headers to allow AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check if movie ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Movie ID is required']);
    exit;
}

$movieId = (int)$_GET['id'];
$movieTitle = isset($_GET['title']) ? mysqli_real_escape_string($conn, $_GET['title']) : null;

// Log the request parameters
$logMessage = "Request for movie ID: $movieId";
if ($movieTitle) {
    $logMessage .= ", Title: $movieTitle";
}
error_log($logMessage);

// Debug: Log the connection status
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch movie details - no login check needed
$movieQuery = "SELECT id, title, synopsis, poster as image, release_date, duration, genre, trailer_url 
               FROM movies 
               WHERE id = $movieId";
$movieResult = mysqli_query($conn, $movieQuery);

// Debug: Log the query and any MySQL errors
if (!$movieResult) {
    error_log("MySQL Error: " . mysqli_error($conn));
    error_log("Query: $movieQuery");
}

if ($movieResult && mysqli_num_rows($movieResult) > 0) {
    $movie = mysqli_fetch_assoc($movieResult);

    // Make sure the image path is absolute
    if ($movie['image'] && !filter_var($movie['image'], FILTER_VALIDATE_URL) && strpos($movie['image'], '/') !== 0) {
        $movie['image'] = '/' . $movie['image'];
    }

    // Determine whether to search by ID or title for cinema matches
    $cinemaQuery = "SELECT DISTINCT c.id, c.name 
                   FROM cinemas c 
                   JOIN schedules s ON c.id = s.cinema_id
                   JOIN movies m ON s.movie_id = m.id
                   WHERE m.id = $movieId OR m.title = '$movieTitle'";
    
    $cinemaResult = mysqli_query($conn, $cinemaQuery);

    // Debug: Log cinema query
    if (!$cinemaResult) {
        error_log("MySQL Error (cinemas): " . mysqli_error($conn));
        error_log("Query: $cinemaQuery");
    }

    $movie['cinemas'] = [];
    if ($cinemaResult && mysqli_num_rows($cinemaResult) > 0) {
        while ($row = mysqli_fetch_assoc($cinemaResult)) {
            $movie['cinemas'][] = $row;
        }
    }

    // Fetch show dates and times for this movie, including all cinemas
    $datesQuery = "SELECT s.cinema_id, s.date, 
                    GROUP_CONCAT(s.id ORDER BY s.start_time SEPARATOR '#') as schedule_ids,
                    GROUP_CONCAT(TIME_FORMAT(s.start_time, '%I:%i %p') ORDER BY s.start_time SEPARATOR '#') as times
                    FROM schedules s
                    JOIN movies m ON s.movie_id = m.id
                    WHERE (m.id = $movieId OR m.title = '$movieTitle')
                    AND s.date >= CURDATE()
                    GROUP BY s.cinema_id, s.date
                    ORDER BY s.date";

    $datesResult = mysqli_query($conn, $datesQuery);

    // Debug: Log dates query
    if (!$datesResult) {
        error_log("MySQL Error (dates): " . mysqli_error($conn));
        error_log("Query: $datesQuery");
    }

    $movie['show_dates'] = [];
    if ($datesResult && mysqli_num_rows($datesResult) > 0) {
        while ($row = mysqli_fetch_assoc($datesResult)) {
            $times = explode('#', $row['times']);
            $scheduleIds = explode('#', $row['schedule_ids']);
            
            // Create array of times with their corresponding screening IDs
            $timesWithIds = [];
            for ($i = 0; $i < count($times); $i++) {
                $timesWithIds[] = [
                    'time' => $times[$i],
                    'schedule_id' => isset($scheduleIds[$i]) ? $scheduleIds[$i] : null
                ];
            }
            
            $movie['show_dates'][] = [
                'cinema_id' => $row['cinema_id'],
                'date' => $row['date'],
                'times_with_ids' => $timesWithIds
            ];
        }
    }

    // Log the successful response
    error_log("Successfully retrieved data for movie ID: $movieId");
    
    // Return JSON response
    echo json_encode($movie);
} else {
    // Return error if movie not found
    error_log("Movie not found for ID: $movieId");
    http_response_code(404);
    echo json_encode(['error' => 'Movie not found']);
}
?>