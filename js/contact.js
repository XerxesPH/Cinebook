// Contact Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const contactForm = document.getElementById('contact-form');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Form validation
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value.trim();
            
            // Basic validation
            if (!name || !email || !message) {
                showAlert('Please fill in all required fields.', 'error');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('Please enter a valid email address.', 'error');
                return;
            }
            
            // In a real application, submit the form data via AJAX to the server
            // For demonstration purposes, we'll just show a success message
            showAlert('Your message has been sent! We will get back to you shortly.', 'success');
            
            // Clear the form
            contactForm.reset();
        });
    }
    
    // Subject dropdown behavior - add placeholder text based on selection
    const subjectDropdown = document.getElementById('subject');
    const messageField = document.getElementById('message');
    
    if (subjectDropdown && messageField) {
        subjectDropdown.addEventListener('change', function() {
            switch(this.value) {
                case 'booking':
                    messageField.placeholder = 'Please include your booking reference if applicable.';
                    break;
                case 'technical':
                    messageField.placeholder = 'Please describe the technical issue in detail.';
                    break;
                case 'feedback':
                    messageField.placeholder = 'We appreciate your feedback! Tell us what you enjoyed or how we can improve.';
                    break;
                case 'partnership':
                    messageField.placeholder = 'Tell us about your company and partnership proposal.';
                    break;
                default:
                    messageField.placeholder = 'How can we help you?';
            }
        });
    }
    
    // FAQ Accordion functionality
    const faqItems = document.querySelectorAll('.faq-item');
    
    if (faqItems.length > 0) {
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            
            question.addEventListener('click', function() {
                // Close all other items
                faqItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('active')) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // Toggle current item
                item.classList.toggle('active');
            });
        });
    }
    
    // Alert function for form messages
    function showAlert(message, type) {
        // Check if an alert already exists and remove it
        const existingAlert = document.querySelector('.form-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create alert element
        const alertElement = document.createElement('div');
        alertElement.className = `form-alert ${type}`;
        alertElement.innerHTML = `
            <div class="alert-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="alert-close"><i class="fas fa-times"></i></button>
        `;
        
        // Add to DOM right after the form
        contactForm.insertAdjacentElement('afterend', alertElement);
        
        // Add close functionality
        const closeButton = alertElement.querySelector('.alert-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                alertElement.remove();
            });
        }
        
        // Auto remove after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                if (alertElement.parentNode) {
                    alertElement.remove();
                }
            }, 5000);
        }
    }
});