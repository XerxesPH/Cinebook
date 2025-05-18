<?php
// Profile Modal Component
// This file should be included in the header.php where profile modal functionality is needed
// It uses the $userProfile array and $paymentCards array from the parent page
?>
<!-- Profile Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content profile-modal">
        <span class="close-btn" id="closeProfileModal">&times;</span>
        
        <div class="modal-tabs">
            <button class="profile-tab-btn active" data-profile-tab="userInfo">Profile Info</button>
            <button class="profile-tab-btn" data-profile-tab="paymentCards">Payment Cards</button>
        </div>
        
        <!-- Profile Info Tab -->
        <div id="userInfo" class="profile-tab-content active">
            <h2>My Profile</h2>
            
            <div class="profile-header">
                <div class="profile-avatar">
                    <img src="<?php echo htmlspecialchars($userProfile['avatar']); ?>" alt="Profile Picture" id="profileAvatar">
                    <button id="changeAvatarBtn" class="change-avatar-btn">Change</button>
                    <input type="file" id="avatarUpload" class="hidden" accept="image/*">
                </div>
                
                <div class="profile-name">
                    <h3><?php echo htmlspecialchars($userProfile['name']); ?></h3>
                    <p class="member-since">Member since <?php echo date('F Y', strtotime($userProfile['created_at'])); ?></p>
                </div>
            </div>
            
            <form id="profileUpdateForm" class="profile-form">
                <div class="form-group">
                    <label for="profileName">Full Name</label>
                    <input type="text" id="profileName" name="name" value="<?php echo htmlspecialchars($userProfile['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="profileEmail">Email Address</label>
                    <input type="email" id="profileEmail" name="email" value="<?php echo htmlspecialchars($userProfile['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="profilePhone">Contact Number</label>
                    <input type="tel" id="profilePhone" name="contact_number" value="<?php echo htmlspecialchars($userProfile['contact_number'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="update-profile-btn">Update Profile</button>
                
                <div class="password-section">
                    <h3>Change Password</h3>
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password">
                    </div>
                    
                    <button type="button" id="changePasswordBtn" class="change-password-btn">Change Password</button>
                </div>
            </form>
        </div>
        
        <!-- Payment Cards Tab -->
        <div id="paymentCards" class="profile-tab-content">
            <h2>My Payment Cards</h2>
            
            <div class="saved-cards">
                <div id="savedCardsList">
                    <?php if (empty($paymentCards)): ?>
                        <p class="no-cards-message">You haven't added any payment cards yet.</p>
                    <?php else: ?>
                        <?php foreach ($paymentCards as $card): ?>
                            <div class="card-item">
                                <div class="card-info">
                                    <div class="card-type <?php echo strtolower($card['card_type']); ?>"></div>
                                    <div class="card-details">
                                        <p class="card-number"><?php echo htmlspecialchars($card['card_number']); ?></p>
                                        <p class="card-expiry">Expires: <?php echo htmlspecialchars($card['expiry_date']); ?></p>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <button class="edit-card-btn" data-card-id="<?php echo $card['id']; ?>">Edit</button>
                                    <button class="delete-card-btn" data-card-id="<?php echo $card['id']; ?>">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button id="addNewCardBtn" class="add-card-btn">Add New Card</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Card Modal -->
<div id="cardModal" class="modal">
    <div class="modal-content card-modal">
        <span class="close-btn" id="closeCardModal">&times;</span>
        <h2 id="cardModalTitle">Add New Card</h2>
        
        <form id="cardForm" class="card-form">
            <input type="hidden" id="cardId" name="card_id" value="">
            
            <div class="form-group">
                <label for="cardNumber">Card Number</label>
                <input type="text" id="cardNumber" name="card_number" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" required>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="cardExpiry">Expiry Date</label>
                    <input type="text" id="cardExpiry" name="card_expiry" placeholder="MM/YY" maxlength="5" required>
                </div>
                
                <div class="form-group half">
                    <label for="cardCVV">CVV</label>
                    <input type="text" id="cardCVV" name="card_cvv" placeholder="XXX" maxlength="4" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="cardName">Cardholder Name</label>
                <input type="text" id="cardName" name="cardholder_name" placeholder="As it appears on your card" required>
            </div>
            
            <button type="submit" id="saveCardBtn" class="save-card-btn">Save Card</button>
        </form>
    </div>
</div>

<!-- Confirmation Modal for Delete -->
<div id="confirmDeleteModal" class="modal">
    <div class="modal-content confirm-modal">
        <h3>Delete Payment Card</h3>
        <p>Are you sure you want to remove this payment card?</p>
        <div class="confirm-actions">
            <button id="confirmDeleteBtn" class="confirm-btn">Yes, Remove</button>
            <button id="cancelDeleteBtn" class="cancel-btn">Cancel</button>
        </div>
    </div>
</div>

<!-- Include the profile modal JavaScript -->
<!-- Script moved to footer.php to prevent duplicate loading -->