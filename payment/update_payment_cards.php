<?php
require_once '../auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to update payment cards']);
    exit;
}

$userId = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $cardId = isset($_POST['card_id']) && !empty($_POST['card_id']) ? (int)$_POST['card_id'] : null;
    $cardNumber = isset($_POST['card_number']) ? preg_replace('/\s+/', '', $_POST['card_number']) : ''; // Remove spaces
    $cardExpiry = isset($_POST['card_expiry']) ? trim($_POST['card_expiry']) : '';
    $cardCVV = isset($_POST['card_cvv']) ? trim($_POST['card_cvv']) : '';
    $cardHolder = isset($_POST['cardholder_name']) ? trim($_POST['cardholder_name']) : '';
    
    // Determine card type based on first digit(s)
    $cardType = 'Visa'; // Default
    if (!empty($cardNumber)) {
        $firstDigit = substr($cardNumber, 0, 1);
        $firstTwoDigits = substr($cardNumber, 0, 2);
        
        if ($firstDigit === '4') {
            $cardType = 'Visa';
        } elseif ($firstDigit === '5') {
            $cardType = 'Mastercard';
        } elseif ($firstTwoDigits === '34' || $firstTwoDigits === '37') {
            $cardType = 'Amex';
        } elseif ($firstDigit === '6') {
            $cardType = 'Discover';
        }
    }
    
    // Validate inputs
    if (empty($cardNumber) || empty($cardExpiry) || empty($cardCVV) || empty($cardHolder)) {
        $response['message'] = 'All fields are required';
    } elseif (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
        $response['message'] = 'Invalid card number';
    } elseif (!preg_match('/^\d{1,2}\/\d{2}$/', $cardExpiry)) {
        $response['message'] = 'Invalid expiry date format (MM/YY)';
    } elseif (strlen($cardCVV) < 3 || strlen($cardCVV) > 4) {
        $response['message'] = 'Invalid CVV';
    } else {
        // For security, encrypt or hash sensitive data before storing
        // In production, you should use a proper encryption method
        $encryptedCVV = password_hash($cardCVV, PASSWORD_DEFAULT);
        
        if ($cardId) {
            // Update existing card
            $query = "UPDATE payment_methods SET 
                      card_type = ?, 
                      card_number = ?, 
                      card_holder = ?, 
                      expiry_date = ?, 
                      cvv = ? 
                      WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssii", $cardType, $cardNumber, $cardHolder, $cardExpiry, $encryptedCVV, $cardId, $userId);
        } else {
            // Insert new card
            $query = "INSERT INTO payment_methods (user_id, card_type, card_number, card_holder, expiry_date, cvv) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isssss", $userId, $cardType, $cardNumber, $cardHolder, $cardExpiry, $encryptedCVV);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = $cardId ? 'Card updated successfully' : 'Card added successfully';
        } else {
            $response['message'] = 'Database error: ' . mysqli_error($conn);
        }
    }
} else {
    $response['message'] = 'Invalid request method';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>