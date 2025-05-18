// Fix for login-modal.js (paste-2.txt)
document.addEventListener('DOMContentLoaded', function() {
    // Login/Register Modal Elements
    const loginModal = document.getElementById('loginModal');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Initialize tabs functionality
    initTabs();
    
    // Check if we need to return to booking after login
    checkRedirectAfterLogin();
    
    // Handle login form submission
    loginForm?.addEventListener('submit', function(e) {
        // Don't prevent default form submission - let PHP handle it
        
        // Optional: Add client-side validation if needed
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        
        if (!email || !password) {
            alert('Please fill in all fields');
            e.preventDefault();
            return;
        }
        
        // Add booking data to the form submission if needed
        if (sessionStorage.getItem('redirect_after_login') === 'booking') {
            // Get booking data from session storage
            const bookingData = JSON.parse(sessionStorage.getItem('booking_data') || '{}');
            const selectedSeats = sessionStorage.getItem('selected_seats');
            
            // Create hidden input field for redirect purpose
            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_after_login';
            redirectInput.value = 'booking';
            loginForm.appendChild(redirectInput);
            
            // Add booking data as hidden inputs
            if (bookingData && bookingData.movieId) {
                for (const [key, value] of Object.entries(bookingData)) {
                    if (value) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'booking_' + key;
                        input.value = value;
                        loginForm.appendChild(input);
                    }
                }
            }
            
            // Add selected seats if available
            if (selectedSeats) {
                const seatsInput = document.createElement('input');
                seatsInput.type = 'hidden';
                seatsInput.name = 'selected_seats';
                seatsInput.value = selectedSeats;
                loginForm.appendChild(seatsInput);
            }
        }
    });
    
    // Handle register form submission
    registerForm?.addEventListener('submit', function(e) {
        // Don't prevent default form submission - let PHP handle it
        
        // Add client-side validation for password matching
        const password = document.getElementById('register-password').value;
        const confirmPassword = document.getElementById('register-confirm-password').value;
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            e.preventDefault();
            return;
        }
        
        // Add booking data to the form submission if needed
        if (sessionStorage.getItem('redirect_after_login') === 'booking') {
            // Get booking data from session storage
            const bookingData = JSON.parse(sessionStorage.getItem('booking_data') || '{}');
            const selectedSeats = sessionStorage.getItem('selected_seats');
            
            // Create hidden input field for redirect purpose
            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_after_login';
            redirectInput.value = 'booking';
            registerForm.appendChild(redirectInput);
            
            // Add booking data as hidden inputs
            if (bookingData && bookingData.movieId) {
                for (const [key, value] of Object.entries(bookingData)) {
                    if (value) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'booking_' + key;
                        input.value = value;
                        registerForm.appendChild(input);
                    }
                }
            }
            
            // Add selected seats if available
            if (selectedSeats) {
                const seatsInput = document.createElement('input');
                seatsInput.type = 'hidden';
                seatsInput.name = 'selected_seats';
                seatsInput.value = selectedSeats;
                registerForm.appendChild(seatsInput);
            }
        }
    });
    
    // Initialize tabs functionality
    function initTabs() {
        if (!tabButtons.length || !tabContents.length) return;
        
        // Hide all tab contents except the first one
        tabContents.forEach((content, index) => {
            if (index !== 0) {
                content.style.display = 'none';
            }
        });
        
        // Add click event listeners to tab buttons
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all buttons
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.style.display = 'none';
                });
                
                // Show the selected tab content
                document.getElementById(tabId).style.display = 'block';
            });
        });
    }
    
    // Check if we need to return to booking after login
    function checkRedirectAfterLogin() {
        // Check if the user just logged in and needs to return to booking
        const urlParams = new URLSearchParams(window.location.search);
        const loginSuccess = urlParams.get('login_success');
        
        // First, check for server-side session data from PHP
        if (typeof phpSessionData !== 'undefined' && phpSessionData) {
            if (phpSessionData.bookingData && loginSuccess === 'true') {
                // Found server-side booking data
                const bookingData = phpSessionData.bookingData;
                const selectedSeats = phpSessionData.selectedSeats || null;
                
                // Process the booking data from the server
                if (bookingData && bookingData.movieId) {
                    // Check if the seat selection module is loaded
                    if (typeof window.displaySeatSelection === 'function') {
                        // Call the seat selection function with the stored booking data
                        window.displaySeatSelection(
                            bookingData.cinemaId,
                            bookingData.movieId,
                            bookingData.date,
                            bookingData.time,
                            bookingData.screeningId
                        );
                        
                        // Show the seat selection modal
                        const seatModal = document.getElementById('seatModal');
                        if (seatModal) {
                            seatModal.style.display = 'block';
                        }
                        
                        // If we have previously selected seats, restore them
                        if (selectedSeats) {
                            try {
                                const seats = JSON.parse(selectedSeats);
                                if (Array.isArray(seats) && seats.length > 0) {
                                    // Restore selected seats
                                    const seatElements = document.querySelectorAll('.seat');
                                    seats.forEach(seatId => {
                                        seatElements.forEach(elem => {
                                            if (elem.getAttribute('data-seat') === seatId) {
                                                // Toggle the seat selection
                                                if (typeof window.toggleSeatSelection === 'function') {
                                                    window.toggleSeatSelection(elem);
                                                } else {
                                                    elem.classList.add('selected');
                                                }
                                            }
                                        });
                                    });
                                }
                            } catch (e) {
                                console.error('Error restoring selected seats:', e);
                            }
                        }
                        
                        return; // Exit function after processing server data
                    }
                }
            }
        }
        
        // Fall back to client-side session storage if no server-side data
        const redirectAfterLogin = sessionStorage.getItem('redirect_after_login');
        
        if (loginSuccess === 'true' && redirectAfterLogin === 'booking') {
            // Clear the redirect flag
            sessionStorage.removeItem('redirect_after_login');
            
            // Get the booking data and selected seats
            const bookingData = JSON.parse(sessionStorage.getItem('booking_data') || '{}');
            const selectedSeats = sessionStorage.getItem('selected_seats');
            
            // Clear the session storage items
            sessionStorage.removeItem('booking_data');
            sessionStorage.removeItem('selected_seats');
            
            // Check if we have booking data
            if (bookingData && bookingData.movieId) {
                // Check if the seat selection module is loaded
                if (typeof window.displaySeatSelection === 'function') {
                    // Call the seat selection function with the stored booking data
                    window.displaySeatSelection(
                        bookingData.cinemaId,
                        bookingData.movieId,
                        bookingData.date,
                        bookingData.time,
                        bookingData.screeningId
                    );
                    
                    // If we have previously selected seats, restore them
                    if (selectedSeats) {
                        try {
                            const seats = JSON.parse(selectedSeats);
                            if (Array.isArray(seats) && seats.length > 0) {
                                // Restore selected seats
                                const seatElements = document.querySelectorAll('.seat');
                                seats.forEach(seatId => {
                                    seatElements.forEach(elem => {
                                        if (elem.getAttribute('data-seat') === seatId) {
                                            // Toggle the seat selection
                                            if (typeof window.toggleSeatSelection === 'function') {
                                                window.toggleSeatSelection(elem);
                                            } else {
                                                elem.classList.add('selected');
                                            }
                                        }
                                    });
                                });
                            }
                        } catch (e) {
                            console.error('Error restoring selected seats:', e);
                        }
                    }
                    
                    // Show the seat selection modal
                    const seatModal = document.getElementById('seatModal');
                    if (seatModal) {
                        seatModal.style.display = 'block';
                    }
                }
            }
        }
    }
    
    // Close modal when clicking the close button
    document.querySelector('#loginModal .close-btn')?.addEventListener('click', function() {
        loginModal.style.display = 'none';
        
        // FIXED: Only show seat selection modal if we were in the booking flow
        if (sessionStorage.getItem('redirect_after_login') === 'booking') {
            const seatModal = document.getElementById('seatModal');
            if (seatModal) {
                seatModal.style.display = 'block';
            }
        }
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === loginModal) {
            loginModal.style.display = 'none';
            
            // FIXED: Only show seat selection modal if we were in the booking flow
            if (sessionStorage.getItem('redirect_after_login') === 'booking') {
                const seatModal = document.getElementById('seatModal');
                if (seatModal) {
                    seatModal.style.display = 'block';
                }
            }
        }
    });
    
    // Make the login modal accessible globally
    window.showLoginModal = function(tabToShow = 'login') {
        if (!loginModal) return;
        
        // Show the modal
        loginModal.style.display = 'block';
        
        // Select the appropriate tab
        const tabBtn = document.querySelector(`.tab-btn[data-tab="${tabToShow}"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    };
});