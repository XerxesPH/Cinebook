<?php
// A simple server check script to verify database connection and file paths

// Enable error display for this diagnostic script
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Server Configuration Check</h2>";

// Check PHP version
echo "<h3>PHP Version</h3>";
echo "PHP Version: " . phpversion();
echo "<br>Required: 7.0 or higher";
echo phpversion() >= '7.0' ? " <span style='color:green'>✓</span>" : " <span style='color:red'>✗</span>";

// Check PHP extensions
echo "<h3>PHP Extensions</h3>";
$required_extensions = ['mysqli', 'json', 'session', 'gd'];
foreach ($required_extensions as $ext) {
    echo "$ext: ";
    echo extension_loaded($ext) ? " <span style='color:green'>Loaded ✓</span>" : " <span style='color:red'>Not Loaded ✗</span>";
    echo "<br>";
}

// Check database connection
echo "<h3>Database Connection</h3>";
$db_include_path = __DIR__ . '/../includes/db.php';
echo "Database include path: $db_include_path<br>";
if (file_exists($db_include_path)) {
    echo "Database include file exists <span style='color:green'>✓</span><br>";
    try {
        // Include the file but capture any output/errors
        ob_start();
        require_once $db_include_path;
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "Warning: Database include file produced output: <pre>$output</pre><br>";
        }
        
        // Check if $conn is set and working
        if (isset($conn)) {
            if ($conn->connect_error) {
                echo "Database connection error: " . $conn->connect_error . " <span style='color:red'>✗</span><br>";
            } else {
                echo "Database connection successful <span style='color:green'>✓</span><br>";
                
                // Test a simple query
                $result = $conn->query("SHOW TABLES");
                if ($result) {
                    echo "Tables in database:<br>";
                    echo "<ul>";
                    while ($row = $result->fetch_row()) {
                        echo "<li>" . $row[0] . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "Error executing query: " . $conn->error . "<br>";
                }
            }
        } else {
            echo "Database connection variable (\$conn) not set <span style='color:red'>✗</span><br>";
        }
    } catch (Exception $e) {
        echo "Error including database file: " . $e->getMessage() . " <span style='color:red'>✗</span><br>";
    }
} else {
    echo "Database include file does not exist <span style='color:red'>✗</span><br>";
}

// Check QR code library
echo "<h3>QR Code Library</h3>";
$qr_lib_path = __DIR__ . '/../includes/phpqrcode/qrlib.php';
echo "QR library path: $qr_lib_path<br>";
if (file_exists($qr_lib_path)) {
    echo "QR code library file exists <span style='color:green'>✓</span><br>";
    try {
        require_once $qr_lib_path;
        if (class_exists('QRcode')) {
            echo "QRcode class exists <span style='color:green'>✓</span><br>";
        } else {
            echo "QRcode class does not exist <span style='color:red'>✗</span><br>";
        }
    } catch (Exception $e) {
        echo "Error including QR library: " . $e->getMessage() . " <span style='color:red'>✗</span><br>";
    }
} else {
    echo "QR code library file does not exist <span style='color:red'>✓</span><br>";
    echo "This is not critical - the system will use a placeholder instead.<br>";
}

// Check directory permissions
echo "<h3>Directory Permissions</h3>";
$directories = [
    __DIR__ => "API directory",
    __DIR__ . '/../uploads' => "Uploads directory",
    'C:/xampp/htdocs/Cinema_Reservation/uploads/qrcodes' => "QR codes directory"
];

foreach ($directories as $dir => $desc) {
    echo "$desc ($dir): ";
    if (file_exists($dir)) {
        echo "Exists ";
        if (is_writable($dir)) {
            echo "and is writable <span style='color:green'>✓</span>";
        } else {
            echo "but is not writable <span style='color:red'>✗</span>";
        }
    } else {
        echo "Does not exist <span style='color:red'>✗</span>";
    }
    echo "<br>";
}

// Create uploads directory if it doesn't exist
if (!file_exists(__DIR__ . '/../uploads')) {
    echo "Attempting to create uploads directory...<br>";
    if (mkdir(__DIR__ . '/../uploads', 0755, true)) {
        echo "Uploads directory created successfully <span style='color:green'>✓</span><br>";
    } else {
        echo "Failed to create uploads directory <span style='color:red'>✗</span><br>";
    }
}

// Create QR codes directory if it doesn't exist
if (!file_exists('C:/xampp/htdocs/Cinema_Reservation/uploads/qrcodes')) {
    echo "Attempting to create QR codes directory...<br>";
    if (mkdir('C:/xampp/htdocs/Cinema_Reservation/uploads/qrcodes', 0755, true)) {
        echo "QR codes directory created successfully <span style='color:green'>✓</span><br>";
    } else {
        echo "Failed to create QR codes directory <span style='color:red'>✗</span><br>";
    }
}

echo "<h3>Session Check</h3>";
// Check if session is working
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'test_value';
echo "Session test value set: " . ($_SESSION['test'] === 'test_value' ? "<span style='color:green'>✓</span>" : "<span style='color:red'>✗</span>") . "<br>";

// Check POST handling
echo "<h3>HTTP Methods</h3>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "Server software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Environment info
echo "<h3>Environment Information</h3>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Free disk space: " . round(disk_free_space('/') / (1024 * 1024 * 1024), 2) . " GB<br>";

echo "<hr>";
echo "<p>If all checks passed with green checkmarks (✓), your server should be set up correctly for the Cinema Reservation System.</p>";
echo "<p>If you see any red crosses (✗), please address those issues before continuing.</p>";
?>