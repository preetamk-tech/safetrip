<?php
if (!isset($page_title)) {
    $page_title = 'SafeTrip Admin';
}
$current_path = trim(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH), '/');
$base_path = '../';
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

function nav_active($needle, $current) {
    return strpos($current, $needle) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SafeTrip Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/dashboard_new.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/components.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/admin.css">
</head>
<body class="st-dashboard">
    <script>
        window.SafeTrip = window.SafeTrip || {};
        window.SafeTrip.csrfToken = <?php echo json_encode(csrf_token(), JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <nav class="st-navbar">
        <a href="<?php echo $base_path; ?>admin/dashboard.php" class="st-navbar-brand">
            <i class="fas fa-shield-alt"></i> SafeTrip Admin
        </a>
        <div class="st-navbar-menu">
            <ul class="st-navbar-links">
                <li><a href="<?php echo $base_path; ?>admin/dashboard.php" class="<?php echo nav_active('dashboard', $current_path); ?>">Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>admin/verify_users.php" class="<?php echo nav_active('verify', $current_path); ?>">Users</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage_trips.php" class="<?php echo nav_active('manage_trips', $current_path); ?>">Trips</a></li>
                <li><a href="<?php echo $base_path; ?>admin/reports.php" class="<?php echo nav_active('reports', $current_path); ?>">Reports</a></li>
            </ul>
            <a href="<?php echo $base_path; ?>auth/logout.php" class="st-btn-logout">Logout</a>
        </div>
    </nav>

    <div class="st-dashboard-container">
        <aside class="st-sidebar">
            <div class="st-profile-card">
                <div class="st-profile-avatar" style="background: #f59e0b;"><i class="fas fa-user-shield"></i></div>
                <div class="st-profile-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="st-profile-role">Administrator</div>
            </div>

            <nav class="st-sidebar-nav">
                <a href="<?php echo $base_path; ?>admin/dashboard.php" class="st-sidebar-link <?php echo nav_active('dashboard', $current_path); ?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="<?php echo $base_path; ?>admin/verify_users.php" class="st-sidebar-link <?php echo nav_active('verify', $current_path); ?>">
                    <i class="fas fa-user-check"></i> Verify Users
                </a>
                <a href="<?php echo $base_path; ?>admin/manage_trips.php" class="st-sidebar-link <?php echo nav_active('manage_trips', $current_path); ?>">
                    <i class="fas fa-route"></i> Manage Trips
                </a>
                <a href="<?php echo $base_path; ?>admin/reports.php" class="st-sidebar-link <?php echo nav_active('reports', $current_path); ?>">
                    <i class="fas fa-flag"></i> Reports
                </a>
                <a href="<?php echo $base_path; ?>admin/system_monitor.php" class="st-sidebar-link <?php echo nav_active('system', $current_path); ?>">
                    <i class="fas fa-server"></i> System Monitor
                </a>
                <a href="#" onclick="showChangePassword()" class="st-sidebar-link">
                    <i class="fas fa-key"></i> Change Password
                </a>
            </nav>
        </aside>

        <main class="st-main">
        
        <!-- Change Password Modal -->
        <div id="changePasswordModal" class="admin-modal" style="display: none;">
            <div class="admin-modal-content">
                <div class="admin-modal-header">
                    <h3><i class="fas fa-key"></i> Change Password</h3>
                    <button class="admin-modal-close" onclick="closeChangePassword()">&times;</button>
                </div>
                <div class="admin-modal-body">
                    <form id="changePasswordForm" onsubmit="return handleChangePassword(event)">
                        <div class="admin-form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required class="admin-input">
                        </div>
                        <div class="admin-form-group">
                            <label>New Password (min 8 chars)</label>
                            <input type="password" name="new_password" required minlength="8" class="admin-input">
                        </div>
                        <div class="admin-form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required minlength="8" class="admin-input">
                        </div>
                        <div id="passwordMessage" class="admin-form-message" style="display: none;"></div>
                        <div class="admin-form-actions">
                            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeChangePassword()">Cancel</button>
                            <button type="submit" class="admin-btn admin-btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        function showChangePassword() {
            document.getElementById('changePasswordModal').style.display = 'flex';
            document.getElementById('passwordMessage').style.display = 'none';
            document.getElementById('changePasswordForm').reset();
        }
        
        function closeChangePassword() {
            document.getElementById('changePasswordModal').style.display = 'none';
        }
        
        function handleChangePassword(e) {
            e.preventDefault();
            const form = e.target;
            const current = form.current_password.value;
            const newPass = form.new_password.value;
            const confirm = form.confirm_password.value;
            const message = document.getElementById('passwordMessage');
            
            if (newPass !== confirm) {
                message.textContent = 'New passwords do not match!';
                message.className = 'admin-form-message admin-form-error';
                message.style.display = 'block';
                return false;
            }
            
            if (newPass.length < 8) {
                message.textContent = 'Password must be at least 8 characters!';
                message.className = 'admin-form-message admin-form-error';
                message.style.display = 'block';
                return false;
            }
            
            fetch('../api/change_admin_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.SafeTrip?.csrfToken || ''
                },
                body: JSON.stringify({
                    current_password: current,
                    new_password: newPass
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    message.textContent = 'Password changed successfully!';
                    message.className = 'admin-form-message admin-form-success';
                    message.style.display = 'block';
                    setTimeout(closeChangePassword, 1500);
                } else {
                    message.textContent = data.message || 'Failed to change password';
                    message.className = 'admin-form-message admin-form-error';
                    message.style.display = 'block';
                }
            })
            .catch(() => {
                message.textContent = 'An error occurred';
                message.className = 'admin-form-message admin-form-error';
                message.style.display = 'block';
            });
            
            return false;
        }
        
        document.addEventListener('click', function(e) {
            if (e.target.id === 'changePasswordModal') {
                closeChangePassword();
            }
        });
        </script>