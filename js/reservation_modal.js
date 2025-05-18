document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables for reservation modal
    const reservationsModal = document.getElementById('reservations-modal');
    const closeReservationsBtn = document.querySelector('#reservations-modal .close-btn');
    
    // Expose loadReservations function to be called from navigation.js
    window.loadReservations = loadReservations;
    
    // Close button for reservations modal
    if (closeReservationsBtn) {
        closeReservationsBtn.addEventListener('click', function() {
            reservationsModal.style.display = 'none';
        });
    }
    
    // Click event for reservation detail buttons
    document.body.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('view-details-btn')) {
            const reservationId = e.target.getAttribute('data-reservation-id');
            loadReservationDetails(reservationId);
        }
        
        // Handle cancel reservation button click
        if (e.target && e.target.classList.contains('cancel-reservation-btn')) {
            const reservationId = e.target.getAttribute('data-reservation-id');
            if (confirm('Are you sure you want to cancel this reservation?')) {
                cancelReservation(reservationId);
            }
        }
        
        // Handle delete reservation button click
        if (e.target && e.target.classList.contains('delete-reservation-btn')) {
            const reservationId = e.target.getAttribute('data-reservation-id');
            if (confirm('Are you sure you want to permanently delete this reservation? This action cannot be undone.')) {
                deleteReservation(reservationId);
            }
        }
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === reservationsModal) {
            reservationsModal.style.display = 'none';
        }
        
        const detailsModal = document.getElementById('reservation-details-modal');
        if (event.target === detailsModal) {
            detailsModal.style.display = 'none';
        }
    });
    
    // Close button for reservation details modal
    const closeDetailsBtn = document.querySelector('#reservation-details-modal .close-btn');
    if (closeDetailsBtn) {
        closeDetailsBtn.addEventListener('click', function() {
            document.getElementById('reservation-details-modal').style.display = 'none';
        });
    }
    
    function loadReservations() {
        const container = document.getElementById('reservations-content');
        const loading = document.getElementById('reservations-loading');
        const noReservations = document.getElementById('no-reservations-message');
        const errorMessage = document.getElementById('reservations-error');
        
        // Show loading, hide other elements
        if (loading) {
            loading.classList.remove('hidden');
        }
        if (container) container.classList.add('hidden');
        if (noReservations) noReservations.classList.add('hidden');
        if (errorMessage) errorMessage.classList.add('hidden');
        
        // AJAX request to get reservations
        fetch('./api/get_reservations.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                // First get the raw text response
                return response.text().then(text => {
                    try {
                        // Try to parse the response as JSON
                        return JSON.parse(text);
                    } catch (err) {
                        console.error('Failed to parse JSON response:', text);
                        throw new Error('Failed to parse JSON response. The server may have returned invalid data.');
                    }
                });
            })
            .then(data => {
                // Make sure loading is hidden regardless of outcome
                if (loading) {
                    loading.classList.add('hidden');
                }
                
                if (data.error) {
                    if (errorMessage) {
                        errorMessage.classList.remove('hidden');
                        errorMessage.textContent = data.error;
                    }
                    return;
                }
                
                if (data.reservations && data.reservations.length > 0) {
                    if (container) {
                        container.classList.remove('hidden');
                        renderReservations(data.reservations, container);
                    }
                } else {
                    if (noReservations) noReservations.classList.remove('hidden');
                }
            })
            .catch(error => {
                if (loading) {
                    loading.classList.add('hidden');
                }
                if (errorMessage) {
                    errorMessage.classList.remove('hidden');
                    errorMessage.textContent = 'Failed to load reservations: ' + (error.message || 'Unknown error occurred');
                }
                console.error('Error loading reservations:', error);
                
                // Log the error detail to console to help with debugging
                console.error('Error details:', error);
            });
    }
    
    function renderReservations(reservations, container) {
        // Clear existing content
        container.innerHTML = '';
        
        // Create row for grid layout
        const row = document.createElement('div');
        row.className = 'reservation-grid';
        container.appendChild(row);
        
        // Add each reservation to the container
        reservations.forEach(reservation => {
            const card = document.createElement('div');
            card.className = 'reservation-card';
            
            const statusClass = reservation.status.toLowerCase();
            
            card.innerHTML = `
                <div class="reservation-header ${statusClass}">
                    <h3 class="movie-title">
                        ${reservation.movie_title}
                        <span class="status-badge ${statusClass}">${reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)}</span>
                    </h3>
                </div>
                <div class="reservation-body">
                    <p><strong>Date:</strong> ${formatDate(reservation.date)}</p>
                    <p><strong>Time:</strong> ${reservation.start_time}</p>
                    <p><strong>Code:</strong> ${reservation.reservation_code}</p>
                </div>
                <div class="reservation-actions">
                    <button class="view-details-btn" data-reservation-id="${reservation.id}">
                        View Details
                    </button>
                    ${reservation.status === 'pending' ? 
                        `<button class="cancel-reservation-btn" 
                                 data-reservation-id="${reservation.id}">
                            Cancel
                        </button>` : ''
                    }
                    ${reservation.status === 'cancelled' || reservation.status === 'verified' ? 
                        `<button class="delete-reservation-btn" 
                                 data-reservation-id="${reservation.id}">
                            Delete
                        </button>` : ''
                    }
                </div>
            `;
            
            row.appendChild(card);
        });
    }
    
    function loadReservationDetails(reservationId) {
        const detailsModal = document.getElementById('reservation-details-modal');
        const detailsContent = document.getElementById('reservation-details-content');
        
        // Show loading
        detailsContent.innerHTML = `
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p>Loading reservation details...</p>
            </div>
        `;
        
        // Show the modal
        detailsModal.style.display = 'block';
        
        // AJAX request to get reservation details
        fetch(`./api/get_reservation_details.php?id=${reservationId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        console.error('Failed to parse JSON response:', text);
                        throw new Error('Failed to parse JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.error) {
                    detailsContent.innerHTML = `
                        <div class="alert-message error">
                            ${data.error}
                        </div>
                    `;
                    return;
                }
                
                const reservation = data.reservation;
                
                // Format seats
                const seats = reservation.seats.join(', ');
                
                // Ensure payment method shows Visa
                const paymentMethod = 'Visa';
                
                // Render details
                detailsContent.innerHTML = `
                    <div class="reservation-details-card">
                        <h3>Reservation Information</h3>
                        <div class="details-content">
                            <p><strong>Reservation Code:</strong> ${reservation.reservation_code}</p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge ${reservation.status.toLowerCase()}">${reservation.status.toUpperCase()}</span>
                            </p>
                            <p><strong>Date:</strong> ${formatDate(reservation.date)}</p>
                            <p><strong>Time:</strong> ${reservation.start_time} - ${reservation.end_time}</p>
                            <p><strong>Cinema:</strong> ${reservation.cinema_name}</p>
                            <p><strong>Movie:</strong> ${reservation.movie_title}</p>
                            <p><strong>Seats:</strong> ${seats}</p>
                            <p><strong>Total Amount:</strong> ₱${parseFloat(reservation.total_amount).toFixed(2)}</p>
                            <p><strong>Payment Method:</strong> ${paymentMethod}</p>
                            <p><strong>Booked On:</strong> ${formatDateTime(reservation.created_at)}</p>
                        </div>
                    </div>
                    
                    <div class="alert-message info">
                        <i class="fas fa-info-circle"></i> Please arrive at least 15 minutes before the movie starts.
                        Present this QR code at the cinema entrance.
                    </div>
                    
                    <div class="qr-container">
                        <img src="${reservation.qr_code}" alt="QR Code" class="qr-code">
                        <p class="qr-caption">Scan this QR code at the cinema entrance</p>
                    </div>
                `;
            })
            .catch(error => {
                detailsContent.innerHTML = `
                    <div class="alert-message error">
                        Failed to load reservation details. Please try again later.
                    </div>
                `;
                console.error('Error loading reservation details:', error);
            });
    }
    
    function cancelReservation(reservationId) {
        // AJAX request to cancel reservation
        fetch(`./api/cancel_reservation.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reservation_id: reservationId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (err) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Failed to parse JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Close details modal if open
                document.getElementById('reservation-details-modal').style.display = 'none';
                
                // Reload reservations
                loadReservations();
                
                // Show success message
                alert('Reservation cancelled successfully');
            } else {
                alert(data.error || 'Failed to cancel reservation');
            }
        })
        .catch(error => {
            alert('An error occurred while cancelling the reservation. Please try again later.');
            console.error('Error cancelling reservation:', error);
        });
    }
    
    function deleteReservation(reservationId) {
        // AJAX request to delete reservation
        fetch(`./api/delete_reservation.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reservation_id: reservationId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (err) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Failed to parse JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Close details modal if open
                document.getElementById('reservation-details-modal').style.display = 'none';
                
                // Reload reservations
                loadReservations();
                
                // Show success message
                alert('Reservation deleted successfully');
            } else {
                alert(data.error || 'Failed to delete reservation');
            }
        })
        .catch(error => {
            alert('An error occurred while deleting the reservation. Please try again later.');
            console.error('Error deleting reservation:', error);
        });
    }
    
    // Helper function to format dates
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }
    
    // Helper function to format date and time
    function formatDateTime(dateTimeString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateTimeString).toLocaleString(undefined, options);
    }
});