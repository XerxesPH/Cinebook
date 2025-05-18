<?php
require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to delete payment cards']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Check if card ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Invalid card ID';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$cardId = (int)$_GET['id'];

// Delete the card
$query = "DELETE FROM payment_methods WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $cardId, $userId);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_affected_rows($conn) > 0) {
        $response['success'] = true;
        $response['message'] = 'Card deleted successfully';
    } else {
        $response['message'] = 'Card not found or you do not have permission to delete it';
    }
} else {
    $response['message'] = 'Database error: ' . mysqli_error($conn);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>