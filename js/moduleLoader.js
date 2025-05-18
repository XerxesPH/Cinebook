// Module Loader for Seat Selection System
// This file loads all the seat selection modules in the correct order

document.addEventListener('DOMContentLoaded', function() {
    // Helper function to load a script
    function loadScript(url, callback) {
        console.log(`Loading script: ${url}`);
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        
        // When the script is loaded, execute the callback
        if (callback) {
            script.onload = function() {
                callback();
            };
        }
        
        // Append the script to the head
        document.head.appendChild(script);
    }
    
    // Define the scripts to load in order
    const scripts = [
        'js/seatSelection.js',
        'js/cardSelection.js',
        'js/paymentHandler.js',
        'js/reservationConfirmation.js',
        'js/seatSelectionMain.js'
    ];
    
    // Load scripts sequentially
    let index = 0;
    
    function loadNextScript() {
        if (index < scripts.length) {
            loadScript(scripts[index], function() {
                index++;
                loadNextScript();
            });
        } else {
            console.log('All seat selection modules loaded');
        }
    }
    
    // Start loading scripts
    loadNextScript();
}); 