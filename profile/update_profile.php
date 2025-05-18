<?php
// update_profile.php - Handles profile information updates

require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to update your profile']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $contactNumber = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : null;
    
    // Validate inputs
    if (empty($name)) {
        $response['message'] = 'Name is required';
    } elseif (empty($email)) {
        $response['message'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
    } else {
        // Check if email already exists (except for current user)
        $checkEmailQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($conn, $checkEmailQuery);
        mysqli_stmt_bind_param($stmt, "si", $email, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $response['message'] = 'Email is already in use by another account';
        } else {
            // Update user profile
            $updateQuery = "UPDATE users SET name = ?, email = ?, contact_number = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $contactNumber, $userId);
            
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Profile updated successfully';
            } else {
                $response['message'] = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;