// Card Selection functionality

function checkForSavedCards() {
    console.log('checkForSavedCards function called');

    const isLoggedIn = 
        document.body.classList.contains('logged-in') || 
        sessionStorage.getItem('user_logged_in') === 'true' ||
        (window.userLoggedIn === true); // Add this line to check for a global variable that PHP might set
    
    console.log('User logged in detection result:', isLoggedIn);
    
    const seatModal = document.getElementById('seatModal');
    const cardModal = document.getElementById('cardModal');
    const loginPromptModal = document.getElementById('loginPromptModal');
    
    if (!isLoggedIn) {
        // User is not logged in, show login prompt
        console.log('User not logged in, showing login prompt');
        if (seatModal) seatModal.style.display = 'none';
        if (loginPromptModal) {
            loginPromptModal.style.display = 'block';
        } else {
            console.error('Login prompt modal not found');
        }
        return;
    }
    
    console.log('User is logged in, fetching saved cards');
    // User is logged in, fetch their cards
    
    if (typeof fetch !== 'function' || window.bypassCardFetch) {
        console.log('Bypassing card fetch, showing card modal directly');
        if (seatModal) seatModal.style.display = 'none';
        if (cardModal) {
            // Reset and prepare the card form
            if (document.getElementById('cardModalTitle')) {
                document.getElementById('cardModalTitle').textContent = 'Add Payment Card';
            }
            if (document.getElementById('cardForm')) {
                document.getElementById('cardForm').reset();
            }
            cardModal.style.display = 'block';
        } else {
            console.error('Card modal not found');
        }
        return;
    }
    
    fetch('./payment/get_payment_cards.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Server responded with status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Card data received:', data);
            if (data.success) {
                const cards = data.cards;
                
                if (cards && cards.length > 0) {
                    // User has saved cards, show card selection modal
                    console.log('User has saved cards, showing card selection modal');
                    showCardSelectionModal(cards);
                } else {
                    // User has no saved cards, show card modal for adding a new card
                    console.log('User has no saved cards, showing card modal');
                    if (seatModal) seatModal.style.display = 'none';
                    if (cardModal) {
                        // Reset and prepare the card form
                        if (document.getElementById('cardModalTitle')) {
                            document.getElementById('cardModalTitle').textContent = 'Add Payment Card';
                        }
                        if (document.getElementById('cardForm')) {
                            document.getElementById('cardForm').reset();
                        }
                        cardModal.style.display = 'block';
                    } else {
                        console.error('Card modal not found');
                    }
                }
            } else {
                // Error fetching cards, fallback to card modal
                console.error('Failed to fetch cards:', data.message);
                if (seatModal) seatModal.style.display = 'none';
                if (cardModal) {
                    // Reset and prepare the card form
                    if (document.getElementById('cardModalTitle')) {
                        document.getElementById('cardModalTitle').textContent = 'Add Payment Card';
                    }
                    if (document.getElementById('cardForm')) {
                        document.getElementById('cardForm').reset();
                    }
                    cardModal.style.display = 'block';
                } else {
                    console.error('Card modal not found');
                }
            }
        })
        .catch(error => {
            console.error('Error fetching payment cards:', error);
            // Error fetching cards, fallback to card modal
            if (seatModal) seatModal.style.display = 'none';
            if (cardModal) {
                // Reset and prepare the card form
                if (document.getElementById('cardModalTitle')) {
                    document.getElementById('cardModalTitle').textContent = 'Add Payment Card';
                }
                if (document.getElementById('cardForm')) {
                    document.getElementById('cardForm').reset();
                }
                cardModal.style.display = 'block';
            } else {
                console.error('Card modal not found');
            }
        });
}

// Show card selection modal
function showCardSelectionModal(cards) {
    // Populate the modal with cards
    const cardList = document.getElementById('saved-cards-list');
    const seatModal = document.getElementById('seatModal');
    const cardSelectionModal = document.getElementById('cardSelectionModal');
    
    if (!cardList) {
        console.error('Element #saved-cards-list not found');
        return;
    }
    
    cardList.innerHTML = '';
    
    cards.forEach(card => {
        // Determine card type icon based on card_type field
        let cardTypeClass = 'unknown';
        if (card.card_type) {
            const type = card.card_type.toLowerCase();
            if (type.includes('visa')) {
                cardTypeClass = 'visa';
            } else if (type.includes('master')) {
                cardTypeClass = 'mastercard';
            } else if (type.includes('amex') || type.includes('american')) {
                cardTypeClass = 'amex';
            }
        }
        
        const cardElement = document.createElement('div');
        cardElement.className = 'saved-card-item';
        cardElement.innerHTML = `
            <div class="card-info">
                <div class="card-type ${cardTypeClass}"></div>
                <div class="card-details">
                    <p class="card-number">${card.card_number}</p>
                    <p class="card-holder">${card.card_holder || 'Card Holder'}</p>
                    <p class="card-expiry">Expires: ${card.expiry_date}</p>
                </div>
            </div>
            <button class="use-card-btn" data-card-id="${card.id}">Use This Card</button>
        `;
        
        cardList.appendChild(cardElement);
    });
    
    // Show the card selection modal
    if (seatModal) seatModal.style.display = 'none';
    if (cardSelectionModal) cardSelectionModal.style.display = 'block';
}

// Show login prompt
function showLoginPrompt() {
    const loginPromptModal = document.getElementById('loginPromptModal');
    const seatModal = document.getElementById('seatModal');
    
    // Check if the login prompt modal exists before trying to show it
    if (!loginPromptModal) {
        console.error('Login prompt modal element not found');
        return;
    }
    
    // Show the login prompt modal
    if (seatModal) seatModal.style.display = 'none';
    loginPromptModal.style.display = 'block';
}

// Expose functions to global scope
window.checkForSavedCards = checkForSavedCards;
window.showCardSelectionModal = showCardSelectionModal;
window.showLoginPrompt = showLoginPrompt; 