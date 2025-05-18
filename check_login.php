<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set headers for AJAX response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Return login status as JSON
echo json_encode(['loggedIn' => $isLoggedIn]);
?>