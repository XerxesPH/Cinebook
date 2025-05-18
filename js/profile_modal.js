document.addEventListener('DOMContentLoaded', function() {
    // Profile Modal Variables
    const profileModal = document.getElementById('profileModal');
    const cardModal = document.getElementById('cardModal');
    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const openProfileBtn = document.getElementById('profileButton');
    const closeProfileBtn = document.getElementById('closeProfileModal');
    const closeCardBtn = document.getElementById('closeCardModal');
    const profileTabs = document.querySelectorAll('.profile-tab-btn');
    const profileTabContents = document.querySelectorAll('.profile-tab-content');
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const avatarUpload = document.getElementById('avatarUpload');
    const profileUpdateForm = document.getElementById('profileUpdateForm');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const addNewCardBtn = document.getElementById('addNewCardBtn');
    const cardForm = document.getElementById('cardForm');
    const savedCardsList = document.getElementById('savedCardsList');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    // Card form elements
    const cardNumberInput = document.getElementById('cardNumber');
    const cardExpiryInput = document.getElementById('cardExpiry');
    const cardCVVInput = document.getElementById('cardCVV');
    
    // Variables to store card deletion info
    let cardToDeleteId = null;
    
    // Format card number with spaces
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Format expiry date as MM/YY
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            e.target.value = value;
        });
    }
    
    // Only allow numbers for CVV
    if (cardCVVInput) {
        cardCVVInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }
    
    // Open Profile Modal
    if (openProfileBtn) {
        openProfileBtn.addEventListener('click', function() {
            profileModal.style.display = 'block';
        });
    }
    
    // Close Profile Modal
    if (closeProfileBtn) {
        closeProfileBtn.addEventListener('click', function() {
            profileModal.style.display = 'none';
        });
    }
    
    // Close Card Modal
    if (closeCardBtn) {
        closeCardBtn.addEventListener('click', function() {
            cardModal.style.display = 'none';
        });
    }
    
    // Tab Navigation
    if (profileTabs.length > 0) {
        profileTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-profile-tab');
                
                // Remove active class from all tabs and contents
                profileTabs.forEach(t => t.classList.remove('active'));
                profileTabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to selected tab and content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    }
    
    // Avatar Upload
    if (changeAvatarBtn && avatarUpload) {
        changeAvatarBtn.addEventListener('click', function() {
            avatarUpload.click();
        });
        
        avatarUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('avatar', this.files[0]);
                
                fetch('profile/update_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Add response validation
                    if (!response.ok) {
                        throw new Error('Server responded with status: ' + response.status);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('profileAvatar').src = data.avatar_url + '?v=' + new Date().getTime();
                    } else {
                        alert('Failed to update avatar: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating your avatar.');
                });
            }
        });
    }
    
    // Profile Update Form
    if (profileUpdateForm) {
        profileUpdateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('profile/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Add response validation
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                } else {
                    alert('Failed to update profile: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating your profile.');
            });
        });
    }
    
    // Change Password
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields.');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New password and confirm password do not match.');
                return;
            }
            
            const formData = new FormData();
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirmPassword);
            
            fetch('profile/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Add response validation
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully!');
                    document.getElementById('currentPassword').value = '';
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmPassword').value = '';
                } else {
                    alert('Failed to change password: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while changing your password.');
            });
        });
    }
    
    // Add New Card Button
    if (addNewCardBtn) {
        addNewCardBtn.addEventListener('click', function() {
            document.getElementById('cardModalTitle').textContent = 'Add New Card';
            document.getElementById('cardId').value = '';
            cardForm.reset();
            cardModal.style.display = 'block';
        });
    }
    
    // Save Card Form
    if (cardForm) {
        cardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('payment/update_payment_cards.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Add response validation
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    cardModal.style.display = 'none';
                    // Refresh the cards list
                    loadPaymentCards();
                } else {
                    alert('Failed to save card: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving your card.');
            });
        });
    }
    
    // Function to load payment cards
    function loadPaymentCards() {
        fetch('payment/get_payment_cards.php')
            .then(response => {
                // Add response validation
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    renderCardsList(data.cards);
                } else {
                    console.error('Failed to fetch cards:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Function to render cards list
    function renderCardsList(cards) {
        if (!savedCardsList) return;
        
        savedCardsList.innerHTML = '';
        
        if (cards.length === 0) {
            savedCardsList.innerHTML = '<p class="no-cards-message">You haven\'t added any payment cards yet.</p>';
            return;
        }
        
        cards.forEach(card => {
            const cardElement = document.createElement('div');
            cardElement.className = 'card-item';
            
            cardElement.innerHTML = `
                <div class="card-info">
                    <div class="card-type ${card.card_type.toLowerCase()}"></div>
                    <div class="card-details">
                        <p class="card-number">${card.card_number}</p>
                        <p class="card-expiry">Expires: ${card.expiry_date}</p>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="edit-card-btn" data-card-id="${card.id}">Edit</button>
                    <button class="delete-card-btn" data-card-id="${card.id}">Remove</button>
                </div>
            `;
            
            savedCardsList.appendChild(cardElement);
        });
        
        // Add event listeners to edit buttons
        document.querySelectorAll('.edit-card-btn').forEach(button => {
            button.addEventListener('click', function() {
                const cardId = this.getAttribute('data-card-id');
                editCard(cardId);
            });
        });
        
        // Add event listeners to delete buttons
        document.querySelectorAll('.delete-card-btn').forEach(button => {
            button.addEventListener('click', function() {
                const cardId = this.getAttribute('data-card-id');
                confirmDeleteCard(cardId);
            });
        });
    }
    
    // Function to edit a card
    function editCard(cardId) {
        // Fixed: Use the correct path to get_card_details.php
        fetch(`payment/get_card_details.php?id=${cardId}`)
            .then(response => {
                // Add response validation
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const card = data.card;
                    document.getElementById('cardModalTitle').textContent = 'Edit Card';
                    document.getElementById('cardId').value = card.id;
                    document.getElementById('cardNumber').value = card.card_number;
                    document.getElementById('cardExpiry').value = card.expiry_date;
                    document.getElementById('cardName').value = card.card_holder;
                    cardModal.style.display = 'block';
                } else {
                    alert('Failed to get card details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching card details.');
            });
    }
    
    // Function to confirm card deletion
    function confirmDeleteCard(cardId) {
        cardToDeleteId = cardId;
        confirmDeleteModal.style.display = 'block';
    }
    
    // Confirm Delete Button
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (cardToDeleteId) {
                deleteCard(cardToDeleteId);
            }
            confirmDeleteModal.style.display = 'none';
        });
    }
    
    // Cancel Delete Button
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            confirmDeleteModal.style.display = 'none';
        });
    }
    
    // Function to delete a card
    function deleteCard(cardId) {
        fetch(`payment/delete_payment_card.php?id=${cardId}`)
            .then(response => {
                // Add response validation
                if (!response.ok) {
                    throw new Error('Server responded with status: ' + response.status);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    loadPaymentCards();
                } else {
                    alert('Failed to delete card: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting your card.');
            });
    }
    
    // Load cards when the page loads
    if (savedCardsList) {
        loadPaymentCards();
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === profileModal) {
            profileModal.style.display = 'none';
        }
        if (event.target === cardModal) {
            cardModal.style.display = 'none';
        }
        if (event.target === confirmDeleteModal) {
            confirmDeleteModal.style.display = 'none';
        }
    });
});