</main>
    </div>
    <script>
        window.SafeTrip = window.SafeTrip || {};
        window.SafeTrip.csrfToken = <?php echo json_encode(csrf_token(), JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/js/main.js"></script>
    <script src="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/js/ai-chat.js"></script>
</body>
</html>