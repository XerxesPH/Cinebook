<!-- My Reservations Modal -->
<div class="modal" id="reservations-modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2 class="modal-title">My Reservations</h2>
        
        <div id="reservations-container">
            <!-- Loading indicator -->
            <div class="loading-indicator" id="reservations-loading">
                <div class="spinner"></div>
                <p>Loading your reservations...</p>
            </div>
            
            <!-- Reservation content container -->
            <div id="reservations-content" class="hidden">
                <!-- Reservations will be displayed here via AJAX -->
            </div>
            
            <!-- No reservations message -->
            <div class="alert-message info hidden" id="no-reservations-message">
                You don't have any reservations yet. <a href="movies.php" class="alert-link">Browse movies</a> to make a reservation.
            </div>
            
            <!-- Error message -->
            <div class="alert-message error hidden" id="reservations-error">
                An error occurred while loading your reservations. Please try again later.
            </div>
        </div>
        
        <div class="modal-actions">
            <a href="movies.php" class="primary-btn">Browse Movies</a>
        </div>
    </div>
</div>

<!-- Reservation Details Modal -->
<div class="modal" id="reservation-details-modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2 class="modal-title">Reservation Details</h2>
        
        <div id="reservation-details-content">
            <!-- Reservation details will be loaded here -->
        </div>
    </div>
</div>

<script>
// Add a small script to ensure loading indicator works correctly
document.addEventListener('DOMContentLoaded', function() {
    // A single one-time check after page load is complete
    setTimeout(function() {
        const loading = document.getElementById('reservations-loading');
        const content = document.getElementById('reservations-content');
        
        if (loading && content && !content.classList.contains('hidden')) {
            loading.classList.add('hidden');
        }
    }, 1000);
});
</script>

<link rel="stylesheet" href="css/reservation_modal.css">
<script src="js/reservation_modal.js"></script>