<?php
// Side Menu component
// This file requires user data from the parent page:
// - $isLoggedIn: boolean indicating if user is logged in
// - $userProfile: array with user data if logged in
?>
<div class="side-menu" id="sideMenu">
    <?php if ($isLoggedIn): ?>
        <div class="menu-header">
            <div class="menu-profile-info">
                <img src="<?php echo $userProfile['avatar']; ?>" alt="Profile" class="menu-profile-pic">
                <h3 class="menu-user-name"><?php echo $userProfile['name']; ?></h3>
                <p class="menu-user-email"><?php echo $userProfile['email']; ?></p>
            </div>
        </div>
        <div class="menu-items">
            <div class="menu-item" data-modal="profile-modal">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </div>
            <div class="menu-item" data-modal="reservations-modal">
                <i class="fas fa-ticket-alt"></i>
                <span>My Reservations</span>
            </div>
            <a href="logout.php" class="menu-item" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    <?php else: ?>
        <div class="menu-header">
            <div class="menu-profile-info">
                <i class="fas fa-user-circle" style="font-size: 80px; color: #666;"></i>
                <h3 class="menu-user-name" style="margin-top: 1rem;">Guest User</h3>
            </div>
        </div>
        <div class="guest-options">
            <button class="menu-login-btn" id="menuLoginBtn">Login</button>
            <button class="menu-register-btn" id="menuRegisterBtn">Register</button>
        </div>
    <?php endif; ?>
</div>

<!-- Overlay that closes the menu when clicked -->
<div class="menu-overlay" id="menuOverlay"></div>

<script>
// Initialize side menu to ensure it starts closed
document.addEventListener('DOMContentLoaded', function() {
    const sideMenu = document.getElementById('sideMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    
    if (sideMenu) {
        // Force side menu to be closed on page load
        sideMenu.classList.remove('open');
        console.log("Side menu initialized:", sideMenu);
    }
    
    if (menuOverlay) {
        menuOverlay.classList.remove('active');
    }
});
</script>