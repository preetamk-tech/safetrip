<?php
require_once '../config/database.php';
require_once '../includes/session.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$filter_from = isset($_GET['from']) ? $_GET['from'] : '';
$filter_to = isset($_GET['to']) ? $_GET['to'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_women_only = isset($_GET['women_only']) && $_GET['women_only'] == '1';

// Get current user gender
$user = getUserById($user_id);

// Build query
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

// Output HTML
if ($trips && $trips->num_rows > 0) {
    while($trip = $trips->fetch_assoc()) { ?>
        <div class="trip-card-large">
            <div class="trip-info">
                <div class="trip-route" style="font-size:1.2rem;">
                    <i class="fas fa-route"></i> <?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?>
                    <?php if (!empty($trip['women_only_trip'])): ?>
                    <span style="background: #fce7f3; color: #9d174d; padding: 2px 10px; border-radius: 999px; font-size: 0.7rem; margin-left: 10px;"><i class="fas fa-venus"></i> Women Only</span>
                    <?php endif; ?>
                </div>
                <div class="trip-details">
                    <i class="fas fa-calendar"></i> <?php echo date('l, d M Y', strtotime($trip['travel_date'])); ?> |
                    <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($trip['travel_time'])); ?> |
                    <i class="fas fa-chair"></i> <?php echo $trip['seats_available']; ?> seats |
                    <i class="fas fa-<?php echo $trip['transport_mode'] === 'flight' ? 'plane' : ($trip['transport_mode'] === 'bus' ? 'bus' : ($trip['transport_mode'] === 'car' ? 'car' : 'train')); ?>"></i> <?php echo ucfirst($trip['transport_mode']); ?>
                </div>
            </div>
            <div class="trip-owner">
                <div>
                    <strong><?php echo htmlspecialchars($trip['full_name']); ?></strong>
                    <?php if ($trip['gender'] === 'female'): ?>
                    <i class="fas fa-venus" style="color: #ec4899; margin-left: 5px;" title="Female traveler"></i>
                    <?php endif; ?>
                </div>
                <div><span class="trust-badge">⭐ <?php echo $trip['trust_score']; ?></span></div>
                <div><span class="verified-badge">✓ Verified</span></div>
                <button onclick="sendJoinRequest(<?php echo $trip['user_id']; ?>, <?php echo $trip['id']; ?>)" class="btn btn-primary btn-sm">
                    <i class="fas fa-handshake"></i> Join Trip
                </button>
            </div>
        </div>
    <?php }
} else { ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No trips found matching your criteria. <a href="create_trip.php">Create your own trip!</a>
    </div>
<?php }