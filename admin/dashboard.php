<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotAdmin();

$page_title = 'Dashboard';
require_once '../includes/admin_header.php';

$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
$pending_verifications = $conn->query("SELECT COUNT(*) as count FROM users WHERE verification_status = 'pending' AND is_admin = 0")->fetch_assoc()['count'];
$active_trips = $conn->query("SELECT COUNT(*) as count FROM trips WHERE status = 'active'")->fetch_assoc()['count'];
$total_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch_assoc()['count'];
$total_trips = $conn->query("SELECT COUNT(*) as count FROM trips")->fetch_assoc()['count'];
$total_emergencies = $conn->query("SELECT COUNT(*) as count FROM safety_alerts WHERE alert_type = 'emergency' AND DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

$gender_stats = $conn->query("SELECT gender, COUNT(*) as count FROM users WHERE is_admin = 0 GROUP BY gender");
$gender_data = ['male' => 0, 'female' => 0, 'other' => 0];
while ($row = $gender_stats->fetch_assoc()) {
    $gender_data[$row['gender']] = (int) $row['count'];
}

$monthly_users = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC
");
$user_labels = [];
$user_counts = [];
while ($row = $monthly_users->fetch_assoc()) {
    $user_labels[] = date('M y', strtotime($row['month'] . '-01'));
    $user_counts[] = (int) $row['count'];
}

$trip_status = $conn->query("SELECT status, COUNT(*) as count FROM trips GROUP BY status");
$trip_labels = [];
$trip_counts = [];
while ($row = $trip_status->fetch_assoc()) {
    $trip_labels[] = ucfirst($row['status']);
    $trip_counts[] = (int) $row['count'];
}

$transport_stats = $conn->query("SELECT transport_mode, COUNT(*) as count FROM trips GROUP BY transport_mode ORDER BY count DESC LIMIT 5");
$transport_labels = [];
$transport_counts = [];
while ($row = $transport_stats->fetch_assoc()) {
    $transport_labels[] = ucfirst($row['transport_mode']);
    $transport_counts[] = (int) $row['count'];
}

$verify_stats = $conn->query("SELECT verification_status, COUNT(*) as count FROM users WHERE is_admin = 0 GROUP BY verification_status");
$verify_data = [];
while ($row = $verify_stats->fetch_assoc()) {
    $verify_data[$row['verification_status']] = (int) $row['count'];
}

$online_now = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_online = 1 AND is_blocked = 0")->fetch_assoc()['count'];

$recent_trips = $conn->query("SELECT COUNT(*) as count FROM trips WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];
$recent_joins = $conn->query("SELECT COUNT(*) as count FROM join_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];
$recent_safety = $conn->query("SELECT COUNT(*) as count FROM safety_alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];

// Get today's emergency alerts with user info
$today_emergencies = $conn->query("
    SELECT sa.*, u.full_name, u.email, u.phone
    FROM safety_alerts sa
    JOIN users u ON sa.user_id = u.id
    WHERE sa.alert_type = 'emergency' AND DATE(sa.created_at) = CURDATE()
    ORDER BY sa.created_at DESC
");

// Function to get location name from coordinates
function getLocationName($lat, $lng) {
    if (!$lat || !$lng) return 'Location not available';
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}";
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: SafeTrip Admin Panel\r\n"
        ]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        $parts = [];
        if (!empty($data['address']['city'])) $parts[] = $data['address']['city'];
        elseif (!empty($data['address']['town'])) $parts[] = $data['address']['town'];
        elseif (!empty($data['address']['village'])) $parts[] = $data['address']['village'];
        if (!empty($data['address']['state'])) $parts[] = $data['address']['state'];
        if (!empty($parts)) return implode(', ', $parts);
    }
    return 'Location not available';
}
?>

<div class="admin-page-header">
    <div>
        <span class="admin-page-kicker">SAFE-TRIP / ADMIN</span>
        <h1>Safety moderation & verification dashboard</h1>
        <p>Quick overview of platform integrity and risk signals.</p>
    </div>
    <div class="admin-emergency-box" onclick="showEmergencyAlerts()" style="cursor: pointer;">
        <div class="label">Emergency Today</div>
        <div class="count"><?php echo (int) $total_emergencies; ?></div>
    </div>
</div>

<!-- Emergency Alerts Modal -->
<div id="emergencyModal" class="admin-emergency-modal" style="display: none;">
    <div class="admin-emergency-modal-content">
        <div class="admin-emergency-modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Emergency Alerts Today</h3>
            <button class="admin-emergency-modal-close" onclick="closeEmergencyModal()">&times;</button>
        </div>
        <div class="admin-emergency-modal-body">
            <?php if ($today_emergencies && $today_emergencies->num_rows > 0): ?>
                <?php while($alert = $today_emergencies->fetch_assoc()): ?>
                <div class="admin-emergency-item">
                    <div class="admin-emergency-user">
                        <div class="admin-emergency-avatar"><?php echo strtoupper(substr($alert['full_name'], 0, 1)); ?></div>
                        <div class="admin-emergency-user-info">
                            <h4><?php echo htmlspecialchars($alert['full_name']); ?></h4>
                            <span><?php echo htmlspecialchars($alert['email']); ?></span>
                        </div>
                    </div>
                    <div class="admin-emergency-details">
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Phone</div>
                            <div class="admin-emergency-detail-value"><?php echo htmlspecialchars($alert['phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Time</div>
                            <div class="admin-emergency-detail-value"><?php echo date('h:i A', strtotime($alert['created_at'])); ?></div>
                        </div>
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Date</div>
                            <div class="admin-emergency-detail-value"><?php echo date('d M Y', strtotime($alert['created_at'])); ?></div>
                        </div>
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Location</div>
                            <div class="admin-emergency-detail-value">
                                <?php 
                                $locationName = getLocationName($alert['location_lat'], $alert['location_lng']);
                                echo htmlspecialchars($locationName);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($alert['location_lat'] && $alert['location_lng']): ?>
                    <div class="admin-emergency-location">
                        <a href="https://maps.google.com/?q=<?php echo $alert['location_lat']; ?>,<?php echo $alert['location_lng']; ?>" target="_blank">
                            <i class="fas fa-map-marker-alt"></i> View Location on Map
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="admin-emergency-location">
                        <span style="color: #94a3b8;"><i class="fas fa-map-marker-alt"></i> Location not available</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="admin-empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No emergency alerts today. All safe!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showEmergencyAlerts() {
    document.getElementById('emergencyModal').style.display = 'flex';
}

function closeEmergencyModal() {
    document.getElementById('emergencyModal').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (e.target.id === 'emergencyModal') {
        closeEmergencyModal();
    }
});
</script>

<div class="admin-stats-grid">
    <div class="admin-stat-item">
        <div class="admin-stat-label">Total Users</div>
        <div class="admin-stat-value"><?php echo (int) $total_users; ?></div>
    </div>
    <div class="admin-stat-item">
        <div class="admin-stat-label">Pending Verify</div>
        <div class="admin-stat-value text-warning"><?php echo (int) $pending_verifications; ?></div>
    </div>
    <div class="admin-stat-item">
        <div class="admin-stat-label">Active Trips</div>
        <div class="admin-stat-value text-success"><?php echo (int) $active_trips; ?></div>
    </div>
    <div class="admin-stat-item">
        <div class="admin-stat-label">Pending Reports</div>
        <div class="admin-stat-value text-danger"><?php echo (int) $total_reports; ?></div>
    </div>
    <div class="admin-stat-item">
        <div class="admin-stat-label">Total Trips</div>
        <div class="admin-stat-value"><?php echo (int) $total_trips; ?></div>
    </div>
    <div class="admin-stat-item">
        <div class="admin-stat-label">Online Now</div>
        <div class="admin-stat-value text-info"><?php echo (int) $online_now; ?></div>
    </div>
</div>

<div class="admin-quick-actions">
    <a href="verify_users.php" class="st-card admin-quick-action-card">
        <div class="st-card-body admin-quick-action-body">
            <div class="admin-quick-action-icon admin-icon-teal">
                <i class="fas fa-user-check"></i>
            </div>
            <div>
                <strong class="admin-quick-action-title">Verify Users</strong>
                <p class="admin-quick-action-desc">Approve identity-backed travelers</p>
            </div>
        </div>
    </a>
    <a href="manage_trips.php" class="st-card admin-quick-action-card">
        <div class="st-card-body admin-quick-action-body">
            <div class="admin-quick-action-icon admin-icon-blue">
                <i class="fas fa-map-marked-alt"></i>
            </div>
            <div>
                <strong class="admin-quick-action-title">Manage Trips</strong>
                <p class="admin-quick-action-desc">Inspect live journeys</p>
            </div>
        </div>
    </a>
    <a href="reports.php" class="st-card admin-quick-action-card">
        <div class="st-card-body admin-quick-action-body">
            <div class="admin-quick-action-icon admin-icon-red">
                <i class="fas fa-flag"></i>
            </div>
            <div>
                <strong class="admin-quick-action-title">Reports</strong>
                <p class="admin-quick-action-desc">Process complaints</p>
            </div>
        </div>
    </a>
    <a href="system_monitor.php" class="st-card admin-quick-action-card">
        <div class="st-card-body admin-quick-action-body">
            <div class="admin-quick-action-icon admin-icon-purple">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <strong class="admin-quick-action-title">System Monitor</strong>
                <p class="admin-quick-action-desc">Platform health</p>
            </div>
        </div>
    </a>
</div>

<div class="admin-charts-grid">
    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-users"></i> User Registrations (6 months)</h3>
        </div>
        <div class="st-card-body">
            <div class="admin-bar-chart">
                <?php
                $max_count = max($user_counts) ?: 1;
                foreach ($user_labels as $i => $label):
                    $height = $user_counts[$i] > 0 ? ($user_counts[$i] / $max_count) * 100 : 5;
                ?>
                <div class="admin-bar-item admin-bar-teal" style="height: <?php echo $height; ?>%;">
                    <span class="admin-bar-value"><?php echo $user_counts[$i]; ?></span>
                    <span class="admin-bar-label"><?php echo $label; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-shield-check"></i> Verification Status</h3>
        </div>
        <div class="st-card-body">
            <div class="admin-list-row">
                <span class="admin-list-label">Verified</span>
                <span class="admin-list-value text-success"><?php echo $verify_data['verified'] ?? 0; ?></span>
            </div>
            <div class="admin-list-row">
                <span class="admin-list-label">Pending</span>
                <span class="admin-list-value text-warning"><?php echo $verify_data['pending'] ?? 0; ?></span>
            </div>
            <div class="admin-list-row">
                <span class="admin-list-label">Rejected</span>
                <span class="admin-list-value text-danger"><?php echo $verify_data['rejected'] ?? 0; ?></span>
            </div>
            <div class="admin-list-row">
                <span class="admin-list-label">Online Now</span>
                <span class="admin-list-value text-info"><?php echo (int) $online_now; ?></span>
            </div>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-route"></i> Transport Mode Usage</h3>
        </div>
        <div class="st-card-body">
            <div class="admin-bar-chart">
                <?php
                $max_transport = max($transport_counts) ?: 1;
                foreach ($transport_labels as $i => $label):
                    $height = $transport_counts[$i] > 0 ? ($transport_counts[$i] / $max_transport) * 100 : 5;
                ?>
                <div class="admin-bar-item admin-bar-orange" style="height: <?php echo $height; ?>%;">
                    <span class="admin-bar-value"><?php echo $transport_counts[$i]; ?></span>
                    <span class="admin-bar-label"><?php echo $label; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-clock"></i> Last 24 Hours</h3>
        </div>
        <div class="st-card-body">
            <div class="admin-list-row">
                <span class="admin-list-label">New Trips</span>
                <span class="admin-list-value text-success"><?php echo (int) $recent_trips; ?></span>
            </div>
            <div class="admin-list-row">
                <span class="admin-list-label">Join Requests</span>
                <span class="admin-list-value text-success"><?php echo (int) $recent_joins; ?></span>
            </div>
            <div class="admin-list-row">
                <span class="admin-list-label">Safety Alerts</span>
                <span class="admin-list-value <?php echo $recent_safety > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo (int) $recent_safety; ?></span>
            </div>
            <div class="admin-list-row">
                <span class="admin-list-label">Online Users</span>
                <span class="admin-list-value text-info"><?php echo (int) $online_now; ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>