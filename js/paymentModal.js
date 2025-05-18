document.addEventListener('DOMContentLoaded', function() {
    // Payment Modal Elements
    const paymentModal = document.getElementById('paymentModal');
    const closePaymentButton = paymentModal?.querySelector('.close-btn');
    
    // Close Payment Modal when clicking on close button
    if (closePaymentButton) {
        closePaymentButton.addEventListener('click', function() {
            paymentModal.style.display = 'none';
        });
    }
    
    // Close Payment Modal when clicking outside the modal
    window.addEventListener('click', function(event) {
        if (event.target === paymentModal) {
            paymentModal.style.display = 'none';
        }
    });
    
    // Handle Payment Form Submission
    document.getElementById('payment-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values (add validation as needed)
        const cardName = document.getElementById('card-name').value;
        const cardNumber = document.getElementById('card-number').value;
        const expiryDate = document.getElementById('expiry-date').value;
        const cvv = document.getElementById('cvv').value;
        
        // Validate payment details
        if (!validatePaymentDetails(cardName, cardNumber, expiryDate, cvv)) {
            return;
        }
        
        // In a real application, process the payment through a secure payment gateway
        // For demo purposes, just show success message
        processPayment();
    });
    
    // Validate payment details
    function validatePaymentDetails(cardName, cardNumber, expiryDate, cvv) {
        // Basic validation
        if (!cardName || !cardNumber || !expiryDate || !cvv) {
            alert('Please fill in all payment details');
            return false;
        }
        
        // Card number validation (should be 16 digits)
        if (!/^\d{16}$/.test(cardNumber.replace(/\s/g, ''))) {
            alert('Please enter a valid 16-digit card number');
            return false;
        }
        
        // Expiry date validation (should be MM/YY format)
        if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
            alert('Please enter expiry date in MM/YY format');
            return false;
        }
        
        // CVV validation (should be 3 digits)
        if (!/^\d{3}$/.test(cvv)) {
            alert('Please enter a valid 3-digit CVV');
            return false;
        }
        
        return true;
    }
    
    // Process payment
    function processPayment() {
        // In a real application, this would call a secure payment API
        
        // Simulate processing with loading indicator
        const submitButton = document.querySelector('#payment-form button[type="submit"]');
        const originalText = submitButton.textContent;
        
        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';
        
        // Simulate network delay
        setTimeout(function() {
            // Success message
            alert('Payment successful! Your seats have been reserved.');
            
            // Reset form
            document.getElementById('payment-form').reset();
            
            // Close modal
            paymentModal.style.display = 'none';
            
            // Reset button
            submitButton.disabled = false;
            submitButton.textContent = originalText;
            
            // In a real application, redirect to confirmation page or update UI
            // window.location.href = 'confirmation.php';
        }, 1500);
    }
    
    // Format card number with spaces for readability
    document.getElementById('card-number')?.addEventListener('input', function(e) {
        let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        
        if (value.length > 16) {
            value = value.substr(0, 16);
        }
        
        // Add space after every 4 digits
        const matches = value.match(/\d{4,16}/g);
        const match = matches && matches[0] || '';
        const parts = [];
        
        for (let i = 0, len = match.length; i < len; i += 4) {
            parts.push(match.substring(i, i + 4));
        }
        
        if (parts.length) {
            this.value = parts.join(' ');
        } else {
            this.value = value;
        }
    });
    
    // Format expiry date
    document.getElementById('expiry-date')?.addEventListener('input', function(e) {
        let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        
        if (value.length > 4) {
            value = value.substr(0, 4);
        }
        
        if (value.length > 2) {
            this.value = value.substr(0, 2) + '/' + value.substr(2);
        } else {
            this.value = value;
        }
    });
});