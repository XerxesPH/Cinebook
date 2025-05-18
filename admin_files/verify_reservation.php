<?php
// Include database connection
require_once __DIR__ . '/../includes/db.php';
// Include QR code verification function
require_once '../api/reservation_qr_code.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = "You must be logged in as admin to verify reservations.";
    header("Location: login.php");
    exit();
}

// Handle direct verification through URL parameters (for QR codes)
$verification_result = null;
$reservation = null;

// Check for direct verification via URL parameters
if (isset($_GET['verify']) && isset($_GET['code']) && isset($_GET['id'])) {
    $reservationCode = $_GET['code'];
    $reservationId = (int)$_GET['id'];
    $verifyToken = $_GET['verify'];
    
    // Simple security check - the token should match a hash of the reservation code and ID
    $expectedToken = substr(md5($reservationCode . $reservationId . 'cinebook_verify_salt'), 0, 16);
    
    if ($verifyToken === $expectedToken) {
        // Token is valid, verify the reservation
        $stmt = $conn->prepare("
            SELECT r.*, s.date, s.start_time, s.end_time, m.title as movie_title, 
            c.name as cinema_name, TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration
            FROM reservations r
            JOIN schedules s ON r.schedule_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN cinemas c ON s.cinema_id = c.id
            WHERE r.id = ? AND r.reservation_code = ?
        ");
        $stmt->bind_param("is", $reservationId, $reservationCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        
        if (!$reservation) {
            $verification_result = ['status' => false, 'message' => 'Reservation not found'];
        } else if ($reservation['status'] === 'verified') {
            $verification_result = ['status' => true, 'message' => 'Reservation is already verified'];
        } else if ($reservation['status'] === 'cancelled') {
            $verification_result = ['status' => false, 'message' => 'Reservation has been cancelled'];
        } else if ($reservation['status'] === 'expired') {
            $verification_result = ['status' => false, 'message' => 'Reservation has expired'];
        } else {
            // Use the checkVerificationTiming function for consistent timing checks
            $timingCheck = checkVerificationTiming($reservation);
            
            if ($timingCheck['status'] === false) {
                $verification_result = [
                    'status' => false, 
                    'message' => $timingCheck['message']
                ];
            } else {
                // Update status to verified only if timing check passes
                $updateStmt = $conn->prepare("UPDATE reservations SET status = 'verified' WHERE id = ?");
                $updateStmt->bind_param("i", $reservationId);
                $updateStmt->execute();
                
                $verification_result = [
                    'status' => true, 
                    'message' => $timingCheck['message']
                ];
            }
        }
    } else {
        $verification_result = ['status' => false, 'message' => 'Invalid verification token'];
    }
}

// Handle manual verification (by reservation code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$verification_result) {
    if (isset($_POST['verify_code'])) {
        // Manual verification by code
        $code = $_POST['reservation_code'] ?? '';
        
        if (empty($code)) {
            $verification_result = ['status' => false, 'message' => 'Please enter a reservation code'];
        } else {
            // Get reservation by code
            $stmt = $conn->prepare("
                SELECT r.*, s.date, s.start_time, s.end_time, m.title as movie_title, 
                c.name as cinema_name, TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration
                FROM reservations r
                JOIN schedules s ON r.schedule_id = s.id
                JOIN movies m ON s.movie_id = m.id
                JOIN cinemas c ON s.cinema_id = c.id
                WHERE r.reservation_code = ?
            ");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $result = $stmt->get_result();
            $reservation = $result->fetch_assoc();
            
            if (!$reservation) {
                $verification_result = ['status' => false, 'message' => 'Reservation not found'];
            } else if ($reservation['status'] === 'verified') {
                $verification_result = ['status' => false, 'message' => 'Reservation already verified'];
            } else if ($reservation['status'] === 'cancelled') {
                $verification_result = ['status' => false, 'message' => 'Reservation has been cancelled'];
            } else if ($reservation['status'] === 'expired') {
                $verification_result = ['status' => false, 'message' => 'Reservation has expired'];
            } else {
                // Use the checkVerificationTiming function for consistent timing checks
                $timingCheck = checkVerificationTiming($reservation);
                
                if ($timingCheck['status'] === false) {
                    $verification_result = [
                        'status' => false, 
                        'message' => $timingCheck['message']
                    ];
                } else {
                    // Update status to verified only if timing check passes
                    $updateStmt = $conn->prepare("UPDATE reservations SET status = 'verified' WHERE id = ?");
                    $updateStmt->bind_param("i", $reservation['id']);
                    $updateStmt->execute();
                
                $verification_result = [
                    'status' => true, 
                    'message' => $timingCheck['message']
                ];
                }
            }
        }
    } else if (isset($_POST['verify_qr'])) {
        // QR verification (from QR scanner)
        $qr_data = $_POST['qr_data'] ?? '';
        
        if (empty($qr_data)) {
            $verification_result = ['status' => false, 'message' => 'No QR data provided'];
        } else {
            // Use verification function
            $verification_result = verifyReservationQR($qr_data, $conn);
            if ($verification_result['status'] && isset($verification_result['reservation'])) {
                $reservation = $verification_result['reservation'];
                
                // Make sure we have the movie duration
                if (!isset($reservation['duration'])) {
                    // Get the movie duration if not already set
                    $durationStmt = $conn->prepare("
                        SELECT TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration
                        FROM reservations r
                        JOIN schedules s ON r.schedule_id = s.id
                        WHERE r.id = ?
                    ");
                    $durationStmt->bind_param("i", $reservation['id']);
                    $durationStmt->execute();
                    $durationResult = $durationStmt->get_result();
                    if ($durationRow = $durationResult->fetch_assoc()) {
                        $reservation['duration'] = $durationRow['duration'];
                    } else {
                        $reservation['duration'] = 120; // Default to 2 hours if not found
                    }
                }
            }
            }
        }
    }
    
    // Get reserved seats if we have a reservation
    if ($reservation) {
    $stmt = $conn->prepare("
            SELECT s.seat_number 
            FROM reservation_details rd
            JOIN seats s ON rd.seat_id = s.id
            WHERE rd.reservation_id = ?
        ");
    $stmt->bind_param("i", $reservation['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $seats = [];
    
    while ($seat = $result->fetch_assoc()) {
        $seats[] = $seat['seat_number'];
    }
    
        $reservation['seats'] = implode(', ', $seats);
}

// Generate Direct Verification URL for testing (normally this would be in the QR generation code)
$verificationExampleUrl = '';
if (isset($reservation) && $reservation) {
    $verifyToken = substr(md5($reservation['reservation_code'] . $reservation['id'] . 'cinebook_verify_salt'), 0, 16);
    $serverName = $_SERVER['SERVER_NAME'];
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $verificationExampleUrl = $protocol . $serverName . $scriptPath . 
                              "?verify=" . $verifyToken . 
                              "&code=" . $reservation['reservation_code'] . 
                              "&id=" . $reservation['id'];
}

// Add the time-based verification check function that uses movie duration
function checkVerificationTiming($reservation) {
    // Check if start_time is in 12-hour format with AM/PM
    if (strpos($reservation['start_time'], 'AM') !== false || strpos($reservation['start_time'], 'PM') !== false) {
        // Format the date and time to ensure proper AM/PM handling
        $dateTimeString = $reservation['date'] . ' ' . $reservation['start_time'];
        $showDateTime = strtotime($dateTimeString);
        
        // For debugging
        error_log("Time format: " . $dateTimeString . " parsed as " . date('Y-m-d h:i A', $showDateTime));
    } else {
        // Assume it's in 24-hour format
        $showDateTime = strtotime($reservation['date'] . ' ' . $reservation['start_time']);
    }
    
    $currentTime = time();
    $timeUntilShow = $showDateTime - $currentTime;
    $twoHoursInSeconds = 2 * 60 * 60;
    
    // Log key time values for debugging
    error_log("Current time: " . date('Y-m-d h:i A', $currentTime));
    error_log("Show time: " . date('Y-m-d h:i A', $showDateTime));
    error_log("Time until show: " . ($timeUntilShow/3600) . " hours");
    
    // Calculate grace period (2/3 of movie duration)
    $movieDurationMinutes = isset($reservation['duration']) ? $reservation['duration'] : 120; // Default 2 hours
    $gracePeriodMinutes = ceil($movieDurationMinutes * 2 / 3);
    $gracePeriodSeconds = $gracePeriodMinutes * 60;
    
    if ($timeUntilShow > $twoHoursInSeconds) {
        // Too early to verify
        $movieDateTime = date('F d, Y, h:i A', strtotime($reservation['date'] . ' ' . $reservation['start_time']));
        $hoursUntilShow = ceil($timeUntilShow / 3600);
        return [
            'status' => false, 
            'message' => "Too early to verify. This reservation is for $movieDateTime, which is $hoursUntilShow hours away. Please verify within 2 hours of showtime. (Current time: " . date('h:i A', $currentTime) . ")",
            'timing' => 'early',
            'time_data' => [
                'show_time' => $showDateTime,
                'current_time' => $currentTime,
                'time_until_show' => $timeUntilShow,
                'hours_until_show' => $hoursUntilShow,
                'grace_period_minutes' => $gracePeriodMinutes
            ]
        ];
    } else if ($timeUntilShow < 0) {
        // Movie has already started
        if ($timeUntilShow > -$gracePeriodSeconds) { // Within grace period after start time
            return [
                'status' => true, 
                'message' => "Reservation verified successfully. Note: Movie has already started. You have " . 
                             ceil(($gracePeriodSeconds + $timeUntilShow) / 60) . " minutes left to enter.",
                'timing' => 'grace',
                'time_data' => [
                    'show_time' => $showDateTime,
                    'current_time' => $currentTime,
                    'time_since_start' => abs($timeUntilShow),
                    'grace_period_minutes' => $gracePeriodMinutes,
                    'minutes_remaining' => ceil(($gracePeriodSeconds + $timeUntilShow) / 60)
                ]
            ];
        } else {
            // Beyond grace period
            return [
                'status' => false, 
                'message' => "Verification failed. Movie started " . ceil(abs($timeUntilShow) / 60) . 
                             " minutes ago, which exceeds the grace period of $gracePeriodMinutes minutes.",
                'timing' => 'expired',
                'time_data' => [
                    'show_time' => $showDateTime,
                    'current_time' => $currentTime,
                    'time_since_start' => abs($timeUntilShow),
                    'grace_period_minutes' => $gracePeriodMinutes
                ]
            ];
        }
    } else {
        // Within 2 hours of showtime - perfect timing!
        return [
            'status' => true, 
            'message' => 'Reservation verified successfully',
            'timing' => 'perfect',
            'time_data' => [
                'show_time' => $showDateTime,
                'current_time' => $currentTime,
                'minutes_until_show' => ceil($timeUntilShow / 60)
            ]
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            font-family: 'Roboto', sans-serif;
            color: #fff;
            padding: 0;
            margin: 0;
        }
        .header {
            background-color: #e50914;
            color: white;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        .card {
            background-color: #1f1f1f;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            color: #fff;
        }
        .card-header {
            background-color: #292929;
            border-bottom: 1px solid #333;
            padding: 15px 20px;
            font-weight: bold;
            color: #fff;
        }
        .step {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #e50914;
        }
        .step-number {
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            background-color: #e50914;
            color: white;
            border-radius: 50%;
            margin-right: 10px;
        }
        .step-title {
            font-weight: bold;
            display: inline-block;
            vertical-align: middle;
        }
        .step-content {
            margin-top: 10px;
            margin-left: 38px;
            color: #ccc;
        }
        .btn-primary {
            background-color: #e50914;
            border-color: #e50914;
        }
        .btn-primary:hover {
            background-color: #b2070f;
            border-color: #b2070f;
        }
        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        .success-box {
            background: linear-gradient(to right, #2ecc71, #27ae60);
            color: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .alert-success {
            background-color: #2ecc71;
            color: white;
            border-color: #27ae60;
        }
        .alert-danger {
            background-color: #e74c3c;
            color: white;
            border-color: #c0392b;
        }
        .form-control {
            background-color: #333;
            border: 1px solid #444;
            color: #fff;
            padding: 10px 15px;
        }
        .form-control:focus {
            background-color: #3a3a3a;
            color: #fff;
            border-color: #e50914;
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }
        .form-control::placeholder {
            color: #aaa;
        }
        label {
            color: #ccc;
            margin-bottom: 8px;
        }
        .direct-link-box {
            background: rgba(229, 9, 20, 0.15);
            border: 1px dashed #e50914;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .auto-verify-badge {
            background-color: #e50914;
            color: white;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .card {
                margin-bottom: 15px;
            }
        }
        .time-info {
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-left: 4px solid #f39c12;
        }
        .time-early {
            border-left-color: #3498db;
        }
        .time-ready {
            border-left-color: #2ecc71;
        }
        .time-late {
            border-left-color: #e74c3c;
        }
        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2><i class="fas fa-qrcode"></i> CineBook QR Verification</h2>
    </div>
    
    <div class="container">
        <?php if ($verification_result): ?>
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-clipboard-check"></i> Verification Result</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-<?= $verification_result['status'] ? 'success' : 'danger' ?>">
                        <i class="fas fa-<?= $verification_result['status'] ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                        <?= htmlspecialchars($verification_result['message']) ?>
                    </div>
                    
                    <?php if ($reservation && $verification_result['status']): ?>
                        <div class="success-box">
                    <div class="row">
                        <div class="col-md-6">
                                    <h5><i class="fas fa-ticket-alt"></i> Reservation Details</h5>
                                    <p><strong>Code:</strong> <?= htmlspecialchars($reservation['reservation_code']) ?></p>
                                    <p><strong>Movie:</strong> <?= htmlspecialchars($reservation['movie_title']) ?></p>
                                    <p><strong>Cinema:</strong> <?= htmlspecialchars($reservation['cinema_name']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Date:</strong> <?= date('F d, Y', strtotime($reservation['date'])) ?></p>
                                    <p><strong>Time:</strong> <?= date('h:i A', strtotime($reservation['date'] . ' ' . $reservation['start_time'])) ?></p>
                                    <p><strong>Seats:</strong> <?= $reservation['seats'] ?? 'N/A' ?></p>
                                    <p><strong>Amount:</strong> ₱<?= number_format($reservation['total_amount'], 2) ?></p>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="print_ticket.php?id=<?= $reservation['id'] ?>" class="btn btn-light" target="_blank">
                                    <i class="fas fa-print"></i> Print Ticket
                                </a>
                            </div>
                        </div>
                    <?php elseif ($reservation): ?>
                        <?php
                            // Display time information for failed verifications
                            $showDateTime = strtotime($reservation['date'] . ' ' . $reservation['start_time']);
                            $currentTime = time();
                            $timeUntilShow = $showDateTime - $currentTime;
                            $hoursUntilShow = ceil($timeUntilShow / 3600);
                            $minutesUntilShow = ceil($timeUntilShow / 60);
                            
                            // Calculate grace period (2/3 of movie duration)
                            $movieDurationMinutes = isset($reservation['duration']) ? $reservation['duration'] : 120; // Default 2 hours
                            $gracePeriodMinutes = ceil($movieDurationMinutes * 2 / 3);
                            
                            if ($timeUntilShow > 7200) { // More than 2 hours
                                $timeClass = 'time-early';
                                $timeIcon = 'clock';
                                $timeMessage = "Verification will be available 2 hours before the show.";
                                $countdownText = "$hoursUntilShow hours until showtime";
                            } else if ($timeUntilShow > 0) { // Less than 2 hours but not started
                                $timeClass = 'time-ready';
                                $timeIcon = 'check-circle';
                                $timeMessage = "This reservation is ready for verification!";
                                $countdownText = "$minutesUntilShow minutes until showtime";
                            } else if ($timeUntilShow > -($gracePeriodMinutes * 60)) { // Started but within grace period
                                $timeClass = 'time-ready';
                                $timeIcon = 'exclamation-circle';
                                $timeMessage = "Movie has started but verification is still available.";
                                $minutesLeft = ceil(($gracePeriodMinutes * 60 + $timeUntilShow) / 60);
                                $countdownText = "Movie started " . abs(ceil($timeUntilShow / 60)) . " minutes ago. You have $minutesLeft minutes left to enter.";
                            } else { // Started more than grace period ago
                                $timeClass = 'time-late';
                                $timeIcon = 'times-circle';
                                $timeMessage = "Movie started more than the grace period of $gracePeriodMinutes minutes ago. Verification period has ended.";
                                $countdownText = "Movie started " . abs(ceil($timeUntilShow / 60)) . " minutes ago";
                            }
                        ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold"><i class="fas fa-info-circle"></i> Reservation Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Movie:</strong> <?= htmlspecialchars($reservation['movie_title'] ?? 'N/A') ?></p>
                                        <p><strong>Cinema:</strong> <?= htmlspecialchars($reservation['cinema_name'] ?? 'N/A') ?></p>
                                    </div>
                        <div class="col-md-6">
                                        <p><strong>Date:</strong> <?= date('F d, Y', strtotime($reservation['date'])) ?></p>
                                        <p><strong>Time:</strong> <?= date('h:i A', strtotime($reservation['date'] . ' ' . $reservation['start_time'])) ?></p>
                                        <p><strong>Duration:</strong> <?= isset($reservation['duration']) ? $reservation['duration'] : 'N/A' ?> minutes</p>
                                        <p><strong>Grace Period:</strong> <?= $gracePeriodMinutes ?> minutes</p>
                                    </div>
                                </div>
                                <div class="time-info <?= $timeClass ?>">
                                    <i class="fas fa-<?= $timeIcon ?>"></i> <?= $timeMessage ?>
                                    <div class="countdown"><?= $countdownText ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-link"></i> Fast Verification <span class="auto-verify-badge">Auto-Verify</span></h6>
                    </div>
                    <div class="card-body">
                        <p>The new QR codes contain a special verification link that automatically verifies the reservation when scanned.</p>
                        
                        <div class="step">
                            <span class="step-number">1</span>
                            <span class="step-title">Simply scan the QR code</span>
                            <div class="step-content">
                                Use your phone's camera app to scan the reservation QR code.
                        </div>
                    </div>
                    
                        <div class="step">
                            <span class="step-number">2</span>
                            <span class="step-title">Tap the link</span>
                            <div class="step-content">
                                Your phone will show a link - tap it to open the verification page.
                            </div>
                            </div>
                            
                        <div class="step">
                            <span class="step-number">3</span>
                            <span class="step-title">Verification complete!</span>
                            <div class="step-content">
                                The verification happens automatically - no need to copy/paste anything!
                                    </div>
                                            </div>
                        
                        <?php if ($verificationExampleUrl): ?>
                            <div class="direct-link-box mt-4">
                                <h6><i class="fas fa-info-circle"></i> Example Verification Link:</h6>
                                <p class="text-break"><?= htmlspecialchars($verificationExampleUrl) ?></p>
                                <div class="text-center">
                                    <a href="<?= htmlspecialchars($verificationExampleUrl) ?>" class="btn btn-primary">
                                        <i class="fas fa-external-link-alt"></i> Test Direct Verification
                                                </a>
                                            </div>
                                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-keyboard"></i> Manual Verification</h6>
                                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group mb-3">
                                <label class="mb-2">Reservation Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="reservation_code" 
                                           placeholder="Enter code (e.g. RSV-ABCDEF1234)">
                                    <button type="submit" name="verify_code" class="btn btn-success">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold"><i class="fas fa-paste"></i> Paste QR Data</h6>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group mb-3">
                                <label>QR Code Data</label>
                                <textarea class="form-control" name="qr_data" rows="4" placeholder="Paste the JSON data from the QR code here..."></textarea>
                            </div>
                            <button type="submit" name="verify_qr" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Verify QR Data
                            </button>
                        </form>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

