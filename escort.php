<?php
require_once 'config/database.php';

$escort_code = $_GET['code'] ?? '';

// Check if escort session exists
$session = null;
if ($escort_code) {
    $session = get_escort_session_by_code($escort_code);
    if ($session) {
        increment_escort_views($session['id']);
    }
}

// If no session found or expired
if (!$session) {
    http_response_code(404);
    exit('Escort session not found or has expired.');
}

$time_remaining = max(0, strtotime($session['expires_at']) - time());
$location_age = $session['last_updated'] ? (time() - strtotime($session['last_updated'])) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Trip Tracker - SafeTrip</title>
    <meta name="description" content="Track <?php echo htmlspecialchars($session['full_name']); ?>'s trip from <?php echo htmlspecialchars($session['from_location']); ?> to <?php echo htmlspecialchars($session['to_location']); ?>">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(180deg, #f7f0e3 0%, #fdf9f2 50%, #efe4d1 100%); }
        .escort-container { max-width: 500px; margin: 30px auto; padding: 20px; }
        .escort-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(60, 48, 28, 0.15);
        }
        .escort-header {
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .escort-header h1 { margin: 0 0 10px; font-size: 1.5rem; }
        .escort-header p { margin: 0; opacity: 0.9; }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        .status-live { background: #22c55e; }
        .status-stale { background: #f59e0b; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .trip-info {
            padding: 25px;
        }
        .route-display {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .route-icon { font-size: 2rem; color: #0f766e; }
        .route-text strong { font-size: 1.2rem; display: block; }
        .route-text span { color: #667085; font-size: 0.9rem; }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        .info-item i { font-size: 1.5rem; color: #0f766e; margin-bottom: 5px; }
        .info-item strong { display: block; font-size: 1.1rem; }
        .info-item span { color: #667085; font-size: 0.8rem; }
        .location-status {
            background: #dcfce7;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .location-status.stale { background: #fef3c7; }
        .location-status.inactive { background: #fee2e2; }
        .location-status h3 { margin: 0 0 5px; }
        .location-status p { margin: 0; color: #667085; font-size: 0.85rem; }
        .timer-bar {
            background: #e8f8f5;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }
        .timer-bar strong { color: #0f766e; }
        .view-count {
            text-align: center;
            color: #667085;
            font-size: 0.8rem;
            padding: 10px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #667085;
            font-size: 0.8rem;
        }
        .footer a { color: #0f766e; text-decoration: none; }
        #mapContainer {
            height: 200px;
            background: #e8f8f5;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667085;
        }
        [data-theme="dark"] body { background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%); }
        [data-theme="dark"] .escort-card { background: #334155; }
        [data-theme="dark"] .escort-header { background: linear-gradient(135deg, #0f172a, #1e293b); }
        [data-theme="dark"] .info-item, [data-theme="dark"] .location-status, [data-theme="dark"] .timer-bar { background: rgba(30, 41, 59, 0.9); }
        [data-theme="dark"] .info-item i { color: #14b8a6; }
        [data-theme="dark"] .timer-bar { background: rgba(20, 184, 166, 0.2); }
        [data-theme="dark"] .timer-bar strong { color: #5eead4; }
    </style>
</head>
<body>
    <div class="escort-container">
        <div class="escort-card">
            <div class="escort-header">
                <h1><i class="fas fa-location-crosshairs"></i> Live Trip Tracker</h1>
                <p><span class="status-indicator status-<?php echo $location_age && $location_age < 300 ? 'live' : 'stale'; ?>"></span>
                <?php echo $location_age && $location_age < 300 ? 'Location updating' : 'Location may be outdated'; ?></p>
            </div>

            <div class="trip-info">
                <div class="route-display">
                    <i class="fas fa-route route-icon"></i>
                    <div class="route-text">
                        <strong><?php echo htmlspecialchars($session['from_location']); ?></strong>
                        <i class="fas fa-arrow-down" style="margin: 5px 0; color: #667085;"></i>
                        <span><?php echo htmlspecialchars($session['to_location']); ?></span>
                    </div>
                </div>

                <div id="mapContainer">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem;"></i>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <strong><?php echo date('d M', strtotime($session['travel_date'])); ?></strong>
                        <span>Travel Date</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-<?php echo $session['transport_mode'] === 'flight' ? 'plane' : ($session['transport_mode'] === 'bus' ? 'bus' : 'train'); ?>"></i>
                        <strong><?php echo ucfirst($session['transport_mode']); ?></strong>
                        <span>Via</span>
                    </div>
                </div>

                <div class="location-status <?php echo $location_age && $location_age < 300 ? '' : ($location_age && $location_age < 1800 ? 'stale' : 'inactive'); ?>">
                    <?php if ($session['last_location_lat'] && $session['last_location_lng']): ?>
                    <h3><i class="fas fa-map-pin"></i> Last Known Location</h3>
                    <p>Updated <?php echo $location_age ? ($location_age < 60 ? 'just now' : floor($location_age / 60) . ' min ago') : 'unknown'; ?></p>
                    <?php else: ?>
                    <h3><i class="fas fa-clock"></i> Trip Started</h3>
                    <p>Location sharing will begin when traveler moves</p>
                    <?php endif; ?>
                </div>

                <div class="timer-bar">
                    <i class="fas fa-hourglass-half"></i>
                    Live tracking ends in <strong><?php echo floor($time_remaining / 3600); ?>h <?php echo floor(($time_remaining % 3600) / 60); ?>m</strong>
                </div>
            </div>

            <div class="view-count">
                <i class="fas fa-eye"></i> <?php echo $session['view_count']; ?> people viewing this trip
            </div>

            <div class="footer">
                <p>Being tracked by <a href="index.php">SafeTrip</a></p>
                <p style="margin-top: 10px;"><i class="fas fa-shield-heart"></i> Verified traveler</p>
            </div>
        </div>
    </div>

    <script>
        // Check for dark mode
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        // Auto-refresh location every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>