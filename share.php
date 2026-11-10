<?php
require_once 'config/database.php';

$share_code = $_GET['code'] ?? '';

// Check if shared trip exists
$trip = null;
if ($share_code) {
    $trip = get_trip_by_share_code($share_code);
    if ($trip) {
        increment_share_view($share_code);
    }
}

// If no trip found or user profile is private, show error
if (!$trip) {
    http_response_code(404);
    exit('Trip not found or has been removed.');
}

$error = '';

// If not logged in user tries to send join request, save the trip they'd like
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_join'])) {
    $_SESSION['pending_trip_request'] = [
        'trip_id' => $trip['id'],
        'to_user_id' => $trip['user_id']
    ];
    header('Location: index.php?show_login=1');
    exit();
}

$share_link = generate_shareable_link($trip['share_code']);
$user_status = [
    'is_online' => $trip['is_online'] ?? 0,
    'last_seen' => $trip['last_seen'] ?? null
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip to <?php echo htmlspecialchars($trip['to_location']); ?> - SafeTrip</title>
    <meta name="description" content="<?php echo htmlspecialchars($trip['full_name']); ?> is traveling from <?php echo htmlspecialchars($trip['from_location']); ?> to <?php echo htmlspecialchars($trip['to_location']); ?> on <?php echo date('d M Y', strtotime($trip['travel_date'])); ?>. Join them!">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        .share-container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .trip-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .trip-route { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .trip-route i { font-size: 2rem; color: #27ae60; }
        .trip-route h2 { margin: 0; }
        .trip-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .trip-info p { margin: 10px 0; color: #666; }
        .trip-info strong { color: #333; }
        .traveler-card { background: #e8f8f5; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .btn-join { width: 100%; padding: 15px; font-size: 1.1rem; margin-top: 20px; }
        .verify-badge { color: #27ae60; font-weight: bold; }
        .share-actions { display: flex; gap: 10px; margin-top: 15px; }
        .share-actions button { flex: 1; padding: 12px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; }
        .share-wa { background: #25D366; color: white; }
        .share-copy { background: #3498db; color: white; }
        .safety-badges { display: flex; gap: 8px; flex-wrap: wrap; margin: 15px 0; }
        .safety-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-verified { background: #dcfce7; color: #166534; }
        .badge-women { background: #fce7f3; color: #9d174d; }
        .badge-escort { background: #dbeafe; color: #1e40af; }
        .views-count { color: #666; font-size: 0.85rem; margin-top: 10px; }
        [data-theme="dark"] body { background: #1e293b; }
        [data-theme="dark"] .trip-card { background: #334155; }
        [data-theme="dark"] .trip-info p, [data-theme="dark"] .traveler-card, [data-theme="dark"] .views-count { color: #cbd5e1; }
        [data-theme="dark"] .traveler-card { background: #1e293b; }
    </style>
</head>
<body>
    <div class="share-container">
        <div class="trip-card">
            <div style="text-align: center; margin-bottom: 20px;">
                <a href="index.php" style="text-decoration: none; color: #0f766e;">
                    <i class="fas fa-shield-heart" style="font-size: 2rem;"></i>
                    <span style="font-size: 1.2rem; font-weight: 700;">SafeTrip</span>
                </a>
            </div>

            <div class="trip-route">
                <i class="fas fa-route"></i>
                <div>
                    <h2 style="color: #0f766e;"><?php echo htmlspecialchars($trip['from_location']); ?></h2>
                    <i class="fas fa-arrow-down" style="color: #667085;"></i>
                    <h2 style="color: #f97316;"><?php echo htmlspecialchars($trip['to_location']); ?></h2>
                </div>
            </div>

            <div class="safety-badges">
                <?php if($trip['verification_status'] == 'verified'): ?>
                <span class="safety-badge badge-verified"><i class="fas fa-check-circle"></i> Verified</span>
                <?php endif; ?>
                <?php if(!empty($trip['women_only_trip'])): ?>
                <span class="safety-badge badge-women"><i class="fas fa-venus"></i> Women Only</span>
                <?php endif; ?>
                <?php if(!empty($trip['escort_enabled'])): ?>
                <span class="safety-badge badge-escort"><i class="fas fa-share-nodes"></i> Escort Mode</span>
                <?php endif; ?>
            </div>

            <div class="trip-info">
                <div>
                    <p><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo date('d M Y', strtotime($trip['travel_date'])); ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date('h:i A', strtotime($trip['travel_time'])); ?></p>
                    <p><i class="fas fa-chair"></i> <strong>Seats:</strong> <?php echo $trip['seats_available']; ?></p>
                </div>
                <div>
                    <p><i class="fas fa-<?php echo $trip['transport_mode'] === 'flight' ? 'plane' : ($trip['transport_mode'] === 'bus' ? 'bus' : ($trip['transport_mode'] === 'car' ? 'car' : 'train')); ?>"></i> <strong>Via:</strong> <?php echo ucfirst($trip['transport_mode']); ?></p>
                    <p><i class="fas fa-star"></i> <strong>Trust:</strong> <?php echo $trip['trust_score']; ?>/100</p>
                </div>
            </div>

            <div class="traveler-card">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #17383b, #0f766e); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0;"><?php echo htmlspecialchars($trip['full_name']); ?></h3>
                        <?php echo format_online_status($user_status); ?>
                    </div>
                </div>
            </div>

            <div class="share-actions">
                <button class="share-wa" onclick="shareWhatsApp()">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                <button class="share-copy" onclick="copyLink()">
                    <i class="fas fa-copy"></i> Copy Link
                </button>
            </div>

            <p class="views-count"><i class="fas fa-eye"></i> <?php echo $trip['share_view_count']; ?> views</p>

            <form method="POST">
                <input type="hidden" name="request_join" value="1">
                <button type="submit" class="btn btn-primary btn-join">
                    <i class="fas fa-handshake"></i> Request to Join This Trip
                </button>
            </form>

            <p style="text-align:center; margin-top:20px; color:#666;">
                <small>Powered by <a href="index.php">SafeTrip</a> - Verified Travel Community</small>
            </p>
        </div>
    </div>

    <script>
        const shareLink = '<?php echo $share_link; ?>';
        const tripInfo = '<?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?> on <?php echo date('d M Y', strtotime($trip['travel_date'])); ?>';

        function shareWhatsApp() {
            const msg = encodeURIComponent('Hey! Check out this trip on SafeTrip: ' + tripInfo + '\n\n' + shareLink);
            window.open('https://wa.me/?text=' + msg, '_blank');
        }

        function copyLink() {
            navigator.clipboard.writeText(shareLink).then(() => {
                alert('Link copied! Share it anywhere.');
            });
        }

        // Check for dark mode
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</body>
</html>