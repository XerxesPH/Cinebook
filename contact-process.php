<?php
// Include database connection
require_once 'includes/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate the form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Check if user is logged in
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_form_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ];
        header("Location: contact.php");
        exit;
    }
    
    // Insert message into admin_messages table
    $sql = "INSERT INTO admin_messages (user_id, name, email, subject, message, is_read) VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issss", $user_id, $name, $email, $subject, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            // Set success message
            $_SESSION['contact_success'] = "Thank you for your message! We will get back to you shortly.";
            
            // Email configuration (this is just for demonstration - won't actually send in this example)
            $to = "info@cinebook.com"; // Replace with your email
            $email_subject = "New Contact Form Submission: " . htmlspecialchars($subject);
            $email_body = "You have received a new message from your website's contact form.\n\n";
            $email_body .= "Name: " . htmlspecialchars($name) . "\n";
            $email_body .= "Email: " . htmlspecialchars($email) . "\n";
            
            if (!empty($phone)) {
                $email_body .= "Phone: " . htmlspecialchars($phone) . "\n";
            }
            
            $email_body .= "Subject: " . htmlspecialchars($subject) . "\n";
            $email_body .= "Message:\n" . htmlspecialchars($message) . "\n";
            
            $headers = "From: noreply@cinebook.com\n";
            $headers .= "Reply-To: " . htmlspecialchars($email);
            
            // Uncomment this to actually send the email in production
            // mail($to, $email_subject, $email_body, $headers);
            
            header("Location: contact.php");
            exit;
        } else {
            $_SESSION['contact_errors'] = ["Failed to send message. Please try again later."];
            $_SESSION['contact_form_data'] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message
            ];
            header("Location: contact.php");
            exit;
        }
    } else {
        $_SESSION['contact_errors'] = ["Database error. Please try again later."];
        $_SESSION['contact_form_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ];
        header("Location: contact.php");
        exit;
    }
} else {
    // If someone tries to access this script directly, redirect to contact page
    header("Location: contact.php");
    exit;
}
?> 