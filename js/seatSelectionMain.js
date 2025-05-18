// Main file to initialize the seat selection system and connect all components

// Dependencies:
// - seatSelection.js
// - dateTimeSelection.js
// - cardSelection.js
// - paymentHandler.js
// - reservationConfirmation.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing seat selection system...');

    // Expose global variables and functions to make them accessible across files
    window.seatSelectionSystem = {
        // Core seat selection
        displaySeatSelection: window.displaySeatSelection || function() { console.error('displaySeatSelection not found'); },
        resetSelectedSeats: window.resetSelectedSeats || function() { console.error('resetSelectedSeats not found'); },
        toggleSeatSelection: window.toggleSeatSelection || function() { console.error('toggleSeatSelection not found'); },
        resetBookingState: window.resetBookingState || function() { console.error('resetBookingState not found'); },
        refreshSeatsNow: window.refreshSeatsNow || function() { console.error('refreshSeatsNow not found'); },
        
        // Date and time from movie info modal
        selectedDate: window.selectedDate,
        selectedTime: window.selectedTime,
        selectedScreeningId: window.selectedScreeningId,
        
        // Card selection
        checkForSavedCards: window.checkForSavedCards || function() { console.error('checkForSavedCards not found'); },
        
        // Payment processing
        processPaymentWithCard: window.processPaymentWithCard || function() { console.error('processPaymentWithCard not found'); },
        
        // Confirmation
        showConfirmationModal: window.showConfirmationModal || function() { console.error('showConfirmationModal not found'); }
    };
    
    // Add event listeners for the "Use This Card" buttons in the card selection modal
    const cardSelectionModal = document.getElementById('cardSelectionModal');
    if (cardSelectionModal) {
        cardSelectionModal.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('use-card-btn')) {
                const cardId = e.target.getAttribute('data-card-id');
                if (cardId && window.processPaymentWithCard) {
                    window.processPaymentWithCard(cardId);
                }
            }
        });
    }
    
    // Monitor for successful reservations
    window.addEventListener('reservationComplete', function(event) {
        console.log('Reservation completed, refreshing seats for all users');
        // Refresh seats for everyone
        if (window.refreshSeatsNow) {
            window.refreshSeatsNow();
        }
    });
    
    // Add event listener for "Close" buttons on all modals
    document.querySelectorAll('.modal .close-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
                
                // If closing the seat modal, stop the refresh interval
                if (modal.id === 'seatModal' && window.stopSeatRefresh) {
                    window.stopSeatRefresh();
                }
            }
        });
    });
    
    // Add event listener for seat modal opening
    const seatModal = document.getElementById('seatModal');
    if (seatModal) {
        // Use MutationObserver to detect when modal becomes visible
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'style' && 
                    seatModal.style.display === 'block' && 
                    window.refreshSeatsNow) {
                    // Modal is now visible, refresh seats immediately
                    window.refreshSeatsNow();
                }
            });
        });
        
        observer.observe(seatModal, { attributes: true });
    }
    
    // Close modal when clicking outside of it (on the overlay)
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                
                // If closing the seat modal, stop the refresh interval
                if (this.id === 'seatModal' && window.stopSeatRefresh) {
                    window.stopSeatRefresh();
                }
            }
        });
    });
    
    console.log('Seat selection system initialized.');
}); 