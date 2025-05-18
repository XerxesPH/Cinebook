/**
 * Admin Script - Backward Compatibility
 * This file serves as a transition layer to maintain backward compatibility
 * while moving to a more modular script structure.
 * 
 * It loads the appropriate scripts based on the current page.
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Using legacy admin-script.js. Consider updating your script references to use specific admin scripts.');
    
    // Determine current page
    const currentPath = window.location.pathname;
    const isMessagesPage = currentPath.includes('admin-messages.php');
    const isDashboardPage = currentPath.includes('admin-dashboard.php');
    
    // Load appropriate scripts
    if (isMessagesPage) {
        // Check if admin-messages.js already loaded
        if (!window.adminMessagesLoaded) {
            loadScript('admin-js/admin-messages.js');
        }
    }
    
    if (isDashboardPage) {
        // Check if admin-dashboard.js already loaded
        if (!window.adminDashboardLoaded) {
            loadScript('admin-js/admin-dashboard.js');
        }
    }
    
    // Common functionality
    if (!window.adminCommonLoaded) {
        loadScript('admin-js/admin-common.js');
    }
    
    // Helper function to load scripts dynamically
    function loadScript(src) {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        document.head.appendChild(script);
        
        script.onload = function() {
            console.log(`Loaded script: ${src}`);
            if (src.includes('admin-messages.js')) {
                window.adminMessagesLoaded = true;
            } else if (src.includes('admin-dashboard.js')) {
                window.adminDashboardLoaded = true;
            } else if (src.includes('admin-common.js')) {
                window.adminCommonLoaded = true;
            }
        };
        
        script.onerror = function() {
            console.error(`Failed to load script: ${src}`);
        };
    }
});