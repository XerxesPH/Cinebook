<?php

$pageTitle = "CineBook - Contact Us";

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'includes/db.php';

// Get any error or success messages from session
$errors = isset($_SESSION['contact_errors']) ? $_SESSION['contact_errors'] : [];
$success = isset($_SESSION['contact_success']) ? $_SESSION['contact_success'] : '';
$formData = isset($_SESSION['contact_form_data']) ? $_SESSION['contact_form_data'] : [];

// Clear session variables
unset($_SESSION['contact_errors']);
unset($_SESSION['contact_success']);
unset($_SESSION['contact_form_data']);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userData = [];

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    
    // Fetch user data to pre-fill form
    $userQuery = "SELECT name, email, contact_number FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $userQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $userData = mysqli_fetch_assoc($result);
    }
}

include 'includes/header.php';

?>

<link rel="stylesheet" href="css/contact.css">

<!-- Contact Content -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-form-container">
                <div class="section-header">
                    <h2>Send Us a Message</h2>
                    <div class="section-divider"></div>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="form-alert error">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>
                            <?php foreach ($errors as $error): ?>
                                <?php echo htmlspecialchars($error); ?><br>
                            <?php endforeach; ?>
                        </span>
                    </div>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="form-alert success">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                <?php endif; ?>
                
                <form id="contact-form" action="contact-process.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" placeholder="John Doe" 
                                   value="<?php 
                                   if (isset($formData['name'])) {
                                       echo htmlspecialchars($formData['name']);
                                   } elseif ($isLoggedIn && isset($userData['name'])) {
                                       echo htmlspecialchars($userData['name']);
                                   } else {
                                       echo '';
                                   }
                                   ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Your Email</label>
                            <input type="email" id="email" name="email" placeholder="john@example.com" 
                                   value="<?php 
                                   if (isset($formData['email'])) {
                                       echo htmlspecialchars($formData['email']);
                                   } elseif ($isLoggedIn && isset($userData['email'])) {
                                       echo htmlspecialchars($userData['email']);
                                   } else {
                                       echo '';
                                   }
                                   ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number (Optional)</label>
                            <input type="tel" id="phone" name="phone" placeholder="(123) 456-7890"
                                   value="<?php 
                                   if (isset($formData['phone'])) {
                                       echo htmlspecialchars($formData['phone']);
                                   } elseif ($isLoggedIn && isset($userData['contact_number'])) {
                                       echo htmlspecialchars($userData['contact_number']);
                                   } else {
                                       echo '';
                                   }
                                   ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject">
                                <option value="general" <?php echo (isset($formData['subject']) && $formData['subject'] == 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="booking" <?php echo (isset($formData['subject']) && $formData['subject'] == 'booking') ? 'selected' : ''; ?>>Booking Issue</option>
                                <option value="feedback" <?php echo (isset($formData['subject']) && $formData['subject'] == 'feedback') ? 'selected' : ''; ?>>Feedback/Suggestion</option>
                                <option value="technical" <?php echo (isset($formData['subject']) && $formData['subject'] == 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="partnership" <?php echo (isset($formData['subject']) && $formData['subject'] == 'partnership') ? 'selected' : ''; ?>>Partnership Opportunities</option>
                                <option value="other" <?php echo (isset($formData['subject']) && $formData['subject'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" placeholder="How can we help you?" required><?php echo isset($formData['message']) ? htmlspecialchars($formData['message']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- Contact Info -->
            <div class="contact-info-container">
                <div class="contact-info-card">
                    <div class="section-header">
                        <h2>Contact Information</h2>
                        <div class="section-divider"></div>
                    </div>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Our Location</h3>
                                <p>LSPU San Pablo City<br>Laguna, Philippines</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Phone Number</h3>
                                <p>+1 (123) 456-7890</p>
                                <p>+1 (123) 456-7891</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Email Address</h3>
                                <p>info@cinebook.com</p>
                                <p>support@cinebook.com</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-text">
                                <h3>Opening Hours</h3>
                                <p>Monday - Sunday: 9:00 AM - 11:00 PM</p>
                                <p>Holidays: 9:00 AM - 11:00 PM</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <h3>Follow Us</h3>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="container">
        <div class="section-header">
            <h2>Find Us</h2>
            <div class="section-divider"></div>
        </div>
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3866.5233946053246!2d121.3242779760182!3d14.273004186202954!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397e3e0ac130723%3A0xe953ee9d5dd7f32a!2sLaguna%20State%20Polytechnic%20University%20-%20San%20Pablo%20City%20Campus!5e0!3m2!1sen!2sph!4v1694779012345!5m2!1sen!2sph" 
                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <h2>Frequently Asked Questions</h2>
            <div class="section-divider"></div>
            <p>Find quick answers to common questions about our services and policies.</p>
        </div>
        
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">
                    <h3>How do I book tickets online?</h3>
                    <span class="faq-icon"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Booking tickets online is simple! Visit our homepage, select the movie you want to watch, choose your preferred showtime, select your seats, and complete the payment process. You'll receive an email confirmation with your e-ticket.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Can I cancel or modify my booking?</h3>
                    <span class="faq-icon"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Yes, you can cancel or modify your booking up to 4 hours before the showtime. To do this, log in to your account, go to "My Bookings," and select the booking you wish to change. Please note that a small processing fee may apply for cancellations.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Are there any discounts for students or seniors?</h3>
                    <span class="faq-icon"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Yes, we offer special discounts for students and seniors. Students can enjoy a 15% discount with a valid student ID, while seniors (65+) receive a 20% discount. These discounts are applicable for all regular screenings from Monday to Thursday.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h3>Do you offer private screenings or venue rental?</h3>
                    <span class="faq-icon"><i class="fas fa-plus"></i></span>
                </div>
                <div class="faq-answer">
                    <p>Yes, we offer private screenings and venue rental for special events, corporate functions, birthday parties, and more. To inquire about availability and pricing, please contact our events team at events@cinebook.com or call us at (123) 456-7890.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="js/contact.js"></script>

<?php
include 'includes/footer.php';
?>