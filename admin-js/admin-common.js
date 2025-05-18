/**
 * Admin Common Scripts
 * Shared functionality across all admin pages
 */
document.addEventListener('DOMContentLoaded', function() {
    // Common notification system
    window.showAdminNotification = function(message, type = 'success', duration = 3000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add notification to page
        document.body.appendChild(notification);
        
        // Show notification with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Remove notification after specified duration
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, duration);
    };
    
    // Common confirmation dialog
    window.showConfirmDialog = function(title, message, confirmCallback, cancelCallback) {
        // Create confirmation dialog
        const confirmDialog = document.createElement('div');
        confirmDialog.className = 'confirm-dialog';
        confirmDialog.innerHTML = `
            <div class="confirm-dialog-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="confirm-actions">
                    <button class="cancel-btn">Cancel</button>
                    <button class="confirm-btn">Confirm</button>
                </div>
            </div>
        `;
        
        // Add dialog to page
        document.body.appendChild(confirmDialog);
        
        // Handle dialog buttons
        confirmDialog.querySelector('.cancel-btn').addEventListener('click', function() {
            document.body.removeChild(confirmDialog);
            if (typeof cancelCallback === 'function') {
                cancelCallback();
            }
        });
        
        confirmDialog.querySelector('.confirm-btn').addEventListener('click', function() {
            document.body.removeChild(confirmDialog);
            if (typeof confirmCallback === 'function') {
                confirmCallback();
            }
        });
    };
    
    // Global error handler for AJAX requests
    window.handleAjaxError = function(error, errorElement) {
        console.error('AJAX Error:', error);
        if (errorElement) {
            errorElement.textContent = 'An error occurred: ' + error.message;
            errorElement.style.display = 'block';
        } else {
            window.showAdminNotification('An error occurred: ' + error.message, 'error');
        }
    };
}); 