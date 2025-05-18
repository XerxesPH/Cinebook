<!-- Card Selection Modal -->
<div class="modal" id="cardSelectionModal">
    <div class="modal-content">
        <span class="close-btn" id="closeCardSelectionModal">&times;</span>
        <h2>Select Payment Card</h2>
        <div id="saved-cards-list" class="saved-cards-list">
            <!-- Cards will be populated here -->
        </div>
        <div class="card-selection-actions">
            <button id="useNewCardBtn" class="secondary-btn">Use New Card</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cardSelectionModal = document.getElementById('cardSelectionModal');
    const closeCardSelectionModalBtn = document.getElementById('closeCardSelectionModal');
    const useNewCardBtn = document.getElementById('useNewCardBtn');
    const paymentModal = document.getElementById('paymentModal');
    
    // First verify if elements exist
    if (!cardSelectionModal) {
        console.error('Card selection modal element not found');
        return;
    }
    
    // Add event listeners for the card selection modal if elements exist
    if (closeCardSelectionModalBtn) {
        closeCardSelectionModalBtn.addEventListener('click', function() {
            cardSelectionModal.style.display = 'none';
        });
    }
    
    if (useNewCardBtn && paymentModal) {
        useNewCardBtn.addEventListener('click', function() {
            // Switch to payment modal to enter new card details
            cardSelectionModal.style.display = 'none';
            paymentModal.style.display = 'block';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === cardSelectionModal) {
            cardSelectionModal.style.display = 'none';
        }
    });
    
    // Add event listeners to card selection buttons (dynamically added)
    document.addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('use-card-btn')) {
            const cardId = event.target.getAttribute('data-card-id');
            // Check if the function exists before calling it
            if (typeof window.processPaymentWithCard === 'function') {
                window.processPaymentWithCard(cardId);
            } else {
                console.error('processPaymentWithCard function not found');
            }
        }
    });
});</script>