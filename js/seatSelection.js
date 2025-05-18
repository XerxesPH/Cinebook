// Seat Selection Module
// Handles all seat selection functionality

// Seat Selection Variables
let selectedSeats = [];
const seatPrice = 300;
let selectedDate = null;
let selectedTime = null;
let selectedScreeningId = null;
let refreshInterval = null; // Store the interval ID for clearing when modal closes

// Make these variables and functions available to other scripts
window.selectedSeats = selectedSeats;
window.displaySeatSelection = displaySeatSelection;
window.resetSelectedSeats = resetSelectedSeats;
window.toggleSeatSelection = toggleSeatSelection;
window.selectDate = selectDate;
window.selectTime = selectTime;
window.loadDatesForMovie = loadDatesForMovie;
window.loadTimesForDate = loadTimesForDate;
window.stopSeatRefresh = stopSeatRefresh;
window.refreshSeatsNow = refreshSeatsNow; // Add immediate refresh function

/**
 * Display Seat Selection
 * @param {string} cinemaId - The ID of the cinema
 * @param {string} movieId - The ID of the movie
 * @param {string} showDate - The selected show date
 * @param {string} showTime - The selected show time
 * @param {string} screeningId - The selected screening ID
 */
function displaySeatSelection(cinemaId, movieId, showDate, showTime, screeningId) {
    // This function fetches cinema and seat data
    const seatsGrid = document.getElementById('seats-grid');
    
    // Check if required elements exist
    const cinemaIdInput = document.getElementById('cinema-id');
    const movieIdInput = document.getElementById('movie-id');
    const screeningIdInput = document.getElementById('screening-id');
    
    // Handle case when inputs don't exist
    if (!cinemaIdInput || !movieIdInput || !screeningIdInput) {
        console.error('Required input elements not found in the DOM. Make sure the seat selection modal is included.');
        alert('Seat selection is not available at this moment. Please try again later.');
        return;
    }
    
    if (cinemaId) {
        cinemaIdInput.value = cinemaId;
    }
    if (movieId) {
        movieIdInput.value = movieId;
    }
    if (screeningId) {
        screeningIdInput.value = screeningId;
    }
    
    resetSelectedSeats();
    
    // Store the selected date and time received from movieInfoModal
    selectedDate = showDate;
    selectedTime = showTime;
    selectedScreeningId = screeningId;
    
    // Update global variables
    window.selectedDate = selectedDate;
    window.selectedTime = selectedTime;
    window.selectedScreeningId = selectedScreeningId;

    // Get movie and cinema info
    fetchMovieAndCinemaInfo(cinemaId, movieId);
    
    // Update show time display with the received date and time
    updateShowTimeDisplay();
    
    // Clear seats grid
    if (seatsGrid) {
        seatsGrid.innerHTML = '';
        // Load seats directly if we have a screeningId
        if (screeningId) {
            loadSeatsForScreening(screeningId);
            
            // Start periodic refresh of seat data
            startSeatRefresh(screeningId);
        } else {
            // Generate default seat grid if no screening ID provided
            generateSeatGrid(8, 15);
        }
    } else {
        console.error('Seats grid element not found');
    }
}

/**
 * Start periodic refresh of seat availability
 * @param {string} screeningId - The ID of the screening
 */
function startSeatRefresh(screeningId) {
    // Clear any existing interval
    stopSeatRefresh();
    
    // Refresh seat data every 10 seconds
    refreshInterval = setInterval(() => {
        refreshSeatsData(screeningId);
    }, 10000); // 10 seconds
}

/**
 * Stop seat refresh interval when modal closes
 */
function stopSeatRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

/**
 * Refresh seats data without resetting user's selected seats
 * @param {string} screeningId - The ID of the screening
 */
function refreshSeatsData(screeningId) {
    console.log("Refreshing seats data for screening:", screeningId);
    
    fetch(`./api/get_seats.php?screening_id=${screeningId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error("Failed to refresh seat data:", data.message);
                return;
            }
            
            // Get all seat elements
            const seatElements = document.querySelectorAll('#seats-grid .seat');
            
            // Create a map of seat numbers to their status from the API data
            const seatStatusMap = {};
            data.seats.forEach(seat => {
                seatStatusMap[seat.seat_number] = seat.status;
            });
            
            // Update each seat's status
            seatElements.forEach(seatEl => {
                const seatId = seatEl.getAttribute('data-seat');
                const newStatus = seatStatusMap[seatId] || 'available';
                
                // Skip updating seats that the current user has selected
                if (selectedSeats.includes(seatId)) {
                    return;
                }
                
                // Update seat class based on new status
                seatEl.classList.remove('available', 'reserved');
                seatEl.classList.add(newStatus);
                
                // Update click event based on status
                if (newStatus === 'reserved') {
                    // Remove click event for reserved seats
                    seatEl.replaceWith(seatEl.cloneNode(true));
                } else if (newStatus === 'available' && !seatEl.onclick) {
                    // Add click event for available seats if they don't have it
                    seatEl.addEventListener('click', function() {
                        toggleSeatSelection(this);
                    });
                }
            });
            
            console.log("Seat data refreshed successfully");
        })
        .catch(error => {
            console.error("Error refreshing seats:", error);
        });
}

/**
 * Fetch movie and cinema information
 * @param {string} cinemaId - The ID of the cinema
 * @param {string} movieId - The ID of the movie
 */
function fetchMovieAndCinemaInfo(cinemaId, movieId) {
    // Check if required elements exist
    const cinemaNameEl = document.getElementById('cinema-name');
    const movieNameEl = document.getElementById('movie-name');
    
    if (!cinemaNameEl || !movieNameEl) {
        console.warn('Cinema name or movie name elements not found');
        return;
    }
    
    // In a real implementation, this would fetch from your database
    fetch(`./api/get_movie_cinema_info.php?cinema_id=${cinemaId}&movie_id=${movieId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (cinemaNameEl) cinemaNameEl.textContent = data.cinema.name;
                if (movieNameEl) movieNameEl.textContent = data.movie.title;
            } else {
                console.warn('API returned success: false', data);
                
                // Set default values if API fails
                if (cinemaNameEl) cinemaNameEl.textContent = 'Cinema ' + cinemaId;
                if (movieNameEl) movieNameEl.textContent = 'Selected Movie';
            }
        })
        .catch(error => {
            console.error('Error fetching movie/cinema info:', error);
            
            // Set default values if API fails
            if (cinemaNameEl) cinemaNameEl.textContent = 'Cinema ' + cinemaId;
            if (movieNameEl) movieNameEl.textContent = 'Selected Movie';
        });
}

/**
 * Load available dates for the selected movie and cinema
 * @param {string} cinemaId - The ID of the cinema
 * @param {string} movieId - The ID of the movie
 */
function loadDatesForMovie(cinemaId, movieId) {
    const dateSelector = document.getElementById('date-selector');
    
    if (!dateSelector) {
        console.error('Date selector element not found');
        return;
    }
    
    // Clear previous options
    dateSelector.innerHTML = '<option value="">Select a date</option>';
    
    // In a real implementation, this would fetch from your database
    fetch(`./api/get_available_dates.php?cinema_id=${cinemaId}&movie_id=${movieId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.dates.length > 0) {
                data.dates.forEach(date => {
                    const option = document.createElement('option');
                    option.value = date.date;
                    
                    // Format the date for display (e.g., "Thursday, May 8")
                    const dateObj = new Date(date.date);
                    const formattedDate = dateObj.toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    
                    option.textContent = formattedDate;
                    dateSelector.appendChild(option);
                });
                
                // Enable the date selector
                dateSelector.disabled = false;
            } else {
                // No dates available
                const option = document.createElement('option');
                option.textContent = 'No dates available';
                dateSelector.appendChild(option);
                dateSelector.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading dates:', error);
            dateSelector.innerHTML = '<option>Error loading dates</option>';
        });
        
    // Clear the time selector too
    const timeSelector = document.getElementById('time-selector');
    if (timeSelector) {
        timeSelector.innerHTML = '<option value="">Select time</option>';
        timeSelector.disabled = true;
    }
}

/**
 * Function to select a date
 * @param {HTMLElement} dateElement - The date selector element
 */
function selectDate(dateElement) {
    const date = dateElement.value;
    
    if (!date) {
        return;
    }
    
    selectedDate = date;
    
    // Get cinema_id and movie_id
    const cinemaId = document.getElementById('cinema-id').value;
    const movieId = document.getElementById('movie-id').value;
    
    // Load times for this date
    loadTimesForDate(cinemaId, movieId, date);
}

/**
 * Load available times for the selected date
 * @param {string} cinemaId - The ID of the cinema
 * @param {string} movieId - The ID of the movie
 * @param {string} date - The selected date
 */
function loadTimesForDate(cinemaId, movieId, date) {
    const timeSelector = document.getElementById('time-selector');
    
    if (!timeSelector) {
        console.error('Time selector element not found');
        return;
    }
    
    // Clear previous options
    timeSelector.innerHTML = '<option value="">Select a time</option>';
    
    // In a real implementation, this would fetch from your database
    fetch(`./api/get_available_times.php?cinema_id=${cinemaId}&movie_id=${movieId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.times.length > 0) {
                data.times.forEach(timeSlot => {
                    const option = document.createElement('option');
                    option.value = timeSlot.id; // Store the screening_id as the value
                    option.textContent = timeSlot.start_time;
                    option.dataset.screeningId = timeSlot.id;
                    timeSelector.appendChild(option);
                });
                
                // Enable the time selector
                timeSelector.disabled = false;
            } else {
                // No times available
                const option = document.createElement('option');
                option.textContent = 'No times available';
                timeSelector.appendChild(option);
                timeSelector.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading times:', error);
            timeSelector.innerHTML = '<option>Error loading times</option>';
        });
        
    // Clear the seats grid until a time is selected
    const seatsGrid = document.getElementById('seats-grid');
    if (seatsGrid) {
        seatsGrid.innerHTML = '';
    }
}

/**
 * Function to select a time
 * @param {HTMLElement} timeElement - The time selector element
 */
function selectTime(timeElement) {
    const screeningId = timeElement.value;
    
    if (!screeningId) {
        return;
    }
    
    selectedTime = timeElement.options[timeElement.selectedIndex].textContent;
    selectedScreeningId = screeningId;
    
    // Update show time display
    const showTimeEl = document.getElementById('show-time');
    if (showTimeEl) {
        // Format the date
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            weekday: 'short', 
            month: 'short', 
            day: 'numeric' 
        });
        
        showTimeEl.textContent = `${formattedDate} at ${selectedTime}`;
    }
    
    // Set the screening_id hidden input
    const screeningIdInput = document.getElementById('screening-id');
    if (screeningIdInput) {
        screeningIdInput.value = selectedScreeningId;
    }
    
    // Now load the seats for this specific screening
    loadSeatsForScreening(selectedScreeningId);
}

/**
 * Load seats for the selected screening
 * @param {string} screeningId - The ID of the screening
 */
function loadSeatsForScreening(screeningId) {
    const seatsGrid = document.getElementById('seats-grid');
    
    if (!seatsGrid) {
        console.error('Seats grid element not found');
        return;
    }
    
    // Clear the seats grid
    seatsGrid.innerHTML = '';
    
    // Show loading indicator
    seatsGrid.innerHTML = '<div class="loading-seats">Loading seats...</div>';
    
    // In a real implementation, this would fetch from your database
    fetch(`./api/get_seats.php?screening_id=${screeningId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear loading indicator
                seatsGrid.innerHTML = '';
                
                // Generate the seat grid based on the returned data
                const rows = data.seatLayout.rows;
                const seatsPerRow = data.seatLayout.seatsPerRow;
                
                generateSeatGridFromData(rows, seatsPerRow, data.seats);
                
                // Enable the proceed button if needed
                const proceedButton = document.querySelector('.proceed-btn');
                if (proceedButton) {
                    proceedButton.disabled = selectedSeats.length === 0;
                }
            } else {
                seatsGrid.innerHTML = '<div class="error-message">Failed to load seats</div>';
            }
        })
        .catch(error => {
            console.error('Error loading seats:', error);
            seatsGrid.innerHTML = '<div class="error-message">Error loading seats</div>';
        });
}

/**
 * Generate seat grid with data from the server
 * @param {number} rows - Number of rows
 * @param {number} seatsPerRow - Number of seats per row
 * @param {Array} seatsData - Array of seat data objects
 */
function generateSeatGridFromData(rows, seatsPerRow, seatsData) {
    const seatsGrid = document.getElementById('seats-grid');
    
    if (!seatsGrid) {
        console.error('Seats grid element not found');
        return;
    }
    
    for (let row = 1; row <= rows; row++) {
        const rowElement = document.createElement('div');
        rowElement.className = 'seat-row';
        
        // Row label (A, B, C, etc.)
        const rowLabel = document.createElement('div');
        rowLabel.className = 'row-label';
        rowLabel.textContent = String.fromCharCode(64 + row); // A=65, B=66, etc.
        rowElement.appendChild(rowLabel);
        
        // Seats in this row
        for (let seat = 1; seat <= seatsPerRow; seat++) {
            // Add stairs (space) after every 5 seats
            if (seat > 1 && (seat - 1) % 5 === 0) {
                const stairSpace = document.createElement('div');
                stairSpace.className = 'stair-space';
                rowElement.appendChild(stairSpace);
                
                // Add another stair space
                const stairSpace2 = document.createElement('div');
                stairSpace2.className = 'stair-space';
                rowElement.appendChild(stairSpace2);
            }
            
            const seatElement = document.createElement('div');
            
            // Set seat ID in format "A1", "B2", etc.
            const rowLetter = String.fromCharCode(64 + row);
            const seatId = `${rowLetter}${seat}`;
            
            // Find this seat in the seatsData
            const seatData = seatsData.find(s => s.seat_number === seatId);
            
            if (seatData) {
                // Set the class based on the seat status
                if (seatData.status === 'available') {
                    seatElement.className = 'seat available';
                } else if (seatData.status === 'reserved' || seatData.status === 'occupied') {
                    seatElement.className = 'seat reserved';
                }
            } else {
                seatElement.className = 'seat available';
            }
            
            seatElement.setAttribute('data-seat', seatId);
            seatElement.textContent = seat;
            
            // Add click event to seat only if it's available
            if (!seatElement.classList.contains('reserved')) {
                seatElement.addEventListener('click', function() {
                    toggleSeatSelection(this);
                });
            }
            
            rowElement.appendChild(seatElement);
        }
        
        seatsGrid.appendChild(rowElement);
    }
}

/**
 * Generate seat grid with rows and columns
 * @param {number} rows - Number of rows
 * @param {number} seatsPerRow - Number of seats per row
 */
function generateSeatGrid(rows, seatsPerRow) {
    const seatsGrid = document.getElementById('seats-grid');
    
    if (!seatsGrid) {
        console.error('Seats grid element not found');
        return;
    }
    
    for (let row = 1; row <= rows; row++) {
        const rowElement = document.createElement('div');
        rowElement.className = 'seat-row';
        
        // Row label (A, B, C, etc.)
        const rowLabel = document.createElement('div');
        rowLabel.className = 'row-label';
        rowLabel.textContent = String.fromCharCode(64 + row); // A=65, B=66, etc.
        rowElement.appendChild(rowLabel);
        
        // Seats in this row
        for (let seat = 1; seat <= seatsPerRow; seat++) {
            // Add stairs (space) after every 5 seats
            if (seat > 1 && (seat - 1) % 5 === 0) {
                const stairSpace = document.createElement('div');
                stairSpace.className = 'stair-space';
                rowElement.appendChild(stairSpace);
                
                // Add another stair space
                const stairSpace2 = document.createElement('div');
                stairSpace2.className = 'stair-space';
                rowElement.appendChild(stairSpace2);
            }
            
            const seatElement = document.createElement('div');
            seatElement.className = 'seat available';
            
            // Set seat ID in format "A1", "B2", etc.
            const rowLetter = String.fromCharCode(64 + row);
            const seatId = `${rowLetter}${seat}`;
            seatElement.setAttribute('data-seat', seatId);
            seatElement.textContent = seat;
            
            // Add click event to seat
            seatElement.addEventListener('click', function() {
                if (!this.classList.contains('reserved')) {
                    toggleSeatSelection(this);
                }
            });
            
            rowElement.appendChild(seatElement);
        }
        
        seatsGrid.appendChild(rowElement);
    }
}

/**
 * Reset selected seats
 */
function resetSelectedSeats() {
    const seatsGrid = document.getElementById('seats-grid');
    
    selectedSeats = [];
    window.selectedSeats = selectedSeats; // Update global reference
    updateSelectedSeatsInfo();
    
    // Reset visual selection if seats are already displayed
    if (seatsGrid) {
        const selectedSeatElements = seatsGrid.querySelectorAll('.seat.selected');
        selectedSeatElements.forEach(seat => {
            seat.classList.remove('selected');
        });
    }
}

/**
 * Toggle seat selection
 * @param {HTMLElement} seatElement - The seat element
 */
function toggleSeatSelection(seatElement) {
    if (!seatElement) return;
    
    const seatId = seatElement.getAttribute('data-seat');
    if (!seatId) return;
    
    if (seatElement.classList.contains('selected')) {
        // Deselect seat
        seatElement.classList.remove('selected');
        selectedSeats = selectedSeats.filter(seat => seat !== seatId);
    } else {
        // Select seat
        seatElement.classList.add('selected');
        selectedSeats.push(seatId);
    }
    
    // Update global reference
    window.selectedSeats = selectedSeats;
    
    updateSelectedSeatsInfo();
}

/**
 * Update selected seats information
 */
function updateSelectedSeatsInfo() {
    const selectedSeatsDisplay = document.getElementById('selected-seats');
    const totalPriceDisplay = document.getElementById('total-price');
    const proceedButton = document.querySelector('.proceed-btn');
    
    if (!selectedSeatsDisplay || !totalPriceDisplay || !proceedButton) {
        console.error('Required display elements not found');
        return;
    }
    
    if (selectedSeats.length > 0) {
        // Sort seats by row and number
        selectedSeats.sort((a, b) => {
            if (a[0] !== b[0]) {
                return a.charCodeAt(0) - b.charCodeAt(0); // Sort by letter
            } else {
                return parseInt(a.substring(1)) - parseInt(b.substring(1)); // Sort by number
            }
        });
        
        selectedSeatsDisplay.textContent = selectedSeats.join(', ');
        totalPriceDisplay.textContent = (selectedSeats.length * seatPrice).toFixed(2);
        proceedButton.disabled = false;
    } else {
        selectedSeatsDisplay.textContent = 'None';
        totalPriceDisplay.textContent = '0.00';
        proceedButton.disabled = true;
    }
}

// Initialize seat selection functionality when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize seat selection UI elements
    const seatsGrid = document.getElementById('seats-grid');
    const dateSelector = document.getElementById('date-selector');
    const timeSelector = document.getElementById('time-selector');
    
    // If date selector exists, add change event listener
    if (dateSelector) {
        dateSelector.addEventListener('change', function() {
            selectDate(this);
        });
    }
    
    // If time selector exists, add change event listener
    if (timeSelector) {
        timeSelector.addEventListener('change', function() {
            selectTime(this);
        });
    }
});

// Expose functions to the global scope
window.resetSelectedSeats = resetSelectedSeats;
window.toggleSeatSelection = toggleSeatSelection;
window.resetBookingState = resetBookingState;

// Reset booking state
function resetBookingState() {
    resetSelectedSeats();
    // Any other reset operations needed
}

// Helper function to format the current time
function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Update the show time display with the selected date and time
 */
function updateShowTimeDisplay() {
    const showTimeEl = document.getElementById('show-time');
    if (!showTimeEl) {
        console.warn('Show time element not found');
        return;
    }
    
    if (selectedDate && selectedTime) {
        // Format the date
        const dateObj = new Date(selectedDate);
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            weekday: 'short', 
            month: 'short', 
            day: 'numeric' 
        });
        
        showTimeEl.textContent = `${formattedDate} at ${selectedTime}`;
    } else {
        showTimeEl.textContent = 'Date and time not selected';
    }
}

/**
 * Trigger an immediate refresh of seat data
 * This can be called after a successful reservation to update the UI
 */
function refreshSeatsNow() {
    if (selectedScreeningId) {
        refreshSeatsData(selectedScreeningId);
    }
}