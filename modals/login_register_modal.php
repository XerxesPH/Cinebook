<?php
// The error and success messages are already defined in auth.php, so we don't need to define them here
// $loginError, $registerError, and $registerSuccess are already available from auth.php
?>

<!-- Login/Register Modal -->
<div class="modal" id="loginModal">
    <div class="modal-content smaller-modal">
        <span class="close-btn">&times;</span>
        <div class="modal-tabs">
            <button class="tab-btn active" data-tab="login">Login</button>
            <button class="tab-btn" data-tab="register">Register</button>
        </div>
        <div class="tab-content" id="login">
            <form id="login-form" method="POST" action="">
                <?php if (!empty($loginError)): ?>
                    <div class="error-message"><?php echo $loginError; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="login-email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="login-password" required>
                </div>
                <button type="submit" name="login" class="submit-btn">Login</button>
            </form>
        </div>
        <div class="tab-content" id="register">
            <form id="register-form" method="POST" action="">
                <?php if (!empty($registerError)): ?>
                    <div class="error-message"><?php echo $registerError; ?></div>
                <?php endif; ?>
                <?php if (!empty($registerSuccess)): ?>
                    <div class="success-message"><?php echo $registerSuccess; ?></div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="register-name">Full Name</label>
                    <input type="text" id="register-name" name="register-name" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="register-email" required>
                </div>
                <div class="form-group">
                    <label for="register-phone">Contact Number</label>
                    <input type="tel" id="register-phone" name="register-phone" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="register-password" required>
                </div>
                <div class="form-group">
                    <label for="register-confirm-password">Confirm Password</label>
                    <input type="password" id="register-confirm-password" name="register-confirm-password" required>
                </div>
                <button type="submit" name="register" class="submit-btn">Register</button>
            </form>
        </div>
    </div>
</div>