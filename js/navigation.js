//nav and side menu script

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const profileBtn = document.getElementById('profileBtn');
    const profileBtnMobile = document.getElementById('profileBtnMobile');
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.querySelector('.mobile-nav-links');
    const sideMenu = document.getElementById('sideMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const dropdowns = document.querySelectorAll('.dropdown');
    const mobileDropdowns = document.querySelectorAll('.mobile-nav-links .dropdown');
    const swipeAreaLeft = document.querySelector('.swipe-area-left');
    const swipeAreaRight = document.querySelector('.swipe-area-right');
    const menuLoginBtn = document.getElementById('menuLoginBtn');
    const menuRegisterBtn = document.getElementById('menuRegisterBtn');
    const loginModal = document.getElementById('loginModal');
    const mobileMenuClose = document.querySelector('.mobile-menu-close');
    
    // Contact navbar scroll behavior
    const contactNavbar = document.querySelector('.contact-navbar');
    const mainNavbar = document.querySelector('.main-navbar');
    let lastScrollTop = 0;
    
    // Touch variables for swipe detection
    let touchStartX = 0;
    let touchEndX = 0;
    const minSwipeDistance = 70; // Minimum distance required for a swipe
    
    // Debug the profile menu elements
    console.log("Profile button (desktop):", profileBtn);
    console.log("Profile button (mobile):", profileBtnMobile);
    console.log("Side menu:", sideMenu);
    console.log("Menu toggle:", menuToggle);
    console.log("Mobile nav links:", navLinks);
    console.log("Mobile menu overlay:", mobileMenuOverlay);
    
    // Initialize - make sure side menu is properly set up
    if (sideMenu) {
        // Ensure side menu starts closed
        sideMenu.classList.remove('open');
        if (menuOverlay) menuOverlay.classList.remove('active');
    }
    
    // Toggle side menu with desktop profile button
    if (profileBtn) {
        profileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log("Desktop profile button clicked");
            openSideMenu(); // Always open, not toggle
        });
    }
    
    // Toggle side menu with mobile profile button
    if (profileBtnMobile) {
        profileBtnMobile.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log("Mobile profile button clicked");
            openSideMenu(); // Always open, not toggle
        });
    }
    
    // Open mobile menu (similar to profile menu)
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log("Menu toggle clicked");
            openMobileMenu();
        });
    }
    
    // Close mobile menu with close button
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', function() {
            closeMobileMenu();
        });
    }
    
    // Close mobile menu when overlay is clicked
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', function() {
            closeMobileMenu();
        });
    }
    
    // Close side menu when overlay is clicked
    if (menuOverlay) {
        menuOverlay.addEventListener('click', function() {
            closeSideMenu();
        });
    }
    
    // Handle clicks on menu items with data-modal attributes
    document.addEventListener('click', function(e) {
        // Find if the click is on a menu-item or one of its children
        let target = e.target;
        let menuItem = null;
        
        // Traverse up to find if the clicked element is inside a menu-item
        while (target && target !== document) {
            if (target.classList && target.classList.contains('menu-item')) {
                menuItem = target;
                break;
            }
            target = target.parentNode;
        }
        
        // If we found a menu-item with a data-modal attribute
        if (menuItem && menuItem.hasAttribute('data-modal')) {
            e.preventDefault();
            console.log("Menu item with data-modal clicked:", menuItem);
            
            // Get the modal ID from the data-modal attribute
            const modalId = menuItem.getAttribute('data-modal');
            console.log("Modal ID:", modalId);
            
            // Handle different modal types 
            if (modalId === 'profile-modal') {
                // Profile modal
                const profileModal = document.getElementById('profileModal');
                if (profileModal) {
                    // Close the side menu
                    closeSideMenu();
                    
                    // Show the profile modal
                    profileModal.style.display = 'block';
                    console.log("Profile modal opened");
                } else {
                    console.error("Profile modal element not found");
                }
            }
            else if (modalId === 'reservations-modal') {
                // Reservations modal
                const reservationsModal = document.getElementById('reservations-modal');
                if (reservationsModal) {
                    // Close the side menu
                    closeSideMenu();
                    
                    // Show the reservations modal
                    reservationsModal.style.display = 'block';
                    console.log("Reservations modal opened");
                    
                    // Call loadReservations function if it exists
                    if (typeof loadReservations === 'function') {
                        loadReservations();
                    }
                } else {
                    console.error("Reservations modal element not found");
                }
            }
        }
    });
    
    // Handle mobile dropdown clicks specifically for mobile menu
    mobileDropdowns.forEach(dropdown => {
        const dropbtn = dropdown.querySelector('.dropbtn');
        if (dropbtn) {
            // Store the dropdown state
            dropdown.isOpen = false;
            
            dropbtn.addEventListener('click', function(e) {
                // Only for mobile/tablet view
                if (window.innerWidth <= 1023) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Mobile dropdown clicked");
                    
                    // Toggle dropdown state
                    dropdown.isOpen = !dropdown.isOpen;
                    
                    // Toggle active class on dropdown
                    if (dropdown.isOpen) {
                        dropdown.classList.add('active');
                    } else {
                        dropdown.classList.remove('active');
                    }
                    
                    // Explicitly handle dropdown content visibility
                    const dropdownContent = dropdown.querySelector('.dropdown-content');
                    if (dropdownContent) {
                        if (dropdown.isOpen) {
                            // Open the dropdown
                            dropdownContent.style.display = 'block';
                            dropdownContent.style.opacity = '1';
                            dropdownContent.style.visibility = 'visible';
                            dropdownContent.style.transform = 'translateX(0)';
                            // Use setTimeout to ensure the maxHeight animation works
                            setTimeout(() => {
                                dropdownContent.style.maxHeight = dropdownContent.scrollHeight + 'px';
                            }, 10);
                        } else {
                            // Close the dropdown
                            dropdownContent.style.maxHeight = '0';
                            dropdownContent.style.transform = 'translateX(-20px)';
                            // Use setTimeout to ensure the transition completes before hiding
                            setTimeout(() => {
                                if (!dropdown.isOpen) {
                                    dropdownContent.style.opacity = '0';
                                    dropdownContent.style.visibility = 'hidden';
                                }
                            }, 500);
                        }
                    }
                    
                    // Close other dropdowns
                    mobileDropdowns.forEach(otherDropdown => {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.isOpen = false;
                            otherDropdown.classList.remove('active');
                            const otherContent = otherDropdown.querySelector('.dropdown-content');
                            if (otherContent) {
                                otherContent.style.maxHeight = '0';
                                otherContent.style.transform = 'translateX(-20px)';
                                // Use setTimeout to ensure the transition completes before hiding
                                setTimeout(() => {
                                    if (!otherDropdown.isOpen) {
                                        otherContent.style.opacity = '0';
                                        otherContent.style.visibility = 'hidden';
                                    }
                                }, 500);
                            }
                        }
                    });
                }
            });
            
            // Prevent clicks on dropdown content from closing the dropdown
            const dropdownContent = dropdown.querySelector('.dropdown-content');
            if (dropdownContent) {
                dropdownContent.addEventListener('click', function(e) {
                    // Only prevent if it's a link within the dropdown
                    if (e.target.tagName === 'A') {
                        e.stopPropagation(); // Don't bubble up to document click handler
                    }
                });
            }
        }
    });
    
    // Close mobile menu when clicking anywhere outside, but don't close dropdowns
    document.addEventListener('click', function(e) {
        if (navLinks && navLinks.classList.contains('active')) {
            // Check if click is outside the mobile menu
            if (!navLinks.contains(e.target) && e.target !== menuToggle && !menuToggle.contains(e.target)) {
                closeMobileMenu();
            }
        }
    });
    
    // Swipe detection for left side menu (only on mobile/tablet)
    if (swipeAreaLeft) {
        swipeAreaLeft.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        swipeAreaLeft.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleLeftSwipe();
        }, { passive: true });
    }
    
    // Swipe detection for right menu (only on mobile/tablet)
    if (swipeAreaRight) {
        swipeAreaRight.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        swipeAreaRight.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleRightSwipe();
        }, { passive: true });
    }
    
    // Full body swipe detection for mobile/tablet
    document.addEventListener('touchstart', function(e) {
        // Only handle on mobile/tablet view
        if (window.innerWidth <= 1023) {
            touchStartX = e.changedTouches[0].screenX;
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        // Only handle on mobile/tablet view
        if (window.innerWidth <= 1023) {
            touchEndX = e.changedTouches[0].screenX;
            const swipeDistance = Math.abs(touchEndX - touchStartX);
            
            // Only process if it's a significant swipe
            if (swipeDistance > minSwipeDistance) {
                // Detect if side menu is open
                const isSideMenuOpen = sideMenu && sideMenu.classList.contains('open');
                
                // Detect if mobile menu is open
                const isMobileMenuOpen = navLinks && navLinks.classList.contains('active');
                
                // Right to left swipe
                if (touchStartX > touchEndX) {
                    console.log("Right to left swipe");
                    // If side menu is open, close it
                    if (isSideMenuOpen) {
                        closeSideMenu();
                    } 
                    // If mobile menu is not open and we're near the right edge, open it
                    else if (!isMobileMenuOpen && touchStartX > window.innerWidth - 50) {
                        openMobileMenu();
                    }
                }
                // Left to right swipe
                else if (touchEndX > touchStartX) {
                    console.log("Left to right swipe");
                    // If mobile menu is open, close it
                    if (isMobileMenuOpen) {
                        closeMobileMenu();
                    }
                    // If side menu is not open and we're near the left edge, open it
                    else if (!isSideMenuOpen && touchStartX < 50) {
                        openSideMenu();
                    }
                }
            }
        }
    }, { passive: true });
    
    // Scroll behavior for contact navbar
    window.addEventListener('scroll', function() {
        if (!contactNavbar || !mainNavbar) return;
        
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 50) {
            // Scrolling down
            contactNavbar.classList.add('hidden');
            mainNavbar.style.top = '0';
        } else {
            // Scrolling up
            contactNavbar.classList.remove('hidden');
            mainNavbar.style.top = '40px';
        }
        
        lastScrollTop = scrollTop;
    }, { passive: true });
    
    // If menu login and register buttons exist (user not logged in)
    if (menuLoginBtn && loginModal) {
        menuLoginBtn.addEventListener('click', function() {
            closeSideMenu(); // Close the menu
            loginModal.style.display = 'block'; // Open login modal
            
            // Select the login tab
            const loginTab = document.querySelector('.tab-btn[data-tab="login"]');
            if (loginTab) loginTab.click();
        });
    }
    
    if (menuRegisterBtn && loginModal) {
        menuRegisterBtn.addEventListener('click', function() {
            closeSideMenu(); // Close the menu
            loginModal.style.display = 'block'; // Open login modal
            
            // Select the register tab
            const registerTab = document.querySelector('.tab-btn[data-tab="register"]');
            if (registerTab) registerTab.click();
        });
    }
    
    // Window resize handler
    window.addEventListener('resize', function() {
        // Handle responsive changes when screen size crosses breakpoints
        if (window.innerWidth > 1023) {
            // Reset mobile menu state when switching to desktop
            closeMobileMenu();
            
            // Reset dropdown states
            mobileDropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Functions
    function openSideMenu() {
        console.log("Open side menu called");
        if (sideMenu) {
            console.log("Side menu before open:", sideMenu.classList.contains('open'));
            sideMenu.classList.add('open');
            console.log("Side menu after open:", sideMenu.classList.contains('open'));
            
            if (menuOverlay) {
                menuOverlay.classList.add('active');
            }
            document.body.classList.add('menu-open');
        } else {
            console.error("Side menu element not found");
        }
    }
    
    function toggleSideMenu() {
        console.log("Toggle side menu called");
        if (sideMenu) {
            console.log("Side menu before toggle:", sideMenu.classList.contains('open'));
            sideMenu.classList.toggle('open');
            console.log("Side menu after toggle:", sideMenu.classList.contains('open'));
            
            if (menuOverlay) {
                menuOverlay.classList.toggle('active');
            }
            document.body.classList.toggle('menu-open');
        } else {
            console.error("Side menu element not found");
        }
    }
    
    function closeSideMenu() {
        if (sideMenu) {
            sideMenu.classList.remove('open');
            if (menuOverlay) menuOverlay.classList.remove('active');
            document.body.classList.remove('menu-open');
        }
    }
    
    function openMobileMenu() {
        if (navLinks) {
            navLinks.classList.add('active');
            if (menuToggle) menuToggle.classList.add('active');
            if (mobileMenuOverlay) mobileMenuOverlay.classList.add('active');
            document.body.classList.add('mobile-menu-open');
        }
    }
    
    function closeMobileMenu() {
        if (navLinks) {
            navLinks.classList.remove('active');
        }
        if (menuToggle) {
            menuToggle.classList.remove('active');
        }
        if (mobileMenuOverlay) {
            mobileMenuOverlay.classList.remove('active');
        }
        document.body.classList.remove('mobile-menu-open');
    }
    
    function handleLeftSwipe() {
        if (touchEndX - touchStartX > minSwipeDistance) {
            // Right swipe from left edge - open side menu
            const isSideMenuOpen = sideMenu && sideMenu.classList.contains('open');
            if (!isSideMenuOpen) {
                openSideMenu();
            }
        }
    }
    
    function handleRightSwipe() {
        if (touchStartX - touchEndX > minSwipeDistance) {
            // Left swipe from right edge - open mobile menu
            const isMobileMenuOpen = navLinks && navLinks.classList.contains('active');
            if (!isMobileMenuOpen) {
                openMobileMenu();
            }
        }
    }
});