<?php
// Make sure this file is only included, not accessed directly
if (!defined('INCLUDED')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
?>
<!-- Add Movie Modal -->
<div class="modal" id="addMovieModal">
    <div class="modal-content large-modal">
        <span class="close-modal">&times;</span>
        <h2>Add New Movie</h2>
        <form id="add-movie-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="movie-title">Movie Title</label>
                <input type="text" id="movie-title" name="movie-title" required>
            </div>
            <div class="form-group">
                <label for="movie-description">Synopsis</label>
                <textarea id="movie-description" name="movie-description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="movie-genre">Genre</label>
                <select id="movie-genre" name="movie-genre" required>
                    <option value="">Select Genre</option>
                    <option value="Action">Action</option>
                    <option value="Adventure">Adventure</option>
                    <option value="Comedy">Comedy</option>
                    <option value="Drama">Drama</option>
                    <option value="Horror">Horror</option>
                    <option value="Sci-Fi">Sci-Fi</option>
                    <option value="Fantasy">Fantasy</option>
                    <option value="Romance">Romance</option>
                    <option value="Thriller">Thriller</option>
                    <option value="Animation">Animation</option>
                </select>
            </div>
            <div class="form-group">
                <label for="movie-duration-hours">Duration</label>
                <div class="duration-inputs">
                    <div class="duration-input-group">
                        <input type="number" id="movie-duration-hours" name="movie-duration-hours" min="0" max="12" value="2" required>
                        <span class="duration-label">Hours</span>
                    </div>
                    <div class="duration-input-group">
                        <input type="number" id="movie-duration-minutes" name="movie-duration-minutes" min="0" max="59" value="0" required>
                        <span class="duration-label">Minutes</span>
                    </div>
                    <!-- Hidden field to store total minutes for backend processing -->
                    <input type="hidden" id="movie-duration" name="movie-duration" value="120">
                </div>
            </div>
            <div class="form-group">
                <label for="release-date">Release Date</label>
                <input type="date" id="release-date" name="release-date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="show-start-date">Show Start Date</label>
                    <input type="date" id="show-start-date" name="show-start-date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group half-width">
                    <label for="show-end-date">Show End Date</label>
                    <input type="date" id="show-end-date" name="show-end-date" value="<?php echo date('Y-m-d', strtotime('+6 days')); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="movie-rating">Rating</label>
                <select id="movie-rating" name="movie-rating" required>
                    <option value="G">G</option>
                    <option value="PG">PG</option>
                    <option value="PG-13" selected>PG-13</option>
                    <option value="R">R</option>
                    <option value="NC-17">NC-17</option>
                </select>
            </div>
            <div class="form-group">
                <label for="movie-trailer">Trailer URL (optional)</label>
                <input type="url" id="movie-trailer" name="movie-trailer" placeholder="https://www.youtube.com/watch?v=...">
            </div>
            <div class="form-group">
                <label for="movie-cinema">Cinema</label>
                <select id="movie-cinema" name="movie-cinema" required>
                    <option value="">Select Cinema</option>
                    <?php
                    // Query to get all cinemas from database
                    $cinema_query = "SELECT id, name, total_seats FROM cinemas WHERE id > 0 ORDER BY id";
                    $cinema_result = mysqli_query($conn, $cinema_query);

                    if (mysqli_num_rows($cinema_result) > 0) {
                        while ($row = mysqli_fetch_assoc($cinema_result)) {
                            echo '<option value="' . $row['id'] . '">' . $row['name'] . ' (' . $row['total_seats'] . ' seats)</option>';
                        }
                    } else {
                        echo '<option value="" disabled>No cinemas available</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Show Times</label>
                <div class="showtime-container">
                    <div class="showtime-checkboxes">
                        <div class="showtime-option">
                            <input type="checkbox" id="time-10am" name="showtimes[]" value="10:00:00">
                            <label for="time-10am">10:00 AM</label>
                        </div>
                        <div class="showtime-option">
                            <input type="checkbox" id="time-1pm" name="showtimes[]" value="13:00:00">
                            <label for="time-1pm">1:00 PM</label>
                        </div>
                        <div class="showtime-option">
                            <input type="checkbox" id="time-4pm" name="showtimes[]" value="16:00:00">
                            <label for="time-4pm">4:00 PM</label>
                        </div>
                        <div class="showtime-option">
                            <input type="checkbox" id="time-7pm" name="showtimes[]" value="19:00:00">
                            <label for="time-7pm">7:00 PM</label>
                        </div>
                        <div class="showtime-option">
                            <input type="checkbox" id="time-10pm" name="showtimes[]" value="22:00:00">
                            <label for="time-10pm">10:00 PM</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="movie-poster">Movie Poster</label>
                <div class="file-upload">
                    <div class="file-preview">
                        <img id="poster-preview" src="images/placeholder-poster.jpg" alt="Poster Preview" class="poster-image">
                    </div>
                    <input type="file" id="movie-poster" name="movie-poster" accept="image/*" required style="display:none;">
                    <button type="button" class="upload-btn">Choose File</button>
                    <span class="file-name">No file chosen</span>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-option">
                    <input type="checkbox" id="is-featured" name="is-featured" value="1">
                    <label for="is-featured">Featured Movie</label>
                </div>
            </div>
            <!-- Removed "Available for Booking" checkbox as it will be determined automatically -->
            <input type="hidden" id="is-available" name="is-available" value="1">
            <div class="form-actions">
                <button type="submit" class="save-btn">Add Movie</button>
                <button type="button" class="cancel-btn">Cancel</button>
                <button type="button" id="load-test-movies" class="test-btn">Load Test Movies</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Improved showtime UI */
    .showtime-container {
        border: 1px solid #444;
        border-radius: 5px;
        padding: 10px;
        margin-top: 5px;
        background-color: #2a2a2a;
    }

    .showtime-checkboxes {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .showtime-option {
        display: flex;
        align-items: center;
        background-color: #333;
        padding: 5px 10px;
        border-radius: 4px;
        border: 1px solid #555;
        transition: all 0.2s ease;
    }

    .showtime-option:hover {
        background-color: #444;
        border-color: #666;
    }

    .showtime-option input[type="checkbox"] {
        margin-right: 5px;
    }

    .showtime-option label {
        font-size: 0.9em;
        cursor: pointer;
        user-select: none;
    }

    .showtime-option input[type="checkbox"]:checked+label {
        font-weight: bold;
        color: #ff9800;
    }

    /* Style for duration inputs */
    .duration-inputs {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .duration-input-group {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .duration-input-group input {
        width: 70px;
        text-align: center;
    }

    .duration-label {
        font-size: 0.9em;
        color: #ccc;
    }

    .test-btn {
        background-color: #6c5ce7;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .test-btn:hover {
        background-color: #5649c9;
    }
</style>

<script>
$(document).ready(function() {
    // Load test movies button
    $('#load-test-movies').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('This will add 8 test movies to your database. Continue?')) {
            $.ajax({
                url: 'admin_files/add_test_movies.php',
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    // Show loading indicator
                    $('#load-test-movies').prop('disabled', true).text('Loading...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        // Reload the page to show the new movies
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error: Could not add test movies. Please try again.');
                    console.error(xhr.responseText);
                },
                complete: function() {
                    // Reset button
                    $('#load-test-movies').prop('disabled', false).text('Load Test Movies');
                }
            });
        }
    });
});
</script>