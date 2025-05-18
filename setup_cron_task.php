<?php
/**
 * Reservation Status Management
 * 
 * This file explains how reservation statuses are automatically updated to "ended"
 * after movie show times have ended without requiring cron jobs or scheduled tasks.
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Status Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #e50914;
        }
        code {
            background-color: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: Consolas, monospace;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .info {
            background-color: #e0f7fa;
            border-left: 4px solid #00acc1;
            padding: 10px 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<h1>Automatic Reservation Status Management</h1>

<p>
    The Cinema Reservation system automatically updates reservation statuses to "ended" after a movie's showtime 
    has passed. Unlike traditional approaches that require cron jobs or scheduled tasks, our system uses a smart 
    on-demand approach.
</p>

<div class="info">
    <strong>How it works:</strong> Each time a user or admin interacts with reservations, the system automatically 
    checks for any reservations with ended showtimes and updates their status.
</div>

<h2>Key Integration Points</h2>

<p>The status checker is integrated at the following key points:</p>

<ol>
    <li><strong>Admin Dashboard</strong> - When admins load the dashboard, all reservation statuses are checked and updated</li>
    <li><strong>User Reservation Views</strong> - When users view their reservations, the system checks for ended showings</li>
    <li><strong>Reservation API Endpoints</strong> - API calls that retrieve reservation information also trigger status updates</li>
</ol>

<h2>Benefits of On-Demand Approach</h2>

<ul>
    <li>No need to set up and maintain cron jobs or scheduled tasks</li>
    <li>Works seamlessly on any hosting environment</li>
    <li>Updates happen in real-time as users interact with the system</li>
    <li>Reduced server load compared to frequent scheduled checks</li>
    <li>Always up-to-date information for both users and administrators</li>
</ul>

<h2>Manual Update Option</h2>

<p>
    Administrators can still manually trigger a status update by visiting the "Update Statuses" page in the admin dashboard.
    This is useful for ensuring all statuses are current, especially after system maintenance or if the site has had low traffic.
</p>

<h3>How to use the manual update:</h3>
<ol>
    <li>Log in to the admin dashboard</li>
    <li>Click on "Update Statuses" in the sidebar menu</li>
    <li>Click the "Update Reservation Statuses" button</li>
</ol>

<h2>Technical Implementation</h2>

<p>
    The status update logic is centralized in the <code>includes/status_checker.php</code> file, which provides a reusable 
    function that can be called from any page in the system. This ensures consistent behavior and simplifies maintenance.
</p>

<pre><code>// Example of how the status checker is used:
require_once 'includes/status_checker.php';
checkAndUpdateReservationStatuses($conn);
</code></pre>

<p>
    The system updates both the main reservation status and any associated reservation details, ensuring data consistency
    throughout the application.
</p>

</body>
</html> 