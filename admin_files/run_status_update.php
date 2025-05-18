<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in, redirect to login if not
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth.php');
    exit;
}

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Include status checker utility
require_once __DIR__ . '/../includes/status_checker.php';

// Process form submission for manual update
$updateMessage = '';
$updateCount = 0;

if (isset($_POST['update_status'])) {
    // Run status update
    $updateCount = checkAndUpdateReservationStatuses($conn, true);
    $updateMessage = "Status update completed successfully. Updated $updateCount reservations to 'ended' status.";
}

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Status Update</title>
    <link rel="stylesheet" href="../admin-styles/admin-styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #1a1a1a;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #ff0000;
            margin-bottom: 20px;
        }
        .message {
            background-color: #222;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }
        .btn {
            background-color: #ff0000;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #cc0000;
        }
        .back-btn {
            background-color: #333;
        }
        .back-btn:hover {
            background-color: #444;
        }
        .info-box {
            background-color: #333;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #ccc;
        }
        form {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reservation Status Update</h1>
        
        <div class="info-box">
            <p>This tool checks for reservations with past showtimes and updates their status to "ended".</p>
            <p>The system automatically performs this check when users view reservations, but you can also manually run it here.</p>
        </div>
        
        <?php if (!empty($updateMessage)): ?>
        <div class="message">
            <?php echo $updateMessage; ?>
        </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <input type="submit" name="update_status" class="btn" value="Update Reservation Statuses">
            <a href="../admin-dashboard.php" class="btn back-btn">Back to Dashboard</a>
        </form>
    </div>
</body>
</html> 