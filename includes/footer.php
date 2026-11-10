<?php
$footer_base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/user/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $footer_base_path = '../';
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>🚀 SafeTrip</h3>
                <p>Travel safer together. Made with ❤️ for students and travelers.</p>
            </div>
            <div class="footer-col">
                <h4>Features</h4>
                <ul>
                    <li><a href="<?php echo $footer_base_path; ?>index.php#features">Verified Profiles</a></li>
                    <li><a href="<?php echo $footer_base_path; ?>index.php#features">Route Matching</a></li>
                    <li><a href="<?php echo $footer_base_path; ?>index.php#features">Emergency SOS</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="<?php echo $footer_base_path; ?>index.php#about">About</a></li>
                    <li><a href="<?php echo $footer_base_path; ?>index.php#features">Features</a></li>
                    <li><a href="<?php echo $footer_base_path; ?>index.php#how">How It Works</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">support@safetrip.com</a></li>
                    <li><a href="#">Emergency: 112</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 SafeTrip. Travel safer together. 🎉</p>
        </div>
    </div>
</footer>
<script>
    window.SafeTrip = window.SafeTrip || {};
    window.SafeTrip.csrfToken = <?php echo json_encode(csrf_token(), JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo $footer_base_path; ?>assets/js/main.js"></script>
<script src="<?php echo $footer_base_path; ?>assets/js/map.js"></script>
<script src="<?php echo $footer_base_path; ?>assets/js/emergency.js"></script>