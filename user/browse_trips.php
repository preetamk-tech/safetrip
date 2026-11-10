<?php
$page_title = 'Browse Trips';
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$user_id = getUserId();
$user = getUserById($user_id);
$filter_from = isset($_GET['from']) ? $_GET['from'] : '';
$filter_to = isset($_GET['to']) ? $_GET['to'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_women_only = isset($_GET['women_only']) && $_GET['women_only'] == '1';

$query = "SELECT t.*, u.full_name, u.trust_score, u.verification_status, u.gender
          FROM trips t
          JOIN users u ON t.user_id = u.id
          WHERE t.user_id != $user_id
          AND t.status = 'active'
          AND t.travel_date >= CURDATE()
          AND u.verification_status = 'verified'
          AND u.is_blocked = 0";

if ($user['gender'] === 'female' && $filter_women_only) {
    $query .= " AND t.women_only_trip = 1";
}

if ($filter_from) {
    $query .= " AND t.from_location LIKE '%$filter_from%'";
}
if ($filter_to) {
    $query .= " AND t.to_location LIKE '%$filter_to%'";
}
if ($filter_date) {
    $query .= " AND t.travel_date = '$filter_date'";
}

$query .= " ORDER BY t.travel_date ASC";
$trips = $conn->query($query);

require_once '../includes/user_header.php';
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Discover</span>
    </div>
    <h1><i class="fas fa-compass"></i> Browse Available Trips</h1>
    <p>Find verified travelers going your way. All trips are from verified users.</p>
</div>

<div class="st-content-grid" style="grid-template-columns: 280px 1fr;">
    <!-- Search Filters -->
    <div class="st-content-card" style="height: fit-content;">
        <h2 style="margin-bottom: 16px;">Filter Trips</h2>
        
        <form method="GET" action="browse_trips.php">
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">From</label>
                <input type="text" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="input-full" placeholder="City">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">To</label>
                <input type="text" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="input-full" placeholder="City">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 500;">Date</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" class="input-full">
            </div>
            
            <button type="submit" class="st-btn-primary" style="width: 100%;">Search</button>
        </form>
    </div>

    <!-- Results -->
    <div class="st-content-card">
        <div class="st-content-header">
            <h2>Available Trips</h2>
            <span class="st-content-badge"><?php echo $trips->num_rows; ?> found</span>
        </div>
        
        <?php if ($trips->num_rows > 0): ?>
            <?php while ($trip = $trips->fetch_assoc()): ?>
            <div class="st-trip-item">
                <div style="flex: 1;">
                    <div class="st-trip-route"><?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?></div>
                    <div class="st-trip-details"><?php echo date('d M Y', strtotime($trip['travel_date'])); ?> • <?php echo htmlspecialchars($trip['full_name']); ?> • Trust: <?php echo (int)$trip['trust_score']; ?>/100</div>
                </div>
                <button onclick="joinTrip(<?php echo $trip['id']; ?>)" class="st-btn-primary" style="padding: 8px 16px;">Join</button>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="st-empty-state">
            <i class="fas fa-search"></i>
            <p>No trips found matching your criteria</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function joinTrip(tripId) {
    if(!confirm('Send join request to this trip?')) return;
    
    const csrfToken = window.SafeTrip?.csrfToken || '';
    
    fetch('../api/send_join_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({trip_id: tripId})
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('Join request sent!');
            location.reload();
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(err => {
        alert('Error: ' + err);
    });
}
</script>

<?php require_once '../includes/user_footer.php'; ?>