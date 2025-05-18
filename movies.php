<?php
// Set page title based on category
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

switch ($category) {
    case 'now-showing':
        $pageTitle = "CineBook - Now Showing";
        $heading = "Now Showing";
        break;
    case 'coming-soon':
        $pageTitle = "CineBook - Coming Soon";
        $heading = "Coming Soon";
        break;
    default:
        $pageTitle = "CineBook - All Movies";
        $heading = "All Movies";
        $category = 'all';
}

// Include header (which handles authentication and includes the navbars and side menu)
include 'includes/header.php';

// Prepare the query based on the category
$today = date('Y-m-d');
$movieQuery = "SELECT m.id, m.title, m.synopsis, m.poster as image, m.genre, m.rating, 
               MIN(s.show_date) as start_showing, MAX(s.end_date) as end_showing 
               FROM movies m 
               LEFT JOIN schedules s ON m.id = s.movie_id";

// Add WHERE clause based on the category
switch ($category) {
    case 'now-showing':
        $movieQuery .= " WHERE '$today' BETWEEN s.show_date AND s.end_date";
        break;
    case 'coming-soon':
        $movieQuery .= " WHERE s.show_date > '$today'";
        break;
}

$movieQuery .= " GROUP BY m.id, m.title";
$movieResult = mysqli_query($conn, $movieQuery);

// Check for query errors
if (!$movieResult) {
    echo "<div class='error-message'>Error fetching movies: " . mysqli_error($conn) . "</div>";
}

$movies = [];
$uniqueMovies = []; // Array to track unique movies by title
$uniqueMovieIds = []; // Array to store the first ID of each unique movie

if ($movieResult && mysqli_num_rows($movieResult) > 0) {
    while ($row = mysqli_fetch_assoc($movieResult)) {
        // Check if this movie title already exists in our unique movies array
        if (!in_array($row['title'], $uniqueMovies)) {
            $uniqueMovies[] = $row['title'];
            $uniqueMovieIds[] = $row['id'];
            $movies[] = $row;
        }
    }
}
?>

<!-- Page Content -->
<div class="main-content">
    <section class="movies-section">
        <div class="section-header">
            <h2><?php echo $heading; ?></h2>
            <div class="category-filters">
                <a href="movies.php" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>">All Movies</a>
                <a href="movies.php?category=now-showing" class="filter-btn <?php echo $category === 'now-showing' ? 'active' : ''; ?>">Now Showing</a>
                <a href="movies.php?category=coming-soon" class="filter-btn <?php echo $category === 'coming-soon' ? 'active' : ''; ?>">Coming Soon</a>
            </div>
        </div>

        <?php if (empty($movies)): ?>
            <div class="no-movies">
                <p>No movies found in this category.</p>
            </div>
        <?php else: ?>
            <div class="movies-grid">
                <?php foreach ($movies as $movie): ?>
                    <div class="movie-card" data-movie-id="<?php echo $movie['id']; ?>" data-movie-title="<?php echo htmlspecialchars($movie['title']); ?>">
                        <div class="movie-poster-container">
                            <img src="<?php echo $movie['image']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                            <?php if (isset($movie['rating'])): ?>
                                <span class="movie-rating"><?php echo htmlspecialchars($movie['rating']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="movie-info">
                            <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="movie-genre"><?php echo isset($movie['genre']) ? htmlspecialchars($movie['genre']) : 'Genre N/A'; ?></p>
                            <div class="movie-buttons">
                                <button class="info-btn">More Info</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'modals/movie_info_modal.php'; ?>
<?php include 'modals/seat_selection_modal.php'; ?>

<script src="js/movieInfoModal.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in (should be defined in header.php)
    const userIsLoggedIn = typeof window.userLoggedIn !== 'undefined' ? window.userLoggedIn : false;
    console.log("User logged in status in movies.php:", userIsLoggedIn);
    
    // Add click handlers for movie cards
    const movieCards = document.querySelectorAll('.movie-card');
    movieCards.forEach(card => {
        card.addEventListener('click', function() {
            const movieId = this.getAttribute('data-movie-id');
            const movieTitle = this.getAttribute('data-movie-title');
            if (movieId) {
                window.showMovieInfo(movieId, movieTitle);
                document.getElementById('movieModal').style.display = 'block';
            }
        });
    });

    // Add separate click handlers for the info buttons
    const infoButtons = document.querySelectorAll('.info-btn');
    infoButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the card click
            const movieCard = this.closest('.movie-card');
            const movieId = movieCard.getAttribute('data-movie-id');
            const movieTitle = movieCard.getAttribute('data-movie-title');
            if (movieId) {
                window.showMovieInfo(movieId, movieTitle);
                document.getElementById('movieModal').style.display = 'block';
            }
        });
    });
});
</script>

<style>
.main-content {
    margin-top: 80px; /* Add top margin to prevent overlapping with navigation */
    position: relative;
    z-index: 1; /* Ensure content is above other elements */
}

.movies-section {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2; /* Higher z-index than main-content */
}

.section-header h2 {
    font-size: 2rem;
    color: var(--primary-color);
    margin: 0;
}

.category-filters {
    display: flex;
    gap: 10px;
    position: relative;
    z-index: 3; /* Even higher z-index to ensure visibility */
}

.filter-btn {
    padding: 8px 15px;
    background-color: var(--card-background);
    color: var(--text-color);
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.filter-btn:hover, .filter-btn.active {
    background-color: var(--primary-color);
    color: white;
}

.movies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
}

.movie-card {
    background-color: var(--card-background);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}

.movie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.movie-poster-container {
    position: relative;
    height: 350px;
    overflow: hidden;
}

.movie-poster {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.movie-card:hover .movie-poster {
    transform: scale(1.05);
}

.movie-rating {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: bold;
}

.movie-info {
    padding: 15px;
}

.movie-info h3 {
    margin: 0 0 10px 0;
    font-size: 1.2rem;
    color: var(--text-color);
}

.movie-genre {
    color: var(--secondary-text-color);
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.movie-buttons {
    display: flex;
}

.info-btn {
    padding: 8px 0;
    border: none;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
    flex: 1;
    background-color: var(--primary-color);
    color: white;
}

.info-btn:hover {
    background-color: var(--hover-color);
}

.no-movies {
    text-align: center;
    padding: 50px;
    background-color: var(--card-background);
    border-radius: 10px;
    margin-top: 20px;
}

.error-message {
    background-color: #ffdddd;
    color: #ff0000;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .main-content {
        margin-top: 70px; /* Slightly less margin on smaller screens */
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .movies-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .movie-poster-container {
        height: 280px;
    }
}

@media screen and (max-width: 480px) {
    .movies-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .movie-poster-container {
        height: 220px;
    }
    
    .movie-info h3 {
        font-size: 1rem;
    }
    
    .movie-buttons {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?> 