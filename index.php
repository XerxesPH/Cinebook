<?php
// Set page title
$pageTitle = "CineBook - Home";

// Include header (which handles authentication and includes the navbars and side menu)
include 'includes/header.php';

// Fetch movies from database - Only get currently showing movies
$today = date('Y-m-d');
$movieQuery = "SELECT m.id, m.title, m.synopsis, m.poster as image, m.genre 
               FROM movies m 
               JOIN schedules s ON m.id = s.movie_id 
               WHERE m.is_available = 1 
               AND '$today' BETWEEN s.show_date AND s.end_date
               GROUP BY m.id, m.title";
$movieResult = mysqli_query($conn, $movieQuery);

$movies = [];
$uniqueMovies = []; // Array to track unique movies by title
$uniqueMovieIds = []; // Array to store the first ID of each unique movie

if (mysqli_num_rows($movieResult) > 0) {
    while ($row = mysqli_fetch_assoc($movieResult)) {
        // Check if this movie title already exists in our unique movies array
        if (!in_array($row['title'], $uniqueMovies)) {
            $uniqueMovies[] = $row['title'];
            $uniqueMovieIds[] = $row['id'];
            $movies[] = $row;
        }
    }
} else {
    $movies = [
        ['id' => 1, 'title' => 'Sample Movie', 'image' => 'images/default-movie.jpg', 'synopsis' => 'No movies available at the moment.', 'genre' => 'N/A']
    ];
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
    </div>
    <div class="hero-carousel">
        <div class="carousel-container">
            <?php foreach ($movies as $movie): ?>
                <div class="carousel-item" style="background-image: url('<?php echo $movie['image']; ?>');">
                    <div class="overlay-blur"></div>
                    <div class="carousel-poster">
                        <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" />
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-control next"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>

<!-- Available Movies Section -->
<section class="available-movies">
    <h2>Now Showing</h2>
    <div class="movies-carousel">
        <div class="movies-container">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card" data-movie-id="<?php echo $movie['id']; ?>" data-movie-title="<?php echo htmlspecialchars($movie['title']); ?>">
                    <img src="<?php echo $movie['image']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                    <div class="movie-info">
                        <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                        <p class="movie-genre"><?php echo isset($movie['genre']) ? htmlspecialchars($movie['genre']) : 'Genre N/A'; ?></p>
                        <button class="info-btn">More Info</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control prev"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-control next"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>

<!-- About Us Section -->
<section class="about-us">
    <div class="about-content">
        <h2>About CineBook</h2>
        <p>CineBook offers a premium cinema experience with state-of-the-art facilities, comfortable seating, and the latest blockbuster movies. Our mission is to provide an immersive and enjoyable movie-watching experience for all our patrons.</p>
        <a href="about.php" class="about-btn">Learn More</a>
    </div>
</section>



<?php include 'modals/seat_selection_modal.php'; ?>
<?php include 'modals/movie_info_modal.php'; ?>

<script src="js/movieInfoModal.js"></script>
<?php

include 'includes/footer.php';
?>