<!-- Seat Selection Modal -->
<div class="modal" id="seatModal">
    <div class="modal-content large">
        <span class="close-btn">&times;</span>
        <h2>Select Your Seats</h2>
        <div class="cinema-info">
            <h3 id="cinema-name"></h3>
            <p id="movie-name"></p>
            <p id="show-time"></p>
        </div>
        <!-- Hidden inputs to store IDs -->
        <input type="hidden" id="cinema-id" value="">
        <input type="hidden" id="movie-id" value="">
        <input type="hidden" id="screening-id" value="">
        <div class="screen"></div>
        <div id="seats-grid" class="seats-grid">
            <!-- Seats will be generated via JavaScript -->
            <div class="initial-message">Loading available seats...</div>
        </div>
        <div class="seats-legend">
            <div class="legend-item">
                <div class="seat available"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="seat selected"></div>
                <span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="seat reserved"></div>
                <span>Reserved</span>
            </div>
        </div>
        <div class="selected-seats-info">
            <p>Selected Seats: <span id="selected-seats">None</span></p>
            <p>Total Price: PHP<span id="total-price">0</span></p>
        </div>
        <button class="proceed-btn" id="proceedToPaymentBtn" disabled>Proceed to Payment</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for the proceed button
    const proceedBtn = document.getElementById('proceedToPaymentBtn');
    
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function() {
            if (window.selectedSeats && window.selectedSeats.length > 0) {
                // Check if the checkForSavedCards function exists
                if (typeof window.checkForSavedCards === 'function') {
                    window.checkForSavedCards();
                } else {
                    console.error('checkForSavedCards function not found');
                    alert('Error processing payment. Please try again later.');
                }
            }
        });
    } else {
        console.error('Proceed to Payment button not found');
    }
    
    // Add event listener for closing the modal
    const closeBtn = document.querySelector('#seatModal .close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            // Stop seat refresh when closing the modal
            if (typeof window.stopSeatRefresh === 'function') {
                window.stopSeatRefresh();
            }
            document.getElementById('seatModal').style.display = 'none';
        });
    }
    
    // Also stop refreshing when clicking outside the modal
    const seatModal = document.getElementById('seatModal');
    if (seatModal) {
        seatModal.addEventListener('click', function(e) {
            if (e.target === this) {
                // Stop seat refresh when closing the modal
                if (typeof window.stopSeatRefresh === 'function') {
                    window.stopSeatRefresh();
                }
                this.style.display = 'none';
            }
        });
    }
});
</script>