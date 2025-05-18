<!-- Footer -->
<footer>
    <div class="footer-content">
        <div class="footer-logo">
            <h2>CineBook</h2>
            <p>Experience the Magic of Cinema</p>
        </div>
        <div class="footer-links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="movies.php">All Movies</a></li>
                <li><a href="movies.php?category=now-showing">Now Showing</a></li>
                <li><a href="movies.php?category=coming-soon">Coming Soon</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-contact">
            <h3>Contact Us</h3>
            <p><i class="fas fa-map-marker-alt"></i> 123 Movie Street, Cinema City</p>
            <p><i class="fas fa-phone"></i> (123) 456-7890</p>
            <p><i class="fas fa-envelope"></i> info@cinebook.com</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 CineBook. All Rights Reserved.</p>
    </div>
</footer>

<!-- Include the login/register modal -->
<?php include 'modals/login_register_modal.php'; ?>
<?php include 'modals/login_prompt_modal.php'; ?>
<?php include 'modals/card_selection_modal.php'; ?>
<?php include 'modals/confirmation_modal.php'; ?>
<?php include 'modals/reservation_modal.php'; ?>

<script src="js/moduleLoader.js"></script>
<script src="js/confirmation_script.js"></script>
<script src="js/navigation.js"></script>
<script src="js/script.js"></script>
<script src="js/login-registerModal.js"></script>
<script src="js/profile_modal.js"></script>

<?php if (isset($additionalScripts)): ?>
    <?php foreach ($additionalScripts as $script): ?>
        <script src="<?php echo $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>