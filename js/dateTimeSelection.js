// Variables for date and time selection
let selectedDate = null;
let selectedTime = null;
let selectedScreeningId = null;

// Make variables available to other scripts
window.selectedDate = selectedDate;
window.selectedTime = selectedTime;

// Load available dates for the selected movie and cinema
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

// Function to select a date
function selectDate(dateElement) {
    const date = dateElement.value;
    
    if (!date) {
        return;
    }
    
    selectedDate = date;
    window.selectedDate = selectedDate;
    
    // Get cinema_id and movie_id
    const cinemaId = document.getElementById('cinema-id').value;
    const movieId = document.getElementById('movie-id').value;
    
    // Load times for this date
    loadTimesForDate(cinemaId, movieId, date);
}

// Load available times for the selected date
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

// Function to select a time
function selectTime(timeElement) {
    const screeningId = timeElement.value;
    
    if (!screeningId) {
        return;
    }
    
    selectedTime = timeElement.options[timeElement.selectedIndex].textContent;
    selectedScreeningId = screeningId;
    
    // Update global variables
    window.selectedTime = selectedTime;
    window.selectedScreeningId = selectedScreeningId;
    
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
    if (typeof window.loadSeatsForScreening === 'function') {
        window.loadSeatsForScreening(selectedScreeningId);
    } else {
        console.error('loadSeatsForScreening function not found');
    }
}

// Expose functions to global scope
window.loadDatesForMovie = loadDatesForMovie;
window.loadTimesForDate = loadTimesForDate;
window.selectDate = selectDate;
window.selectTime = selectTime; 