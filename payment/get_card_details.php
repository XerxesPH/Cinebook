<?php
require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to view card details']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => '', 'card' => null];

// Check if card ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Invalid card ID';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$cardId = (int)$_GET['id'];

// Get card details
$query = "SELECT id, card_type, card_number, card_holder, expiry_date FROM payment_methods WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $cardId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($card = mysqli_fetch_assoc($result)) {
    // For security, mask most of the card number when sending to the client
    // Only show last 4 digits
    $maskedNumber = '**** **** **** ' . substr($card['card_number'], -4);
    $card['card_number'] = $maskedNumber;
    
    $response['success'] = true;
    $response['card'] = $card;
} else {
    $response['message'] = 'Card not found or you do not have permission to view it';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>