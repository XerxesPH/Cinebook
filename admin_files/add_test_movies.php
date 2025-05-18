<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
require_once __DIR__ . '/../includes/db.php';

// Test movies data
$test_movies = [
    [
        'title' => 'The Godfather',
        'synopsis' => 'The aging patriarch of an organized crime dynasty transfers control of his empire to his reluctant son.',
        'genre' => 'Crime',
        'duration' => 175,
        'release_date' => '1972-03-24',
        'poster_source' => 'images/The Godfather (1972)',
        'trailer_url' => 'https://www.youtube.com/watch?v=sY1S34973zA',
        'rating' => 'R'
    ],
    [
        'title' => 'The Dark Knight',
        'synopsis' => 'Batman faces the Joker, a criminal mastermind who plunges Gotham into chaos.',
        'genre' => 'Action',
        'duration' => 152,
        'release_date' => '2008-07-18',
        'poster_source' => 'images/The Dark Knight (2008)',
        'trailer_url' => 'https://www.youtube.com/watch?v=EXeTwQWrcwY',
        'rating' => 'PG-13'
    ],
    [
        'title' => 'Forrest Gump',
        'synopsis' => 'The story of a simple man with a low IQ who inadvertently influences major historical events.',
        'genre' => 'Drama',
        'duration' => 142,
        'release_date' => '1994-07-06',
        'poster_source' => 'images/Forrest Gump (1994)',
        'trailer_url' => 'https://www.youtube.com/watch?v=bLvqoHBptjg',
        'rating' => 'PG-13'
    ],
    [
        'title' => 'Inception',
        'synopsis' => 'A skilled thief is offered a chance to have his past crimes forgiven if he implants another person\'s idea into their subconscious.',
        'genre' => 'Sci-Fi',
        'duration' => 148,
        'release_date' => '2010-07-16',
        'poster_source' => 'images/Inception (2010)',
        'trailer_url' => 'https://www.youtube.com/watch?v=8hP9D6kZseM',
        'rating' => 'PG-13'
    ],
    [
        'title' => 'The Matrix',
        'synopsis' => 'A computer hacker discovers the world he lives in is a simulation and joins a rebellion to fight the machines.',
        'genre' => 'Sci-Fi',
        'duration' => 136,
        'release_date' => '1999-03-31',
        'poster_source' => 'images/The Matrix',
        'trailer_url' => 'https://www.youtube.com/watch?v=vKQi3bBA1y8',
        'rating' => 'R'
    ],
    [
        'title' => 'Interstellar',
        'synopsis' => 'A team of explorers travels through a wormhole in space in an attempt to ensure humanity\'s survival.',
        'genre' => 'Adventure',
        'duration' => 169,
        'release_date' => '2014-11-07',
        'poster_source' => 'images/Interstellar',
        'trailer_url' => 'https://www.youtube.com/watch?v=zSWdZVtXT7E',
        'rating' => 'PG-13'
    ],
    [
        'title' => 'Se7en',
        'synopsis' => 'Two detectives hunt a serial killer who uses the seven deadly sins as his motives.',
        'genre' => 'Thriller',
        'duration' => 127,
        'release_date' => '1995-09-22',
        'poster_source' => 'images/Se7en (1995)',
        'trailer_url' => 'https://www.youtube.com/watch?v=znmZoVkCjpI',
        'rating' => 'R'
    ],
    [
        'title' => 'City of God',
        'synopsis' => 'Two boys growing up in a violent neighborhood of Rio de Janeiro take different paths: one becomes a photographer, the other a drug dealer.',
        'genre' => 'Crime',
        'duration' => 130,
        'release_date' => '2002-08-31',
        'poster_source' => 'images/City of God (2002)',
        'trailer_url' => 'https://www.youtube.com/watch?v=dcUOO4Itgmw',
        'rating' => 'R'
    ]
];

try {
    // Get all cinema IDs
    $cinema_query = "SELECT id FROM cinemas WHERE id > 0 ORDER BY id";
    $cinema_result = mysqli_query($conn, $cinema_query);
    
    if (mysqli_num_rows($cinema_result) == 0) {
        throw new Exception("No cinemas found in the database.");
    }
    
    $cinema_ids = [];
    while ($row = mysqli_fetch_assoc($cinema_result)) {
        $cinema_ids[] = $row['id'];
    }
    
    // Group cinemas as specified
    $cinema_groups = [
        [1, 6], // Cinema 1 and 6 share movies
        [2, 4], // Cinema 2 and 4 share movies
        [3, 5]  // Cinema 3 and 5 share movies
    ];
    
    // Default showtimes
    $showtimes = ['10:00:00', '13:00:00', '16:00:00', '19:00:00', '22:00:00'];
    
    // Set date ranges
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $one_week = date('Y-m-d', strtotime('+7 days'));
    $two_weeks = date('Y-m-d', strtotime('+14 days'));
    
    // Count of successfully added movies
    $added_count = 0;
    
    // Assign movies to cinema groups (ensuring different distribution)
    // We'll use 8 movies across 3 cinema groups, roughly 2-3 movies per group
    $movies_per_group = [
        0 => [0, 3, 6],         // Group 1 (Cinema 1,6) - Godfather, Inception, Se7en 
        1 => [1, 4, 7],         // Group 2 (Cinema 2,4) - Dark Knight, Matrix, City of God
        2 => [2, 5]             // Group 3 (Cinema 3,5) - Forrest Gump, Interstellar
    ];
    
    // Process each cinema group
    foreach ($movies_per_group as $group_idx => $movie_indices) {
        $cinemas_in_group = $cinema_groups[$group_idx];
        
        // Process each movie for this cinema group
        foreach ($movie_indices as $movie_idx) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            $movie = $test_movies[$movie_idx];
            
            // Prepare movie data
            $title = mysqli_real_escape_string($conn, $movie['title']);
            $synopsis = mysqli_real_escape_string($conn, $movie['synopsis']);
            $genre = mysqli_real_escape_string($conn, $movie['genre']);
            $duration = (int)$movie['duration'];
            $release_date = $movie['release_date'];
            $rating = mysqli_real_escape_string($conn, $movie['rating']);
            $trailer_url = mysqli_real_escape_string($conn, $movie['trailer_url']);
            
            // Random values for testing
            $is_featured = rand(0, 1);
            $is_available = 1; // Always available for testing
            
            // Determine if this movie is "coming soon" or "showing now"
            // We'll make approximately half the movies coming soon
            $is_coming_soon = ($movie_idx % 2 == 0);
            
            if ($is_coming_soon) {
                // Coming soon movies start 1-2 weeks from now
                $start_days = rand(7, 14);
                $show_start_date = date('Y-m-d', strtotime("+$start_days days"));
                $show_end_date = date('Y-m-d', strtotime("+".($start_days + 14)." days"));
            } else {
                // Current movies
                $show_start_date = $today;
                $show_end_date = date('Y-m-d', strtotime('+14 days'));
            }
            
            // Handle poster - copy from source to uploads
            $upload_dir = '../uploads/posters/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Default placeholder image if source not found
            $default_poster = '../images/placeholder-poster.jpg';
            
            // Full path to images directory
            $images_dir = 'C:/xampp/htdocs/Cinema_Reservation/images/';
            
            // Check for existing poster with the exact filename (with various extensions)
            $possible_poster_paths = [
                $images_dir . $movie['title'] . '.jpeg',
                $images_dir . $movie['title'] . '.jpg',
                $images_dir . $movie['title'] . '.png',
                $images_dir . $movie['poster_source'] . '.jpeg',
                $images_dir . $movie['poster_source'] . '.jpg',
                $images_dir . $movie['poster_source'] . '.png'
            ];
            
            // Online poster URLs as fallback (leaving these for backup)
            $poster_urls = [
                'The Godfather' => 'https://m.media-amazon.com/images/M/MV5BM2MyNjYxNmUtYTAwNi00MTYxLWJmNWYtYzZlODY3ZTk3OTFlXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_.jpg',
                'The Dark Knight' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_.jpg',
                'Forrest Gump' => 'https://m.media-amazon.com/images/M/MV5BNWIwODRlZTUtY2U3ZS00Yzg1LWJhNzYtMmZiYmEyNmU1NjMzXkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_.jpg',
                'Inception' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_.jpg',
                'The Matrix' => 'https://m.media-amazon.com/images/M/MV5BNzQzOTk3OTAtNDQ0Zi00ZTVkLWI0MTEtMDllZjNkYzNjNTc4L2ltYWdlXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_.jpg',
                'Interstellar' => 'https://m.media-amazon.com/images/M/MV5BZjdkOTU3MDktN2IxOS00OGEyLWFmMjktY2FiMmZkNWIyODZiXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_.jpg',
                'Se7en' => 'https://m.media-amazon.com/images/M/MV5BOTUwODM5MTctZjczMi00OTk4LTg3NWUtNmVhMTAzNTNjYjcyXkEyXkFqcGdeQXVyNjU0OTQ0OTY@._V1_.jpg',
                'City of God' => 'https://m.media-amazon.com/images/M/MV5BOTMwYjc5ZmItYTFjZC00ZGQ3LTlkNTMtMjZiNTZlMWQzNzI5XkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_.jpg'
            ];
            
            // Generate unique filename
            $new_file_name = uniqid() . '_' . preg_replace('/[^A-Za-z0-9]/', '', $title) . '.jpg';
            $destination = $upload_dir . $new_file_name;
            
            // Set flag for poster found status
            $found_poster = false;
            
            // First try the exact file names from images directory (confirmed they exist)
            $exact_poster_path = $images_dir . $movie['title'] . '.jpeg';
            if (file_exists($exact_poster_path)) {
                copy($exact_poster_path, $destination);
                $found_poster = true;
                error_log("Found poster at: " . $exact_poster_path);
            } 
            // If exact match not found, try the other possibilities
            else {
                foreach ($possible_poster_paths as $path) {
                    if (file_exists($path)) {
                        copy($path, $destination);
                        $found_poster = true;
                        error_log("Found poster at: " . $path);
                        break;
                    }
                }
            }
            
            // If not found locally, try to download from URL
            if (!$found_poster) {
                if (isset($poster_urls[$title]) && function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
                    $poster_url = $poster_urls[$title];
                    $poster_data = @file_get_contents($poster_url);
                    
                    if ($poster_data) {
                        file_put_contents($destination, $poster_data);
                        $found_poster = true;
                        error_log("Downloaded poster from URL for: " . $title);
                    }
                }
            }
            
            // Use default placeholder if all else fails
            if (!$found_poster) {
                copy($default_poster, $destination);
                error_log("Using placeholder for: " . $title);
            }
            
            $poster_path = 'uploads/posters/' . $new_file_name;
            
            // Insert movie into database
            $movie_query = "INSERT INTO movies (title, synopsis, genre, duration, release_date, poster, rating, trailer_url, is_featured, is_available, created_at) 
                        VALUES ('$title', '$synopsis', '$genre', $duration, '$release_date', '$poster_path', '$rating', '$trailer_url', $is_featured, $is_available, NOW())";
            
            if (!mysqli_query($conn, $movie_query)) {
                throw new Exception('Error adding movie: ' . mysqli_error($conn));
            }
            
            // Get the inserted movie ID
            $movie_id = mysqli_insert_id($conn);
            
            // Create schedules for each cinema in the group
            foreach ($cinemas_in_group as $cinema_index => $cinema_id) {
                // If movie is featured, make it the current movie for this cinema
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
                    
                    // Always available for testing
                    $schedule_is_available = 1;
                    
                    // Randomize the number of showings per day (between 2-4)
                    $num_times = rand(2, 4);
                    
                    // Use different starting indices for showtimes based on cinema index
                    // This ensures the same movie doesn't show at the same time in paired cinemas
                    $start_idx = ($cinema_index * 2) % 5; // Shift by 2 for each cinema in a pair
                    
                    // Get available showtimes by rotating the array based on cinema
                    $rotated_showtimes = array_merge(
                        array_slice($showtimes, $start_idx),
                        array_slice($showtimes, 0, $start_idx)
                    );
                    
                    // Use 2-3 showtimes to ensure different timing
                    $selected_showtimes = array_slice($rotated_showtimes, 0, $num_times);
                    
                    // For each selected showtime, insert a schedule
                    foreach ($selected_showtimes as $start_time) {
                        // Calculate end time based on movie duration
                        $start_time_obj = new DateTime($start_time);
                        $end_time_obj = clone $start_time_obj;
                        $end_time_obj->add(new DateInterval('PT' . $duration . 'M')); // Add duration in minutes
                        
                        $start_time_display = $start_time_obj->format('H:i');
                        $end_time_display = $end_time_obj->format('H:i');
                        
                        // Insert schedule
                        $schedule_query = "INSERT INTO schedules (cinema_id, movie_id, date, show_date, end_date, start_time, end_time, is_available, created_at) 
                                          VALUES ($cinema_id, $movie_id, '$current_date', '$show_start_date', '$show_end_date', '$start_time_display', '$end_time_display', $schedule_is_available, NOW())";
                        
                        if (!mysqli_query($conn, $schedule_query)) {
                            throw new Exception('Error adding schedule: ' . mysqli_error($conn));
                        }
                    }
                }
            }
            
            // Commit the transaction
            mysqli_commit($conn);
            $added_count++;
        }
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => $added_count . ' test movies added successfully, distributed across paired cinemas with varying showtimes!'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (mysqli_connect_errno() === 0) {
        mysqli_rollback($conn);
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
mysqli_close($conn); 