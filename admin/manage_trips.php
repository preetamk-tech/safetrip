<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotAdmin();

$page_title = 'Manage Trips';
require_once '../includes/admin_header.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $trip_id = (int)$_GET['id'];
    $conn->query("DELETE FROM trips WHERE id = $trip_id");
    header("Location: manage_trips.php");
    exit();
}

$trips = $conn->query("SELECT t.*, u.full_name, u.email FROM trips t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC");
?>

<div class="st-page-header">
    <div>
        <span class="st-page-kicker"><i class="fas fa-map-marked-alt"></i> Trip Oversight</span>
        <h1><i class="fas fa-map-marked-alt"></i> Manage All Trips</h1>
        <p>Audit every trip on the platform.</p>
    </div>
</div>

<div class="st-card">
    <div class="st-card-header">
        <h3><i class="fas fa-route"></i> All Trips (<?php echo $trips ? $trips->num_rows : 0; ?>)</h3>
    </div>
    <div class="st-card-body admin-table-wrapper">
        <?php if ($trips && $trips->num_rows > 0): ?>
        <table class="st-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th style="width: 80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($trip = $trips->fetch_assoc()): ?>
                <?php
                $status_colors = ['active' => '#10b981', 'completed' => '#3b82f6', 'cancelled' => '#ef4444'];
                $status_color = $status_colors[$trip['status']] ?? '#94a3b8';
                ?>
                <tr>
                    <td><strong>#<?php echo $trip['id']; ?></strong></td>
                    <td>
                        <div><strong><?php echo htmlspecialchars($trip['full_name']); ?></strong></div>
                        <small class="admin-user-email"><?php echo htmlspecialchars($trip['email']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($trip['from_location']); ?></td>
                    <td><?php echo htmlspecialchars($trip['to_location']); ?></td>
                    <td><?php echo date('d M Y', strtotime($trip['travel_date'])); ?></td>
                    <td><span class="admin-badge"><?php echo ucfirst($trip['transport_mode']); ?></span></td>
                    <td>
                        <span class="admin-status-badge" style="background: <?php echo $status_color; ?>22; color: <?php echo $status_color; ?>;">
                            <?php echo ucfirst($trip['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="?action=delete&id=<?php echo $trip['id']; ?>" class="st-btn st-btn-danger st-btn-sm" onclick="return confirm('Delete this trip?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="admin-empty-state">
            <i class="fas fa-route"></i>
            <p>No trips found.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>