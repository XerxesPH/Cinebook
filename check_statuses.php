<?php
// Start session for admin authentication
session_start();

// Check if admin is logged in, redirect to login if not
if (!isset($_SESSION['admin_id'])) {
    header('Location: auth.php');
    exit;
}

// Include database connection and status checker utility
require_once 'includes/db.php';
require_once 'includes/status_checker.php';

$message = '';
$updated = 0;

// Process manual update request
if (isset($_POST['update_statuses'])) {
    $updated = checkAndUpdateReservationStatuses($conn, true);
    $message = "Status update completed. Updated $updated reservations to 'ended' status.";
}

// Get reservations that need updating
$query = "
    SELECT r.id, r.reservation_code, r.status, r.schedule_id, 
           s.date as show_date, s.end_time, m.title as movie_title,
           CONCAT(s.date, ' ', s.end_time) as end_datetime,
           u.name as user_name
    FROM reservations r
    JOIN schedules s ON r.schedule_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN users u ON r.user_id = u.id
    WHERE r.status IN ('pending', 'verified')
    ORDER BY end_datetime ASC
";

$result = mysqli_query($conn, $query);
$reservations = [];
$needsUpdate = 0;
$currentTime = time();

while ($row = mysqli_fetch_assoc($result)) {
    $endTime = strtotime($row['end_datetime']);
    $row['past_end_time'] = $endTime < $currentTime;
    
    if ($row['past_end_time']) {
        $needsUpdate++;
    }
    
    $reservations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Reservation Statuses</title>
    <link rel="stylesheet" href="admin-styles/admin-styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: #eee;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .message {
            background-color: #222;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #222;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background-color: #222;
        }
        .needs-update {
            background-color: rgba(255, 0, 0, 0.1);
        }
        .action-buttons {
            margin: 20px 0;
        }
        .btn {
            background-color: #e50914;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #f40612;
        }
        .back-btn {
            background-color: #333;
        }
        .back-btn:hover {
            background-color: #444;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            text-align: center;
            color: white;
        }
        .status.pending {
            background-color: #ff9800;
        }
        .status.verified {
            background-color: #4caf50;
        }
        .status.cancelled {
            background-color: #f44336;
        }
        .status.expired {
            background-color: #9e9e9e;
        }
        .status.ended {
            background-color: #2196f3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Check Reservation Statuses</h1>
        
        <div class="action-buttons">
            <a href="admin-dashboard.php" class="btn back-btn">Back to Dashboard</a>
            
            <form method="post" style="display: inline-block; margin-left: 10px;">
                <button type="submit" name="update_statuses" class="btn">
                    Update <?php echo $needsUpdate; ?> Expired Reservations
                </button>
            </form>
        </div>
        
        <?php if ($message): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p>Found <?php echo count($reservations); ?> pending/verified reservations, <?php echo $needsUpdate; ?> need updating.</p>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>User</th>
                    <th>Movie</th>
                    <th>End Date/Time</th>
                    <th>Status</th>
                    <th>Needs Update</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reservations) > 0): ?>
                    <?php foreach ($reservations as $res): ?>
                        <tr class="<?php echo $res['past_end_time'] ? 'needs-update' : ''; ?>">
                            <td><?php echo $res['id']; ?></td>
                            <td><?php echo $res['reservation_code']; ?></td>
                            <td><?php echo $res['user_name']; ?></td>
                            <td><?php echo $res['movie_title']; ?></td>
                            <td><?php echo $res['end_datetime']; ?></td>
                            <td><span class="status <?php echo strtolower($res['status']); ?>"><?php echo ucfirst($res['status']); ?></span></td>
                            <td><?php echo $res['past_end_time'] ? 'YES' : 'NO'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No reservations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 