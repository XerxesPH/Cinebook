<?php
// header.php

// Auth.php handles sessions and authentication
require_once __DIR__ . '/../auth.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userProfile = null;
$paymentCards = [];

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    
    // Fetch user profile data
    $userQuery = "SELECT id, name, email, contact_number, avatar, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $userQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $userResult = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($userResult) > 0) {
        $userProfile = mysqli_fetch_assoc($userResult);
    }
    
    // Fetch payment methods
    $cardsQuery = "SELECT id, card_type, card_number, card_holder, expiry_date FROM payment_methods WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $cardsQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $cardsResult = mysqli_stmt_get_result($stmt);
    
    while ($card = mysqli_fetch_assoc($cardsResult)) {
        // Only show last 4 digits of card number for security
        $card['card_number'] = '**** **** **** ' . substr($card['card_number'], -4);
        $paymentCards[] = $card;
    }
}

// Check if the user is an admin
$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'CineBook'; ?></title>
    <!-- Base styles -->
    <link rel="stylesheet" href="styles.css">
    <!-- Navigation and menu styles -->
    <link rel="stylesheet" href="css/navigation.css">
    <link rel="stylesheet" href="css/side-menu.css">
    <link rel="stylesheet" href="css/profile_modal.css">
    <!-- Responsive styles -->
    <link rel="stylesheet" href="css/tablet.css">
    <link rel="stylesheet" href="css/mobile.css">
    <!-- Font Awesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if (isset($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php include 'components/contact_navbar.php'; ?>
    <?php include 'components/main_navbar.php'; ?>
    <?php include 'components/side_menu.php'; ?>
    
    <?php if ($isLoggedIn && $userProfile): ?>
        <?php include 'modals/profile_modal.php'; ?>
    <?php endif; ?>

    <script>
    // Make PHP session login status available to JavaScript
    window.userLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    // Also add the logged-in class to body if user is logged in
    document.addEventListener('DOMContentLoaded', function() {
        if (window.userLoggedIn) {
            document.body.classList.add('logged-in');
        }
    });
    </script>
    
    <!-- Navigation JavaScript -->
    <script src="js/navigation.js"></script>
</body>
</html>