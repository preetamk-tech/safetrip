<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Favorite Routes';
require_once '../includes/user_header.php';

$user_id = getUserId();

if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $route_id = (int) $_GET['remove'];
    remove_favorite_route($user_id, $route_id);
    header('Location: favorites.php?removed=1');
    exit();
}

$favorites = get_favorite_routes($user_id);
$fav_count = count($favorites);
?>

<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Saved Routes</span>
    </div>
    <h1><i class="fas fa-star"></i> Your Favorite Routes</h1>
    <p>Access your saved travel routes for quick trip creation.</p>
</div>

<?php if (isset($_GET['removed'])): ?>
<div class="st-alert st-alert-success"><i class="fas fa-check-circle"></i> Route removed from favorites.</div>
<?php endif; ?>

<?php if ($fav_count > 0): ?>
<div class="st-card">
    <div class="st-card-header">
        <div class="st-card-header-row">
            <h3><i class="fas fa-route"></i> <?php echo $fav_count; ?> Saved Route<?php echo $fav_count != 1 ? 's' : ''; ?></h3>
            <a href="create_trip.php" class="st-btn st-btn-primary st-btn-sm"><i class="fas fa-plus"></i> Create New Trip</a>
        </div>
    </div>
    <div class="st-card-body">
        <?php while ($fav = array_shift($favorites)): ?>
        <div class="st-list-item fav-item">
            <div class="fav-route">
                <div class="fav-route-header">
                    <i class="fas fa-route fav-icon"></i>
                    <span class="fav-from"><?php echo htmlspecialchars($fav['from_location']); ?></span>
                    <i class="fas fa-arrow-right fav-arrow"></i>
                    <span class="fav-to"><?php echo htmlspecialchars($fav['to_location']); ?></span>
                </div>
                <div class="fav-route-meta">
                    <span><i class="fas fa-<?php echo $fav['transport_mode'] === 'flight' ? 'plane' : ($fav['transport_mode'] === 'bus' ? 'bus' : ($fav['transport_mode'] === 'car' ? 'car' : 'train')); ?>"></i> <?php echo ucfirst($fav['transport_mode']); ?></span>
                    <span><i class="fas fa-redo"></i> Used <?php echo $fav['usage_count']; ?> time<?php echo $fav['usage_count'] != 1 ? 's' : ''; ?></span>
                </div>
            </div>
            <button class="fav-remove-btn" onclick="confirmRemove(<?php echo $fav['id']; ?>)" title="Remove from favorites">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php else: ?>
<div class="st-card">
    <div class="st-card-body st-empty-state">
        <i class="fas fa-heart" style="font-size: 4rem; color: #e5e7eb; margin-bottom: 20px;"></i>
        <h3>No Favorite Routes Yet</h3>
        <p>When you create trips, routes are automatically saved for quick access later.</p>
        <a href="browse_trips.php" class="st-btn st-btn-primary"><i class="fas fa-search"></i> Browse Trips</a>
    </div>
</div>
<?php endif; ?>

<style>
.st-card-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.fav-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
}

.fav-item:last-child {
    border-bottom: none;
}

.fav-route-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.fav-icon {
    color: #0f766e;
    font-size: 1.2rem;
}

.fav-from, .fav-to {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.fav-arrow {
    color: #94a3b8;
}

.fav-route-meta {
    display: flex;
    gap: 15px;
    color: #64748b;
    font-size: 0.85rem;
}

.fav-route-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.fav-route-meta i {
    color: #94a3b8;
}

.fav-remove-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: none;
    background: #fef2f2;
    color: #dc2626;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.fav-remove-btn:hover {
    background: #fee2e2;
    transform: scale(1.05);
}

[data-theme="dark"] .fav-from,
[data-theme="dark"] .fav-to {
    color: #f1f5f9;
}

[data-theme="dark"] .fav-item {
    border-bottom-color: #334155;
}

[data-theme="dark"] .fav-remove-btn {
    background: #334155;
    color: #f87171;
}

[data-theme="dark"] .fav-remove-btn:hover {
    background: #475569;
}
</style>

<script>
function confirmRemove(id) {
    if (confirm('Remove this route from favorites?')) {
        window.location.href = 'favorites.php?remove=' + id;
    }
}
</script>

<?php require_once '../includes/user_footer.php'; ?>