<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'includes/db.php';

// Define variables for error/success messages
$loginError = "";
$registerError = "";
$registerSuccess = "";

// Process login form submission
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['login-email']);
    $password = $_POST['login-password'];
    
    // Check for booking redirect
    $redirectAfterLogin = isset($_POST['redirect_after_login']) ? $_POST['redirect_after_login'] : '';
    $selectedSeats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : '';
    
    // Get booking data from post parameters
    $bookingData = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'booking_') === 0) {
            $bookingData[substr($key, 8)] = $value; // Remove 'booking_' prefix
        }
    }
    
    // Regular user login query
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_image'] = $user['avatar'];
            
            // Set a flag to indicate user is logged in for JavaScript
            $_SESSION['user_logged_in'] = true;
            
            // Check if user is admin and set admin session
            if ($user['is_admin'] == 1) {
                $_SESSION['admin_id'] = $user['id'];
                // Redirect admin to admin dashboard
                header("Location: admin-dashboard.php");
                exit();
            } else {
                // FIXED: Check if we need to redirect back to booking
                if ($redirectAfterLogin === 'booking' && !empty($selectedSeats)) {
                    // Store the booking data in session
                    $_SESSION['booking_data'] = $bookingData;
                    $_SESSION['selected_seats'] = $selectedSeats;
                    
                    // Redirect to the page with a flag indicating successful login for booking
                    header("Location: index.php?login_success=true#booking");
                    exit();
                } else {
                    // Redirect regular user to home page
                    header("Location: index.php");
                    exit();
                }
            }
        } else {
            $loginError = "Invalid password";
        }
    } else {
        $loginError = "Email not found";
    }
}

// Process registration form submission
if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['register-name']);
    $email = mysqli_real_escape_string($conn, $_POST['register-email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['register-phone'] ?? '');
    $password = $_POST['register-password'];
    $confirmPassword = $_POST['register-confirm-password'];
    
    // Check for booking redirect
    $redirectAfterLogin = isset($_POST['redirect_after_login']) ? $_POST['redirect_after_login'] : '';
    $selectedSeats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : '';
    
    // Get booking data from post parameters
    $bookingData = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'booking_') === 0) {
            $bookingData[substr($key, 8)] = $value; // Remove 'booking_' prefix
        }
    }
    
    // Validate passwords match
    if ($password != $confirmPassword) {
        $registerError = "Passwords do not match";
    } else {
        // Check if email already exists
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $registerError = "Email already exists";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO users (name, email, contact_number, password) VALUES ('$name', '$email', '$contact_number', '$hashedPassword')";
            if (mysqli_query($conn, $query)) {
                // Auto-login after successful registration
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_contact_number'] = $contact_number;
                $_SESSION['user_image'] = ''; // Default empty avatar
                
                // Set a flag to indicate user is logged in for JavaScript
                $_SESSION['user_logged_in'] = true;
                
                // FIXED: Check if we need to redirect back to booking
                if ($redirectAfterLogin === 'booking' && !empty($selectedSeats)) {
                    // Store the booking data in session
                    $_SESSION['booking_data'] = $bookingData;
                    $_SESSION['selected_seats'] = $selectedSeats;
                    
                    // Redirect to the page with a flag indicating successful login for booking
                    header("Location: index.php?login_success=true#booking");
                    exit();
                } else {
                    $registerSuccess = "Registration successful! You are now logged in.";
                    header("Location: index.php");
                    exit();
                }
            } else {
                $registerError = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userProfile = $isLoggedIn ? [
    'name' => $_SESSION['user_name'], 
    'email' => $_SESSION['user_email'], 
    'image' => $_SESSION['user_image']
] : null;

// Set user_logged_in session variable for JavaScript to access
if ($isLoggedIn) {
    $_SESSION['user_logged_in'] = true;
} else {
    unset($_SESSION['user_logged_in']);
}

// Check if the user is an admin
$isAdmin = isset($_SESSION['admin_id']);
?>