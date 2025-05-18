// Payment handling functionality

// Process payment with selected card
function processPaymentWithCard(cardId) {
    const cardSelectionModal = document.getElementById('cardSelectionModal');
    
    if (!cardSelectionModal) {
        console.error('Card selection modal not found');
        return;
    }
    
    const cardSelectionModalContent = cardSelectionModal.querySelector('.modal-content');
    if (!cardSelectionModalContent) {
        console.error('Card selection modal content not found');
        return;
    }
    
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.innerHTML = '<p>Processing payment...</p>';
    
    cardSelectionModalContent.appendChild(loadingIndicator);
    
    cardSelectionModal.querySelectorAll('button').forEach(btn => {
        btn.disabled = true;
    });
    
    // Calculate total amount
    const totalPriceDisplay = document.getElementById('total-price');
    const totalAmount = parseFloat(totalPriceDisplay ? totalPriceDisplay.textContent : 0);
    
    // Get movie and cinema IDs
    const movieId = document.getElementById('movie-id')?.value;
    const cinemaId = document.getElementById('cinema-id')?.value;
    
    // Use global variable for screeningId, passed from movieInfoModal
    const screeningId = window.selectedScreeningId;
    
    // Gather all reservation details - using data received from movieInfoModal
    const reservationDetails = {
        movie_id: movieId,
        cinema_id: cinemaId,
        schedule_id: screeningId,
        payment_method_id: cardId,
        seats: window.selectedSeats,
        total_amount: totalAmount,
        show_date: window.selectedDate,
        start_time: window.selectedTime
    };
    
    console.log('Processing reservation with details:', reservationDetails);
    
    // Send the reservation to the server
    fetch('./api/process_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(reservationDetails)
    })
    .then(response => {
        // Check if response is ok before trying to parse as JSON
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Server responded with error:', text);
                throw new Error('Server error: ' + response.status);
            });
        }
        return response.text().then(text => {
            // Attempt to parse as JSON, fall back gracefully if it's not valid JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                console.log('Raw server response:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        loadingIndicator.remove();
        cardSelectionModal.style.display = 'none';
        
        if (data && data.success) {
            // Mark the reservation as complete to prevent duplicate submission
            window.reservationComplete = true;
            
            // Force a refresh of the seat display for all users
            if (typeof window.refreshSeatsNow === 'function') {
                console.log('Refreshing seats after successful reservation');
                window.refreshSeatsNow();
            }
            
            // Show confirmation with the reservation code
            if (typeof window.showConfirmationModal === 'function') {
                window.showConfirmationModal(data.reservation_code);
            } else {
                alert('Reservation successful! Your reservation code is: ' + data.reservation_code);
            }
        } else {
            alert('Reservation failed: ' + (data?.message || 'Unknown error'));
        }
    })
    .catch(error => {
        loadingIndicator.remove();
        cardSelectionModal.style.display = 'none';
        console.error('Error saving reservation:', error);
        alert('An error occurred while processing your reservation. Please try again.');
    });
}

// Handle payment form submission
function initPaymentForm() {
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values (add validation as needed)
            const cardName = document.getElementById('card-name')?.value || '';
            const cardNumber = document.getElementById('card-number')?.value || '';
            const expiryDate = document.getElementById('expiry-date')?.value || '';
            const cvv = document.getElementById('cvv')?.value || '';
            
            // Validate payment details
            if (!validatePaymentDetails(cardName, cardNumber, expiryDate, cvv)) {
                return;
            }
            
            // Process the payment
            processNewCardPayment();
        });
    }
}

// Process payment with new card
function processNewCardPayment() {
    const paymentForm = document.getElementById('payment-form');
    const paymentModal = document.getElementById('paymentModal');
    
    if (!paymentForm) {
        console.error('Payment form not found');
        return;
    }
    
    const submitButton = paymentForm.querySelector('button[type="submit"]');
    if (!submitButton) {
        console.error('Submit button not found in payment form');
        return;
    }
    
    const originalText = submitButton.textContent;
    
    submitButton.disabled = true;
    submitButton.textContent = 'Processing...';
    
    // Get form values
    const cardName = document.getElementById('card-name')?.value || '';
    const cardNumber = document.getElementById('card-number')?.value || '';
    const expiryDate = document.getElementById('expiry-date')?.value || '';
    const cvv = document.getElementById('cvv')?.value || '';
    
    // Create a temporary payment method ID
    const temporaryPaymentMethodId = 'new_card_' + Date.now();
    
    // Get movie and cinema IDs
    const movieId = document.getElementById('movie-id')?.value;
    const cinemaId = document.getElementById('cinema-id')?.value;
    
    // Use global variable for screeningId, passed from movieInfoModal
    const screeningId = window.selectedScreeningId;
    
    // Calculate total amount
    const totalPriceDisplay = document.getElementById('total-price');
    const totalAmount = parseFloat(totalPriceDisplay ? totalPriceDisplay.textContent : 0);
    
    // Gather all reservation details - using data received from movieInfoModal
    const reservationDetails = {
        movie_id: movieId,
        cinema_id: cinemaId,
        schedule_id: screeningId,
        payment_method_id: temporaryPaymentMethodId,
        seats: window.selectedSeats,
        total_amount: totalAmount,
        show_date: window.selectedDate,
        start_time: window.selectedTime
    };
    
    console.log('Processing reservation with details:', reservationDetails);
    
    // Send the reservation to the server
    fetch('./api/process_reservation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(reservationDetails)
    })
    .then(response => response.json())
    .then(data => {
        paymentForm.reset();
        if (paymentModal) paymentModal.style.display = 'none';
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        
        if (data.success) {
            // Mark the reservation as complete to prevent duplicate submission
            window.reservationComplete = true;
            
            // Force a refresh of the seat display for all users
            if (typeof window.refreshSeatsNow === 'function') {
                console.log('Refreshing seats after successful reservation');
                window.refreshSeatsNow();
            }
            
            // Show confirmation with the reservation code
            if (typeof window.showConfirmationModal === 'function') {
                window.showConfirmationModal(data.reservation_code);
            } else {
                alert('Reservation successful! Your reservation code is: ' + data.reservation_code);
            }
        } else {
            alert('Reservation failed: ' + data.message);
        }
    })
    .catch(error => {
        paymentForm.reset();
        if (paymentModal) paymentModal.style.display = 'none';
        submitButton.disabled = false;
        submitButton.textContent = originalText;
        
        console.error('Error saving reservation:', error);
        alert('An error occurred while processing your reservation. Please try again.');
    });
}

// Validate payment details
function validatePaymentDetails(cardName, cardNumber, expiryDate, cvv) {
    // Basic validation
    if (!cardName || !cardNumber || !expiryDate || !cvv) {
        alert('Please fill in all payment details');
        return false;
    }
    
    // Card number validation (should be 16 digits)
    if (!/^\d{16}$/.test(cardNumber.replace(/\s/g, ''))) {
        alert('Please enter a valid 16-digit card number');
        return false;
    }
    
    // Expiry date validation (should be MM/YY format)
    if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
        alert('Please enter expiry date in MM/YY format');
        return false;
    }
    
    // CVV validation (should be 3 digits)
    if (!/^\d{3}$/.test(cvv)) {
        alert('Please enter a valid 3-digit CVV');
        return false;
    }
    
    return true;
}

// Initialize payment functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize payment form
    initPaymentForm();
});

// Expose functions to global scope
window.processPaymentWithCard = processPaymentWithCard;
window.initPaymentForm = initPaymentForm;
window.processNewCardPayment = processNewCardPayment;
window.validatePaymentDetails = validatePaymentDetails; 