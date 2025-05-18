<?php
require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to view payment cards']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => '', 'cards' => []];

// Get all payment cards for the user
$query = "SELECT id, card_type, card_number, card_holder, expiry_date FROM payment_methods WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch cards
while ($card = mysqli_fetch_assoc($result)) {
    // Only keep last 4 digits of card number for security
    $card['card_number'] = '**** **** **** ' . substr($card['card_number'], -4);
    $response['cards'][] = $card;
}

$response['success'] = true;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>