<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Trip Details';
require_once '../includes/user_header.php';

$user_id = getUserId();
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$trip = $conn->query("
    SELECT t.*, u.full_name, u.trust_score, u.verification_status 
    FROM trips t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = $trip_id
")->fetch_assoc();

if (!$trip) {
    header("Location: browse_trips.php");
    exit();
}
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <a href="browse_trips.php" class="st-btn st-btn-secondary" style="margin-bottom: 0;">
            <i class="fas fa-arrow-left"></i> Back to Browse
        </a>
    </div>
    <h1><i class="fas fa-route"></i> Trip Details</h1>
    <p>View complete information about this trip.</p>
</div>

<div class="st-card">
    <div class="st-card-header">
        <h2><i class="fas fa-route"></i> <?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?></h2>
    </div>
    <div class="st-card-body">
        <div class="map-container" id="routeMap" style="height: 300px; border-radius: 12px; overflow: hidden; margin: 20px 0;"></div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin: 20px 0;">
            <div class="st-info-box">
                <h4 style="color: #0f766e; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Trip Details</h4>
                <p><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo date('l, d M Y', strtotime($trip['travel_date'])); ?></p>
                <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date('h:i A', strtotime($trip['travel_time'])); ?></p>
                <p><i class="fas fa-chair"></i> <strong>Seats:</strong> <?php echo $trip['seats_available']; ?></p>
                <p><i class="fas fa-bus"></i> <strong>Mode:</strong> <?php echo ucfirst($trip['transport_mode']); ?></p>
                <?php if($trip['distance_km'] > 0): ?>
                    <p><i class="fas fa-road"></i> <strong>Distance:</strong> <?php echo $trip['distance_km']; ?> km</p>
                    <p><i class="fas fa-clock"></i> <strong>Duration:</strong> <?php echo $trip['duration_hours']; ?> hours</p>
                <?php endif; ?>
            </div>
            <div class="st-info-box">
                <h4 style="color: #0f766e; margin-bottom: 15px;"><i class="fas fa-user"></i> Traveler Info</h4>
                <p><i class="fas fa-user"></i> <strong>Name:</strong> <?php echo htmlspecialchars($trip['full_name']); ?></p>
                <p><i class="fas fa-star"></i> <strong>Trust Score:</strong> <?php echo $trip['trust_score']; ?>/100</p>
                <?php if($trip['verification_status'] == 'verified'): ?>
                    <p><i class="fas fa-check-circle" style="color: #0f766e;"></i> <strong>Verified User</strong></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if($trip['share_code']): ?>
        <div style="background: #e8f8f5; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #0f766e;">
            <h4 style="margin-bottom: 10px;"><i class="fas fa-share-alt"></i> Share This Trip</h4>
            <p style="color: #667085; margin-bottom: 10px;">Share with friends (no login required):</p>
            <div style="display: flex; gap: 10px;">
                <input type="text" value="<?php echo APP_URL; ?>/share.php?code=<?php echo $trip['share_code']; ?>" readonly style="flex: 1; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px;" id="shareLink">
                <button onclick="copyShareLink()" class="st-btn st-btn-primary"><i class="fas fa-copy"></i> Copy</button>
            </div>
        </div>
        <?php endif; ?>
        
        <h4 style="margin: 25px 0 15px;"><i class="fas fa-ticket-alt"></i> Booking Options</h4>
        <div class="booking-links" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <a href="https://www.irctc.co.in/nget/train-search" target="_blank" class="booking-card" style="background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; text-decoration: none; color: #333; transition: transform 0.3s;">
                <i class="fas fa-train" style="font-size: 1.5rem; color: #0f766e;"></i>
                <h4 style="margin: 10px 0 5px;">Book Train</h4>
                <small>IRCTC</small>
            </a>
            <a href="https://www.redbus.in/" target="_blank" class="booking-card" style="background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; text-decoration: none; color: #333; transition: transform 0.3s;">
                <i class="fas fa-bus" style="font-size: 1.5rem; color: #0f766e;"></i>
                <h4 style="margin: 10px 0 5px;">Book Bus</h4>
                <small>RedBus</small>
            </a>
            <a href="https://www.makemytrip.com/" target="_blank" class="booking-card" style="background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; text-decoration: none; color: #333; transition: transform 0.3s;">
                <i class="fas fa-plane" style="font-size: 1.5rem; color: #0f766e;"></i>
                <h4 style="margin: 10px 0 5px;">Book Flight</h4>
                <small>MakeMyTrip</small>
            </a>
            <a href="https://www.olacabs.com/" target="_blank" class="booking-card" style="background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; text-decoration: none; color: #333; transition: transform 0.3s;">
                <i class="fas fa-taxi" style="font-size: 1.5rem; color: #0f766e;"></i>
                <h4 style="margin: 10px 0 5px;">Book Cab</h4>
                <small>Ola / Uber</small>
            </a>
        </div>
        
        <?php if($trip['user_id'] != $user_id): ?>
            <button onclick="sendJoinRequest(<?php echo $trip['user_id']; ?>, <?php echo $trip_id; ?>)" class="st-btn st-btn-primary" style="width: 100%; margin-top: 25px;">
                <i class="fas fa-handshake"></i> Send Join Request
            </button>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>

<script src="../assets/js/map.js"></script>
<script>
    async function loadMap() {
        const from = "<?php echo $trip['from_location']; ?>";
        const to = "<?php echo $trip['to_location']; ?>";
        const fromCoords = await geocode(from);
        const toCoords = await geocode(to);
        
        if (fromCoords && toCoords) {
            const centerLat = (fromCoords.lat + toCoords.lat) / 2;
            const centerLng = (fromCoords.lng + toCoords.lng) / 2;
            const mapContainer = document.getElementById('routeMap');
            mapContainer.innerHTML = `<iframe width="100%" height="100%" frameborder="0" src="https://www.openstreetmap.org/export/embed.html?bbox=${centerLng-2},${centerLat-2},${centerLng+2},${centerLat+2}&layer=mapnik"></iframe>`;
        }
    }
    
    function sendJoinRequest(toUserId, tripId) {
        if(confirm('Send request to join this trip?')) {
            fetch('../api/send_join_request.php', {
                method: 'POST',
                headers: safeTripJsonHeaders(),
                body: JSON.stringify({ to_user_id: toUserId, trip_id: tripId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Request sent successfully!');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Failed: ' + data.message);
                }
            });
        }
    }
    
    function copyShareLink() {
        const input = document.getElementById('shareLink');
        input.select();
        document.execCommand('copy');
        alert('Link copied!');
    }
    
    loadMap();
</script>