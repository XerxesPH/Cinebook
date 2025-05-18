<?php
// update_avatar.php - Handles avatar image uploads

require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to update your avatar']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Check if the request is POST and has file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Upload error: ' . $file['error'];
    } else {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $response['message'] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/avatars/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFilename;
            
            // Move the uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Update user avatar in database
                $avatarPath = 'uploads/avatars/' . $newFilename;
                $updateQuery = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($stmt, "si", $avatarPath, $userId);
                
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Avatar updated successfully';
                    $response['avatar_url'] = $avatarPath;
                } else {
                    $response['message'] = 'Database error: ' . mysqli_error($conn);
                }
            } else {
                $response['message'] = 'Failed to move uploaded file';
            }
        }
    }
} else {
    $response['message'] = 'No avatar file uploaded';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;