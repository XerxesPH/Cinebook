<!-- Login Prompt Modal -->
<div class="modal" id="loginPromptModal">
    <div class="modal-content">
        <span class="close-btn" id="closeLoginPromptModal">&times;</span>
        <h2>Login Required</h2>
        <p>You need to be logged in to complete this booking.</p>
        <div class="login-prompt-actions">
            <button id="goToLoginBtn" class="primary-btn">Login</button>
            <button id="goToRegisterBtn" class="secondary-btn">Register</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginPromptModal = document.getElementById('loginPromptModal');
    const closeLoginPromptModalBtn = document.getElementById('closeLoginPromptModal');
    const goToLoginBtn = document.getElementById('goToLoginBtn');
    const goToRegisterBtn = document.getElementById('goToRegisterBtn');
    const seatModal = document.getElementById('seatModal');
    const loginModal = document.getElementById('loginModal');
    
    // Check if elements exist before adding event listeners
    if (!loginPromptModal) {
        console.error('Login prompt modal not found');
        return;
    }
    
    // Add event listeners for the login prompt modal
    if (closeLoginPromptModalBtn && seatModal) {
        closeLoginPromptModalBtn.addEventListener('click', function() {
            loginPromptModal.style.display = 'none';
            seatModal.style.display = 'block'; // Go back to seat selection
        });
    }
    
    // Show the login modal with login tab active
    if (goToLoginBtn) {
        goToLoginBtn.addEventListener('click', function() {
            // Hide login prompt modal
            loginPromptModal.style.display = 'none';
            
            // Show the login/register modal
            if (loginModal) {
                loginModal.style.display = 'block';
                
                // Activate the login tab
                const loginTab = document.querySelector('.tab-btn[data-tab="login"]');
                if (loginTab) {
                    // Simulate a click on the login tab button
                    loginTab.click();
                }
                
                // Store booking state to return after login
                sessionStorage.setItem('redirect_after_login', 'booking');
                
                // Get selected seats from the global variable or a data attribute
                const selectedSeats = window.selectedSeats || [];
                sessionStorage.setItem('selected_seats', JSON.stringify(selectedSeats));
                // You might want to store other booking information as well
            } else {
                console.error('Login modal not found');
                // Fallback to the original behavior
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
        });
    }
    
    // Show the login modal with register tab active
    if (goToRegisterBtn) {
        goToRegisterBtn.addEventListener('click', function() {
            // Hide login prompt modal
            loginPromptModal.style.display = 'none';
            
            // Show the login/register modal
            if (loginModal) {
                loginModal.style.display = 'block';
                
                // Activate the register tab
                const registerTab = document.querySelector('.tab-btn[data-tab="register"]');
                if (registerTab) {
                    // Simulate a click on the register tab button
                    registerTab.click();
                }
                
                // Store booking state to return after registration
                sessionStorage.setItem('redirect_after_login', 'booking');
                
                // Get selected seats from the global variable or a data attribute
                const selectedSeats = window.selectedSeats || [];
                sessionStorage.setItem('selected_seats', JSON.stringify(selectedSeats));
                // You might want to store other booking information as well
            } else {
                console.error('Login modal not found');
                // Fallback to the original behavior
                window.location.href = 'register.php?redirect=' + encodeURIComponent(window.location.href);
            }
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === loginPromptModal && seatModal) {
            loginPromptModal.style.display = 'none';
            seatModal.style.display = 'block'; // Go back to seat selection
        }
    });
});</script>