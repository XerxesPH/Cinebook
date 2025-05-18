<?php
// Main Navbar component
// This file requires user data from the parent page:
// - $isLoggedIn: boolean indicating if user is logged in
// - $userProfile: array with user data if logged in
?>
<!-- Main Navbar -->
<nav class="main-navbar">
    <!-- Desktop layout - standard structure -->
    <div class="profile-section">
        <button class="profile-btn" id="profileBtn">
            <?php if ($isLoggedIn): ?>
                <img src="<?php echo $userProfile['avatar']; ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user profile-icon"></i>
            <?php endif; ?>
        </button>
    </div>
    
    <div class="logo">
        <a href="index.php">
            <h1>CineBook</h1>
        </a>
    </div>
    
    <div class="nav-links desktop-nav-links">
        <a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Home</a>
        <a href="about.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'class="active"' : ''; ?>>About</a>
        <div class="dropdown">
            <button class="dropbtn">Movies <i class="fas fa-chevron-down"></i></button>
            <div class="dropdown-content">
                <a href="movies.php?category=now-showing">Now Showing</a>
                <a href="movies.php?category=coming-soon">Coming Soon</a>
            </div>
        </div>
    </div>
    
    <!-- Mobile/Tablet layout - only the additional elements needed -->
    <!-- Keep the navbar-left div for profile button -->
    <div class="navbar-left">
        <button class="profile-btn" id="profileBtnMobile">
            <?php if ($isLoggedIn): ?>
                <img src="<?php echo $userProfile['avatar']; ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user profile-icon"></i>
            <?php endif; ?>
        </button>
    </div>
    
    <!-- Center navbar with logo -->
    <div class="navbar-center">
        <a href="index.php" class="mobile-logo">
            <h1>CineBook</h1>
        </a>
    </div>
    
    <!-- Just keep navbar-right for the hamburger menu -->
    <div class="navbar-right">
        <div class="menu-toggle" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>

<!-- Mobile menu content - as slide-in panel similar to profile menu -->
<div class="nav-links mobile-nav-links" id="mobileNavLinks">
    <div class="mobile-menu-close">
        <i class="fas fa-times"></i>
    </div>
    <a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Home</a>
    <a href="about.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'class="active"' : ''; ?>>About</a>
    <div class="dropdown">
        <button class="dropbtn">Movies <i class="fas fa-chevron-down"></i></button>
        <div class="dropdown-content">
            <a href="movies.php?category=now-showing">Now Showing</a>
            <a href="movies.php?category=coming-soon">Coming Soon</a>
        </div>
    </div>
</div>

<!-- Mobile menu overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>


<!-- Swipe areas for mobile touch gestures -->
<div class="swipe-area-left"></div>
<div class="swipe-area-right"></div>