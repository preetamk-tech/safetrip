<?php
$page_title = 'My Trips';
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$user_id = getUserId();

// Cancel trip
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['trip_id'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
        die('Security check failed.');
    }
    $trip_id = (int)$_GET['trip_id'];
    $conn->query("UPDATE trips SET status = 'cancelled' WHERE id = $trip_id AND user_id = $user_id");
    header("Location: my_trips.php");
    exit();
}

// Delete trip
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['trip_id'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf'] ?? '')) {
        die('Security check failed.');
    }
    $trip_id = (int)$_GET['trip_id'];
    // Change status instead of deleting to preserve group
    $conn->query("UPDATE trips SET status = 'cancelled' WHERE id = $trip_id AND user_id = $user_id");
    header("Location: my_trips.php");
    exit();
}

$trips = $conn->query("SELECT * FROM trips WHERE user_id = $user_id ORDER BY travel_date DESC");

require_once '../includes/user_header.php';
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>My Journeys</span>
    </div>
    <h1><i class="fas fa-suitcase-rolling"></i> My Trips</h1>
    <p>Manage all your created and joined trips in one place.</p>
</div>

<div class="st-content-card">
    <div class="st-content-header">
        <h2>Your Trips</h2>
        <a href="create_trip.php" class="st-btn-primary">Create New Trip</a>
    </div>
    
    <?php if ($trips->num_rows > 0): ?>
        <?php while ($trip = $trips->fetch_assoc()): ?>
        <div class="st-trip-item">
            <div style="flex: 1;">
                <div class="st-trip-route"><?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?></div>
                <div class="st-trip-details"><?php echo date('d M Y', strtotime($trip['travel_date'])); ?> • <?php echo ucfirst($trip['transport_mode']); ?> • Status: <?php echo ucfirst($trip['status']); ?></div>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="st-btn-primary" style="padding: 8px 16px;">View</a>
                <?php if ($trip['status'] === 'active'): ?>
                <a href="?action=cancel&trip_id=<?php echo $trip['id']; ?>&csrf=<?php echo htmlspecialchars(csrf_token()); ?>" class="st-btn-warning" style="padding: 8px 16px;" onclick="return confirm('Cancel this trip?')">Cancel</a>
                <?php endif; ?>
                <a href="?action=delete&trip_id=<?php echo $trip['id']; ?>&csrf=<?php echo htmlspecialchars(csrf_token()); ?>" class="st-btn-danger" style="padding: 8px 16px;" onclick="return confirm('Delete this trip permanently?')">Delete</a>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
    <div class="st-empty-state">
        <i class="fas fa-suitcase-rolling"></i>
        <p>No trips created yet</p>
        <a href="create_trip.php" class="st-btn-primary">Create your first trip</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/user_footer.php'; ?>
