<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$user_id = getUserId();
$user = getUserById($user_id);

$total_trips = $conn->query("SELECT COUNT(*) as count FROM trips WHERE user_id = $user_id")->fetch_assoc()['count'];
$active_trips_count = $conn->query("SELECT COUNT(*) as count FROM trips WHERE user_id = $user_id AND status = 'active' AND travel_date >= CURDATE()")->fetch_assoc()['count'];
$pending_requests_count = $conn->query("SELECT COUNT(*) as count FROM join_requests WHERE to_user_id = $user_id AND status = 'pending'")->fetch_assoc()['count'];
$unread_notifications = getUnreadCount($user_id);
$trust_score = (int) ($user['trust_score'] ?? 70);
$initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 2));

$pending_requests = $conn->query("
    SELECT jr.*, t.from_location, t.to_location, t.travel_date, u.full_name, u.trust_score
    FROM join_requests jr
    LEFT JOIN trips t ON jr.trip_id = t.id
    LEFT JOIN users u ON jr.from_user_id = u.id
    WHERE jr.to_user_id = $user_id AND jr.status = 'pending'
    ORDER BY jr.created_at DESC
    LIMIT 3
");

$my_trips = $conn->query("
    SELECT * FROM trips 
    WHERE user_id = $user_id AND status = 'active' AND travel_date >= CURDATE()
    ORDER BY travel_date ASC
    LIMIT 3
");

$page_title = 'Dashboard';
require_once '../includes/user_header.php';
?>
            <!-- Page Header -->
            <div class="page-header-gradient">
                <div class="page-header-kicker">
                    <span>Control Center</span>
                </div>
                <h1><i class="fas fa-th-large"></i> Your Control Board</h1>
                <p>View active journeys, monitor pending requests, and stay ready with SOS protection.</p>
            </div>

            <!-- Stats Grid -->
            <section class="st-stats-grid">
                <a href="my_trips.php" class="st-stat-card">
                    <div class="st-stat-icon"><i class="fas fa-route"></i></div>
                    <div class="st-stat-info">
                        <strong><?php echo (int) $total_trips; ?></strong>
                        <span>Total Trips</span>
                    </div>
                </a>
                <a href="my_trips.php" class="st-stat-card">
                    <div class="st-stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="st-stat-info">
                        <strong><?php echo (int) $active_trips_count; ?></strong>
                        <span>Upcoming Trips</span>
                    </div>
                </a>
                <a href="notifications.php" class="st-stat-card">
                    <div class="st-stat-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="st-stat-info">
                        <strong><?php echo (int) $pending_requests_count; ?></strong>
                        <span>Pending Requests</span>
                    </div>
                </a>
                <a href="notifications.php" class="st-stat-card">
                    <div class="st-stat-icon"><i class="fas fa-bell"></i></div>
                    <div class="st-stat-info">
                        <strong><?php echo (int) $unread_notifications; ?></strong>
                        <span>Unread Notifications</span>
                    </div>
                </a>
            </section>

            <!-- Content Grid -->
            <section class="st-content-grid">
                <!-- Join Requests -->
                <div class="st-content-card">
                    <div class="st-content-header">
                        <h2>Join Requests Received</h2>
                        <?php if ($pending_requests_count > 0): ?>
                        <span class="st-content-badge"><?php echo (int) $pending_requests_count; ?> new</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                        <?php while ($req = $pending_requests->fetch_assoc()): ?>
                        <?php 
                            $fromLoc = $req['from_location'] ?? 'Unknown';
                            $toLoc = $req['to_location'] ?? 'Unknown';
                            $name = $req['full_name'] ?? 'Unknown User';
                            $trust = (int) ($req['trust_score'] ?? 0);
                        ?>
                        <div class="st-trip-item">
                            <div>
                                <div class="st-trip-route"><?php echo htmlspecialchars($fromLoc); ?> → <?php echo htmlspecialchars($toLoc); ?></div>
                                <div class="st-trip-details"><?php echo htmlspecialchars($name); ?> • Trust: <?php echo $trust; ?>/100</div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="st-empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No pending join requests</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- My Active Trips -->
                <div class="st-content-card">
                    <div class="st-content-header">
                        <h2>My Active Trips</h2>
                    </div>
                    <?php if ($my_trips && $my_trips->num_rows > 0): ?>
                        <?php while ($trip = $my_trips->fetch_assoc()): ?>
                        <div class="st-trip-item">
                            <div>
                                <div class="st-trip-route"><?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?></div>
                                <div class="st-trip-details"><?php echo date('d M Y', strtotime($trip['travel_date'])); ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="st-empty-state">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>No active trips</p>
                        <a href="create_trip.php" class="st-btn-primary">Create one now</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Groups Joined -->
                <div class="st-content-card">
                    <div class="st-content-header">
                        <h2>Groups You Joined</h2>
                    </div>
                    <div class="st-empty-state">
                        <i class="fas fa-users"></i>
                        <p>No groups joined yet</p>
                    </div>
                </div>

                <!-- Travelers on Same Route -->
                <div class="st-content-card">
                    <div class="st-content-header">
                        <h2>Travelers on Same Route</h2>
                    </div>
                    <div class="st-empty-state">
                        <i class="fas fa-compass"></i>
                        <p>No travelers found</p>
                        <a href="browse_trips.php" class="st-btn-primary">Browse Trips</a>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>