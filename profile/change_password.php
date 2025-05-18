<?php
// change_password.php - Handles password changes

require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to change your password']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $response['message'] = 'All password fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $response['message'] = 'New password and confirm password do not match';
    } elseif (strlen($newPassword) < 8) {
        $response['message'] = 'Password must be at least 8 characters long';
    } else {
        // Get current user password hash
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $storedHash = $row['password'];
            
            // Verify current password
            if (password_verify($currentPassword, $storedHash)) {
                // Hash new password
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password in database
                $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($stmt, "si", $newPasswordHash, $userId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Password changed successfully';
                } else {
                    $response['message'] = 'Database error: ' . mysqli_error($conn);
                }
            } else {
                $response['message'] = 'Current password is incorrect';
            }
        } else {
            $response['message'] = 'User not found';
        }
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;