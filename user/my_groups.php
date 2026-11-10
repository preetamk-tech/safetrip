<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'My Groups';
require_once '../includes/user_header.php';

$user_id = getUserId();

// Get all groups user is a member of
$groups = $conn->query("
    SELECT tg.*, t.from_location, t.to_location, t.travel_date, t.status as trip_status,
           (SELECT COUNT(*) FROM group_members WHERE group_id = tg.id) as member_count
    FROM group_members gm
    JOIN travel_groups tg ON gm.group_id = tg.id
    JOIN trips t ON tg.trip_id = t.id
    WHERE gm.user_id = $user_id
    ORDER BY gm.joined_at DESC
");
?>

<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Social</span>
    </div>
    <h1><i class="fas fa-users"></i> My Groups</h1>
    <p>Manage your travel groups and chat with trip companions.</p>
</div>

<?php if ($groups && $groups->num_rows > 0): ?>
<div class="st-alert st-alert-info" style="margin-bottom: 20px;">
    <i class="fas fa-users"></i> You have <?php echo $groups->num_rows; ?> group<?php echo $groups->num_rows != 1 ? 's' : ''; ?>
</div>

<div class="groups-grid">
    <?php while($grp = $groups->fetch_assoc()): ?>
    <a href="groups.php?group_id=<?php echo $grp['id']; ?>" class="group-card-link">
        <div class="group-card">
            <div class="group-card-header">
                <div class="group-card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="group-card-info">
                    <h3><?php echo htmlspecialchars($grp['name'] ?? ($grp['from_location'] . ' to ' . $grp['to_location'])); ?></h3>
                    <span class="group-code"><i class="fas fa-code"></i> <?php echo $grp['group_code']; ?></span>
                </div>
            </div>
            <div class="group-card-details">
                <div class="group-detail">
                    <i class="fas fa-route"></i>
                    <?php echo htmlspecialchars($grp['from_location']); ?> → <?php echo htmlspecialchars($grp['to_location']); ?>
                </div>
                <div class="group-detail">
                    <i class="fas fa-calendar"></i>
                    <?php echo date('d M Y', strtotime($grp['travel_date'])); ?>
                </div>
                <div class="group-detail">
                    <i class="fas fa-user-friends"></i>
                    <?php echo $grp['member_count']; ?> member<?php echo $grp['member_count'] != 1 ? 's' : ''; ?>
                </div>
            </div>
            <?php if($grp['trip_status'] != 'active'): ?>
            <div class="group-card-status <?php echo $grp['trip_status']; ?>">
                <?php echo ucfirst($grp['trip_status']); ?>
            </div>
            <?php endif; ?>
        </div>
    </a>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="st-card">
    <div class="st-card-body st-empty-state">
        <i class="fas fa-users-slash" style="font-size: 4rem; color: #e5e7eb; margin-bottom: 20px;"></i>
        <h3>No Groups Yet</h3>
        <p>Join a trip or accept a join request to create a group.</p>
        <a href="browse_trips.php" class="st-btn st-btn-primary"><i class="fas fa-search"></i> Browse Trips</a>
    </div>
</div>
<?php endif; ?>

<style>
.groups-wrapper {
    padding: 24px;
    width: 100%;
    box-sizing: border-box;
}

.groups-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 12px;
}

.groups-header-left {
    display: flex;
    flex-direction: column;
}

.groups-kicker {
    font-size: 0.8rem;
    color: #0f766e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.groups-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.groups-header h1 i {
    margin-right: 8px;
}

.groups-count {
    background: #fef3c7;
    color: #b45309;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.groups-desc {
    color: #64748b;
    margin: 0 0 24px;
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.group-card-link {
    text-decoration: none;
}

.group-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    transition: all 0.2s;
    position: relative;
}

.group-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.group-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.group-card-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.group-card-icon i {
    color: white;
    font-size: 1.3rem;
}

.group-card-info h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 6px;
}

.group-code {
    background: #f1f5f9;
    color: #475569;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-family: monospace;
}

.group-card-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-detail {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 0.9rem;
}

.group-detail i {
    color: #94a3b8;
    width: 16px;
}

.group-card-status {
    position: absolute;
    top: 16px;
    right: 16px;
    background: #fef2f2;
    color: #dc2626;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.group-card-status.completed {
    background: #f0fdf4;
    color: #16a34a;
}

.groups-empty-card {
    text-align: center;
    background: white;
    border-radius: 16px;
    padding: 60px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.groups-empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.groups-empty-icon i {
    font-size: 2rem;
    color: #94a3b8;
}

.groups-empty-card h3 {
    color: #475569;
    margin: 0 0 8px;
}

.groups-empty-card p {
    color: #94a3b8;
    margin: 0 0 20px;
}

.groups-browse-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #0f766e;
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s;
}

.groups-browse-btn:hover {
    background: #0d9488;
}

[data-theme="dark"] .groups-header h1,
[data-theme="dark"] .group-card-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .group-card {
    background: #1e293b;
}

[data-theme="dark"] .group-card:hover {
    background: #263344;
}

[data-theme="dark"] .group-code {
    background: #334155;
    color: #cbd5e1;
}

[data-theme="dark"] .groups-empty-card {
    background: #1e293b;
}

[data-theme="dark"] .groups-empty-icon {
    background: #334155;
}

[data-theme="dark"] .groups-empty-card h3 {
    color: #94a3b8;
}
</style>

<?php require_once '../includes/user_footer.php'; ?>