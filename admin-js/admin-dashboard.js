/**
 * Admin Dashboard Scripts
 * Scripts specific to the main admin dashboard functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const toggleIcon = document.getElementById('toggleIcon');
    
    // Check if sidebar state is stored in localStorage
    const sidebarExpanded = localStorage.getItem('sidebarExpanded') === 'true';
    
    // Set initial state
    if (sidebarExpanded) {
        sidebar.classList.add('expanded');
        toggleIcon.classList.remove('fa-chevron-right');
        toggleIcon.classList.add('fa-chevron-left');
    }
    
    // Toggle sidebar on button click
    if (sidebarToggle && sidebar && toggleIcon) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('expanded');
            const isExpanded = sidebar.classList.contains('expanded');
            
            // Toggle icon direction
            if (isExpanded) {
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
            } else {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
            
            // Store state in localStorage
            localStorage.setItem('sidebarExpanded', isExpanded);
        });
    }
    
    // If on mobile, collapse sidebar by default
    function checkWindowSize() {
        if (window.innerWidth < 768 && !localStorage.getItem('sidebarExpanded')) {
            sidebar.classList.remove('expanded');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        }
    }
    
    // Check on load and resize
    checkWindowSize();
    window.addEventListener('resize', checkWindowSize);
}); 