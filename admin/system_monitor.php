<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotAdmin();

$page_title = 'System Monitor';
require_once '../includes/admin_header.php';

$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM live_locations WHERE last_updated > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetch_assoc()['count'];
$total_trips = $conn->query("SELECT COUNT(*) as count FROM trips")->fetch_assoc()['count'];
$pending_reports = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch_assoc()['count'];
$total_emergencies = $conn->query("SELECT COUNT(*) as count FROM safety_alerts WHERE alert_type = 'emergency'")->fetch_assoc()['count'];

$recent_alerts = $conn->query("
    SELECT sa.*, u.full_name 
    FROM safety_alerts sa
    JOIN users u ON sa.user_id = u.id
    ORDER BY sa.created_at DESC LIMIT 10
");

$popular_routes = $conn->query("
    SELECT from_location, to_location, COUNT(*) as count
    FROM trips
    GROUP BY from_location, to_location
    ORDER BY count DESC LIMIT 5
");

$route_trips = $conn->query("
    SELECT t.*, u.full_name, u.email, u.phone
    FROM trips t
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'active'
    ORDER BY t.created_at DESC
    LIMIT 20
");

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

<div class="st-page-header">
    <div>
        <span class="st-page-kicker"><i class="fas fa-server"></i> Platform Health</span>
        <h1><i class="fas fa-chart-line"></i> System Monitor</h1>
    </div>
</div>

<div class="admin-verify-stats-grid">
    <div class="st-card">
        <div class="st-card-body admin-stat-card-body">
            <div class="admin-stat-card-value"><?php echo (int) $total_users; ?></div>
            <div class="admin-stat-card-label">Total Users</div>
        </div>
    </div>
    <div class="st-card">
        <div class="st-card-body admin-stat-card-body">
            <div class="admin-stat-card-value text-success"><?php echo (int) $active_users; ?></div>
            <div class="admin-stat-card-label">Active Now (1h)</div>
        </div>
    </div>
    <div class="st-card">
        <div class="st-card-body admin-stat-card-body">
            <div class="admin-stat-card-value"><?php echo (int) $total_trips; ?></div>
            <div class="admin-stat-card-label">Total Trips</div>
        </div>
    </div>
    <div class="st-card">
        <div class="st-card-body admin-stat-card-body">
            <div class="admin-stat-card-value text-danger"><?php echo (int) $pending_reports; ?></div>
            <div class="admin-stat-card-label">Pending Reports</div>
        </div>
    </div>
    <div class="st-card">
        <div class="st-card-body admin-stat-card-body">
            <div class="admin-stat-card-value" style="color: #dc2626;"><?php echo (int) $total_emergencies; ?></div>
            <div class="admin-stat-card-label">Total Emergencies</div>
        </div>
    </div>
</div>

<div class="admin-charts-grid">
    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-bell"></i> Recent Safety Alerts</h3>
        </div>
        <div class="st-card-body" style="padding: 0;">
            <?php if($recent_alerts && $recent_alerts->num_rows > 0): ?>
                <?php 
                $allAlerts = [];
                while($alert = $recent_alerts->fetch_assoc()) {
                    $allAlerts[] = $alert;
                }
                ?>
                <?php foreach($allAlerts as $index => $alert): ?>
                <?php
                $locationName = getLocationName($alert['location_lat'], $alert['location_lng']);
                ?>
                <div class="admin-alert-list" onclick="showAlertDetail(<?php echo $index; ?>)" style="cursor: pointer;">
                    <div>
                        <strong class="admin-alert-name"><?php echo htmlspecialchars($alert['full_name']); ?></strong>
                        <span class="admin-alert-badge" style="background: #ef444422; color: #ef4444;"><?php echo htmlspecialchars($locationName); ?></span>
                    </div>
                    <small class="admin-alert-time"><?php echo date('d M H:i', strtotime($alert['created_at'])); ?></small>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="admin-empty-state" style="padding: 40px;">
                    <i class="fas fa-bell-slash"></i>
                    <p>No recent alerts</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-route"></i> Popular Routes</h3>
        </div>
        <div class="st-card-body" style="padding: 0;">
            <?php if($popular_routes && $popular_routes->num_rows > 0): ?>
                <?php 
                $routeData = [];
                while($route = $popular_routes->fetch_assoc()) {
                    $routeData[] = $route;
                }
                foreach($routeData as $index => $route): 
                ?>
                <div class="admin-route-list" onclick="showRouteDetails(<?php echo $index; ?>)" style="cursor: pointer;">
                    <span class="admin-route-name">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($route['from_location']); ?> → <?php echo htmlspecialchars($route['to_location']); ?>
                    </span>
                    <span class="admin-route-count"><?php echo $route['count']; ?> trips</span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="admin-empty-state" style="padding: 40px;">
                    <p>No route data yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Route Details Modal -->
<div id="routeDetailModal" class="admin-emergency-modal" style="display: none;">
    <div class="admin-emergency-modal-content">
        <div class="admin-emergency-modal-header" style="background: linear-gradient(135deg, #0f766e, #14b8a6);">
            <h3><i class="fas fa-route"></i> Route Details</h3>
            <button class="admin-emergency-modal-close" onclick="closeRouteDetailModal()">&times;</button>
        </div>
        <div class="admin-emergency-modal-body" id="routeDetailBody">
        </div>
    </div>
</div>

<!-- Alert Detail Modal -->
<div id="alertDetailModal" class="admin-emergency-modal" style="display: none;">
    <div class="admin-emergency-modal-content">
        <div class="admin-emergency-modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Alert Details</h3>
            <button class="admin-emergency-modal-close" onclick="closeAlertDetailModal()">&times;</button>
        </div>
        <div class="admin-emergency-modal-body" id="alertDetailBody">
        </div>
    </div>
</div>

<script>
const alertData = <?php echo json_encode($allAlerts ?? []); ?>;
const locations = <?php echo json_encode(array_map(function($a) {
    return getLocationName($a['location_lat'] ?? null, $a['location_lng'] ?? null);
}, $allAlerts ?? [])); ?>;
const routeData = <?php echo json_encode($routeData ?? []); ?>;
const tripData = <?php echo json_encode($route_trips ? $route_trips->fetch_all(MYSQLI_ASSOC) : []); ?>;

function showAlertDetail(index) {
    if (!alertData[index]) return;
    const alert = alertData[index];
    const locationName = locations[index] || 'Location not available';
    
    let locationHtml = '';
    if (alert.location_lat && alert.location_lng) {
        locationHtml = '<div class="admin-emergency-location"><a href="https://maps.google.com/?q=' + alert.location_lat + ',' + alert.location_lng + '" target="_blank"><i class="fas fa-map-marker-alt"></i> View on Map</a></div>';
    }
    
    document.getElementById('alertDetailBody').innerHTML = `
        <div class="admin-emergency-item">
            <div class="admin-emergency-user">
                <div class="admin-emergency-avatar">${alert.full_name.charAt(0).toUpperCase()}</div>
                <div class="admin-emergency-user-info">
                    <h4>${alert.full_name}</h4>
                    <span>Emergency Alert</span>
                </div>
            </div>
            <div class="admin-emergency-details">
                <div class="admin-emergency-detail">
                    <div class="admin-emergency-detail-label">Location</div>
                    <div class="admin-emergency-detail-value">${locationName}</div>
                </div>
                <div class="admin-emergency-detail">
                    <div class="admin-emergency-detail-label">Time</div>
                    <div class="admin-emergency-detail-value">${formatDateTime(alert.created_at)}</div>
                </div>
            </div>
            ${locationHtml}
        </div>
    `;
    document.getElementById('alertDetailModal').style.display = 'flex';
}

function showRouteDetails(index) {
    if (!routeData[index]) return;
    const route = routeData[index];
    
    const matchingTrips = tripData.filter(trip => 
        trip.from_location === route.from_location && 
        trip.to_location === route.to_location
    );
    
    let tripsHtml = '';
    if (matchingTrips.length > 0) {
        matchingTrips.forEach(trip => {
            tripsHtml += `
                <div class="admin-emergency-item" style="border-left-color: #0f766e;">
                    <div class="admin-emergency-user">
                        <div class="admin-emergency-avatar" style="background: #0f766e;">${trip.full_name.charAt(0).toUpperCase()}</div>
                        <div class="admin-emergency-user-info">
                            <h4>${trip.full_name}</h4>
                            <span>${trip.email}</span>
                        </div>
                    </div>
                    <div class="admin-emergency-details">
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Phone</div>
                            <div class="admin-emergency-detail-value">${trip.phone || 'N/A'}</div>
                        </div>
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Travel Date</div>
                            <div class="admin-emergency-detail-value">${formatDateTime(trip.travel_date)}</div>
                        </div>
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Transport</div>
                            <div class="admin-emergency-detail-value">${trip.transport_mode}</div>
                        </div>
                        <div class="admin-emergency-detail">
                            <div class="admin-emergency-detail-label">Status</div>
                            <div class="admin-emergency-detail-value" style="color: #10b981;">${trip.status}</div>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        tripsHtml = '<div class="admin-empty-state"><p>No active trips on this route</p></div>';
    }
    
    document.getElementById('routeDetailBody').innerHTML = `
        <div style="margin-bottom: 20px; padding: 15px; background: #0f172a; border-radius: 12px;">
            <h4 style="color: white; margin: 0 0 10px;">
                <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> 
                ${route.from_location} → ${route.to_location}
            </h4>
            <span style="background: #334155; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; color: #94a3b8;">
                ${route.count} trips on this route
            </span>
        </div>
        <h4 style="color: white; margin-bottom: 15px;">Trip Details</h4>
        ${tripsHtml}
    `;
    document.getElementById('routeDetailModal').style.display = 'flex';
}

function closeRouteDetailModal() {
    document.getElementById('routeDetailModal').style.display = 'none';
}

function closeAlertDetailModal() {
    document.getElementById('alertDetailModal').style.display = 'none';
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('en-IN', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true
    });
}

document.addEventListener('click', function(e) {
    if (e.target.id === 'alertDetailModal') {
        closeAlertDetailModal();
    }
    if (e.target.id === 'routeDetailModal') {
        closeRouteDetailModal();
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>