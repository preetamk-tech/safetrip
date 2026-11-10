<?php
// Quick fix - add page title if not set
if (!isset($page_title)) {
    $page_title = 'SafeTrip';
}
$current_path = trim(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH), '/');
$base_path = '';
if (strpos($current_path, 'user/') !== false || strpos($current_path, 'admin/') !== false) {
    $base_path = '../';
}
$user = $_SESSION['user_name'] ?? 'User';
$initials = strtoupper(substr($user, 0, 2));
$trust_score = isset($_SESSION['trust_score']) ? (int)$_SESSION['trust_score'] : 70;
$unread_notifications = 0;
$pending_join_requests = 0;
$total_notifications = 0;

if (isset($conn, $_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    
    // Get unread notifications count
    $res = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $uid AND is_read = 0");
    if ($res) $unread_notifications = (int)$res->fetch_assoc()['cnt'];
    
    // Get pending join requests count
    $pending_requests = $conn->query("SELECT COUNT(*) as cnt FROM join_requests WHERE to_user_id = $uid AND status = 'pending'");
    if ($pending_requests) $pending_join_requests = (int)$pending_requests->fetch_assoc()['cnt'];
    else $pending_join_requests = 0;
    
    $total_notifications = $unread_notifications + $pending_join_requests;
}

function nav_active($needle, $current) {
    return strpos($current, $needle) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SafeTrip</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/dashboard_new.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/ai-chat-widget.css">
    <style>
        body { background: #f8fafc; }
        .input-full { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 0.95rem; }
        .input-full:focus { outline: none; border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }
        .st-nav-badge {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
            min-width: 20px;
            text-align: center;
        }
        .st-sidebar-link {
            display: flex !important;
            align-items: center;
            justify-content: flex-start;
        }
        .st-sidebar-link .st-nav-badge {
            margin-left: auto;
        }
    </style>
</head>
<body class="st-dashboard">
    <!-- Top Navigation -->
    <nav class="st-navbar">
        <a href="<?php echo $base_path; ?>user/dashboard.php" class="st-navbar-brand">
            <i class="fas fa-shield-heart"></i> SafeTrip
        </a>
        <div class="st-navbar-menu">
            <ul class="st-navbar-links">
                <li><a href="<?php echo $base_path; ?>user/dashboard.php" class="<?php echo nav_active('dashboard', $current_path); ?>">Dashboard</a></li>
                <li><a href="<?php echo $base_path; ?>user/browse_trips.php" class="<?php echo nav_active('browse_trips', $current_path); ?>">Browse</a></li>
                <li><a href="<?php echo $base_path; ?>user/my_trips.php" class="<?php echo nav_active('my_trips', $current_path); ?>">My Trips</a></li>
                <li><a href="<?php echo $base_path; ?>user/profile.php" class="<?php echo nav_active('profile', $current_path); ?>">Profile</a></li>
                <li><a href="<?php echo $base_path; ?>user/notifications.php" class="<?php echo nav_active('notifications', $current_path); ?>">Notifications <?php if($unread_notifications > 0): ?><span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $unread_notifications; ?></span><?php endif; ?></a></li>
                <li><a href="<?php echo $base_path; ?>user/join_requests.php" class="<?php echo nav_active('join_requests', $current_path); ?>">Join Requests <?php if($pending_join_requests > 0): ?><span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?php echo $pending_join_requests; ?></span><?php endif; ?></a></li>
            </ul>
            <a href="<?php echo $base_path; ?>auth/logout.php" class="st-btn-logout">Logout</a>
        </div>
    </nav>

    <div class="st-dashboard-container">
        <!-- Sidebar -->
        <aside class="st-sidebar">
            <div class="st-profile-card">
                <div class="st-profile-avatar"><?php echo $initials; ?></div>
                <div class="st-profile-name"><?php echo htmlspecialchars($user); ?></div>
                <div class="st-profile-role">Traveler</div>
            </div>

            <div class="st-trust-card">
                <div class="st-trust-header">
                    <span class="st-trust-label">Trust Score</span>
                    <span class="st-trust-score"><?php echo $trust_score; ?>/100</span>
                </div>
                <div class="st-trust-progress">
                    <div class="st-trust-progress-bar" style="width: <?php echo $trust_score; ?>%"></div>
                </div>
                <div class="st-trust-text">Verified Confidence</div>
            </div>

            <nav class="st-sidebar-nav">
                 <a href="<?php echo $base_path; ?>user/emergency.php" class="st-sos-btn">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>EMERGENCY SOS</strong>
                <span>Alert instantly</span>
                </a>
                <a href="<?php echo $base_path; ?>user/dashboard.php" class="st-sidebar-link <?php echo nav_active('dashboard', $current_path); ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="<?php echo $base_path; ?>user/create_trip.php" class="st-sidebar-link <?php echo nav_active('create_trip', $current_path); ?>">
                    <i class="fas fa-plus-circle"></i> Create Trip
                </a>
                <a href="<?php echo $base_path; ?>user/browse_trips.php" class="st-sidebar-link <?php echo nav_active('browse_trips', $current_path); ?>">
                    <i class="fas fa-search"></i> Browse Trips
                </a>
                <a href="<?php echo $base_path; ?>user/my_groups.php" class="st-sidebar-link <?php echo nav_active('my_groups', $current_path); ?>">
                    <i class="fas fa-users"></i> My Groups
                </a>
                <a href="<?php echo $base_path; ?>user/favorites.php" class="st-sidebar-link <?php echo nav_active('favorites', $current_path); ?>">
                    <i class="fas fa-heart"></i> Favorites
                </a>
                <a href="<?php echo $base_path; ?>user/my_trips.php" class="st-sidebar-link <?php echo nav_active('my_trips', $current_path); ?>">
                    <i class="fas fa-suitcase-rolling"></i> My Trips
                </a>
                <a href="<?php echo $base_path; ?>user/checkin_settings.php" class="st-sidebar-link <?php echo nav_active('checkin', $current_path); ?>">
                    <i class="fas fa-clipboard-check"></i> Check-In
                </a>
                <a href="<?php echo $base_path; ?>user/notifications.php" class="st-sidebar-link <?php echo nav_active('notifications', $current_path); ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if($total_notifications > 0): ?>
                    <span class="st-nav-badge"><?php echo $total_notifications; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo $base_path; ?>user/trusted_contacts.php" class="st-sidebar-link <?php echo nav_active('trusted', $current_path); ?>">
                    <i class="fas fa-address-book"></i> Contacts
                </a>
            </nav>
        </aside>

        <main class="st-main">