// Reservation confirmation functionality

// Show confirmation modal with booking details
function showConfirmationModal(reservationCode = null) {
    const confirmationModal = document.getElementById('confirmationModal');
    
    if (!confirmationModal) {
        console.error('Confirmation modal not found');
        return;
    }
    
    // Set a flag to indicate if the reservation has already been completed
    window.reservationComplete = !!reservationCode;
    
    const confElements = {
        movie: document.getElementById('conf-movie-name'),
        cinema: document.getElementById('conf-cinema-name'),
        showTime: document.getElementById('conf-show-time'),
        seats: document.getElementById('conf-seats'),
        total: document.getElementById('conf-total'),
        reservationCode: document.getElementById('conf-reservation-code')
    };
    
    // Get movie name and update it
    const movieName = document.getElementById('movie-name')?.textContent || 'Sample Movie';
    if (confElements.movie) {
        confElements.movie.textContent = movieName;
    }
    
    // Get cinema name and update it
    const cinemaName = document.getElementById('cinema-name')?.textContent || 'Sample Cinema';
    if (confElements.cinema) {
        confElements.cinema.textContent = cinemaName;
    }
    
    // Format and update show time
    if (confElements.showTime) {
        if (window.selectedDate && window.selectedTime) {
            const dateObj = new Date(window.selectedDate);
            const formattedDate = dateObj.toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
            });
            
            confElements.showTime.textContent = `${formattedDate} at ${window.selectedTime}`;
        } else {
            const showTimeEl = document.getElementById('show-time');
            confElements.showTime.textContent = showTimeEl ? showTimeEl.textContent : getCurrentTime();
        }
    }
    
    // Update seats
    if (confElements.seats) {
        confElements.seats.textContent = window.selectedSeats.join(', ');
    }
    
    // Update total amount
    if (confElements.total) {
        const seatPrice = 300; // This should ideally be fetched from a global config
        confElements.total.textContent = (window.selectedSeats.length * seatPrice).toFixed(2);
    }
    
    // Add reservation code if available
    if (confElements.reservationCode && reservationCode) {
        confElements.reservationCode.textContent = reservationCode;
        confElements.reservationCode.parentElement.style.display = 'block';
        
        // Update button text when reservation is already complete
        const doneButton = document.getElementById('doneButton');
        if (doneButton) {
            doneButton.textContent = 'Done';
        }
        
        // Trigger an event to notify the system that a reservation has been completed
        // This will be caught by listeners to refresh the seat statuses
        const reservationEvent = new CustomEvent('reservationComplete', {
            detail: {
                reservationCode: reservationCode,
                screeningId: window.selectedScreeningId,
                seats: window.selectedSeats
            }
        });
        window.dispatchEvent(reservationEvent);
        
        // Also trigger immediate seat refresh if the function exists
        if (typeof window.refreshSeatsNow === 'function') {
            window.refreshSeatsNow();
        }
    } else if (confElements.reservationCode) {
        confElements.reservationCode.parentElement.style.display = 'none';
    }
    
    confirmationModal.style.display = 'block';
}

// Helper function to format the current time
function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

// Expose function to global scope
window.showConfirmationModal = showConfirmationModal; 