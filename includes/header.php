<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/user/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $base_path = '../';
}

$current_path = trim(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH), '/');
$is_admin = isset($_SESSION['user_id'], $_SESSION['is_admin']) && (int) $_SESSION['is_admin'] === 1;
$is_user = isset($_SESSION['user_id']) && !$is_admin;
$unread = 0;

if ($is_user && isset($conn)) {
    $uid = (int) $_SESSION['user_id'];
    $res = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $uid AND is_read = 0");
    if ($res) {
        $unread = (int) $res->fetch_assoc()['cnt'];
    }
}

function nav_is_active($needle, $current_path) {
    return strpos($current_path, trim($needle, '/')) !== false ? 'active' : '';
}
?>
<header class="site-header">
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo $base_path; ?>index.php" class="nav-brand">
                <i class="fas fa-shield-heart"></i> SafeTrip
            </a>
            <ul class="nav-links">
                <?php if ($is_admin): ?>
                <li><a href="<?php echo $base_path; ?>admin/dashboard.php" class="<?php echo nav_is_active('admin/dashboard.php', $current_path); ?>">Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>admin/verify_users.php" class="<?php echo nav_is_active('admin/verify_users.php', $current_path); ?>">Verify Users</a></li>
                <li><a href="<?php echo $base_path; ?>admin/manage_trips.php" class="<?php echo nav_is_active('admin/manage_trips.php', $current_path); ?>">Trips</a></li>
                <li><a href="<?php echo $base_path; ?>admin/reports.php" class="<?php echo nav_is_active('admin/reports.php', $current_path); ?>">Reports</a></li>
                <li><a href="<?php echo $base_path; ?>auth/logout.php" class="btn-nav">Logout</a></li>
                <?php elseif ($is_user): ?>
                <li><a href="<?php echo $base_path; ?>user/dashboard.php" class="<?php echo nav_is_active('user/dashboard.php', $current_path); ?>">Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>user/browse_trips.php" class="<?php echo nav_is_active('user/browse_trips.php', $current_path); ?>">Browse</a></li>
                <li><a href="<?php echo $base_path; ?>user/my_trips.php" class="<?php echo nav_is_active('user/my_trips.php', $current_path); ?>">My Trips</a></li>
                <li><a href="<?php echo $base_path; ?>user/profile.php" class="<?php echo nav_is_active('user/profile.php', $current_path); ?>">Profile</a></li>
                <li>
                    <a href="<?php echo $base_path; ?>user/notifications.php" class="<?php echo nav_is_active('user/notifications.php', $current_path); ?>">
                        Notifications <?php if ($unread > 0): ?><span style="background: var(--danger); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $unread; ?></span><?php endif; ?>
                    </a>
                </li>
                <li><a href="<?php echo $base_path; ?>auth/logout.php" class="btn-nav">Logout</a></li>
                <?php else: ?>
                <li><a href="<?php echo $base_path; ?>index.php">Home</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#features">Features</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#how">How It Works</a></li>
                <li><a href="<?php echo $base_path; ?>index.php#about">About</a></li>
                <li><a href="#" onclick="openLoginModal(); return false;">Login</a></li>
                <li><a href="#" onclick="openRegisterModal(); return false;" class="btn-nav">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>