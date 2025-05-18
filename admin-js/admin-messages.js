/**
 * Admin Messages Scripts
 * Scripts specific to the admin messages functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Profile slide toggle for messages page
    const slideToggle = document.getElementById('slideToggle');
    const profileSlide = document.getElementById('profileSlide');
    
    if (slideToggle && profileSlide) {
        slideToggle.addEventListener('click', function() {
            profileSlide.classList.toggle('active');
            this.classList.toggle('active');
            
            const icon = this.querySelector('i');
            if (icon) {
                if (profileSlide.classList.contains('active')) {
                    icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                } else {
                    icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                }
            }
        });
    }
    
    // Mark all as read button
    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to mark all messages as read?')) {
                window.location.href = 'admin-messages.php?mark_all_read=1';
            }
        });
    }
    
    // Search functionality for messages
    const searchInput = document.querySelector('.search-bar input');
    const messageItems = document.querySelectorAll('.message-item');
    
    if (searchInput && messageItems.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            messageItems.forEach(item => {
                const subject = item.querySelector('h4').textContent.toLowerCase();
                const message = item.querySelector('p').textContent.toLowerCase();
                const from = item.querySelector('.message-meta span').textContent.toLowerCase();
                
                if (subject.includes(searchTerm) || message.includes(searchTerm) || from.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
}); 