<!-- Movie Info Modal -->
<div class="modal" id="movieModal">
    <div class="modal-content" style="max-width: 1000px; width: 100%;">
        <span class="close-btn">&times;</span>
        <div class="movie-details">
            <h2 id="movieTitle">Movie Title</h2>
            <div class="movie-info-container">
                <div class="movie-poster-container">
                    <img id="moviePoster" src="images/default-movie.jpg" alt="Movie Poster">
                </div>
                <div class="movie-info-right">
                    <p id="movieSynopsis">Movie synopsis will appear here.</p>
                    
                    <!-- Trailer Container -->
                    <div id="trailerContainer" style="margin-top: 20px; max-width: 100%;"></div>
                    
                    <div class="movie-cinemas">
                        <h3>Available at:</h3>
                        <ul id="movieCinemas">
                            <li>Loading cinema information...</li>
                        </ul>
                    </div>
                    <div class="selected-cinema-info">
                        <h3>Selected Cinema: <span id="selectedCinemaName">None selected</span></h3>
                    </div>
                </div>
            </div>
            
            <div class="movie-dates">
                <h3>Show Dates & Times:</h3>
                <div class="date-slider" id="movieDates">
                    <div class="date-item">Loading show dates...</div>
                </div>
            </div>
            
            <div class="booking-section">
                <div class="booking-summary">
                    <div class="booking-detail">
                        <span class="detail-label">Cinema:</span>
                        <span id="bookingCinema">Not selected</span>
                    </div>
                    <div class="booking-detail">
                        <span class="detail-label">Date:</span>
                        <span id="bookingDate">Not selected</span>
                    </div>
                    <div class="booking-detail">
                        <span class="detail-label">Time:</span>
                        <span id="bookingTime">Not selected</span>
                    </div>
                </div>
                <button class="book-now-btn" id="bookNowBtn" disabled>Select a date & time to book</button>
            </div>
        </div>
    </div>
</div>