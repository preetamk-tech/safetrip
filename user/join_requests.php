<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Join Requests';
$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action'], $_POST['request_id'])) {
    verify_csrf_or_abort();

    $request_id = (int) $_POST['request_id'];
    $action = $_POST['request_action'];

    $requestStmt = $conn->prepare("SELECT * FROM join_requests WHERE id = ? AND to_user_id = ? AND status = 'pending' LIMIT 1");
    $requestStmt->bind_param('ii', $request_id, $user_id);
    $requestStmt->execute();
    $request = $requestStmt->get_result()->fetch_assoc();

    if ($request && $action === 'accept') {
        $trip_id = (int) $request['trip_id'];
        $requesterId = (int) $request['from_user_id'];
        
        $tripInfo = $conn->query("SELECT * FROM trips WHERE id = $trip_id")->fetch_assoc();
        $trip_name = $tripInfo ? ($tripInfo['from_location'] . ' to ' . $tripInfo['to_location']) : 'Trip Group';
        
        $groupResult = $conn->query("SELECT id FROM travel_groups WHERE trip_id = $trip_id");
        
        if ($groupResult && $groupResult->num_rows === 0) {
            $group_code = generateGroupCode();
            
            // Check if name column exists
            $colCheck = $conn->query("SHOW COLUMNS FROM travel_groups LIKE 'name'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $conn->query("INSERT INTO travel_groups (trip_id, group_code, created_by, name) VALUES ($trip_id, '$group_code', $user_id, '$trip_name')");
            } else {
                $conn->query("INSERT INTO travel_groups (trip_id, group_code, created_by) VALUES ($trip_id, '$group_code', $user_id)");
            }
            
            $group_id = $conn->insert_id;
        } else {
            $group = $groupResult->fetch_assoc();
            $group_id = (int) $group['id'];
        }

        if ($group_id > 0) {
            // Add trip owner (creator) as member
            $conn->query("INSERT IGNORE INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)");
            // Add requester as member  
            $conn->query("INSERT IGNORE INTO group_members (group_id, user_id) VALUES ($group_id, $requesterId)");
            
            // Update join request status
            $conn->query("UPDATE join_requests SET status = 'accepted' WHERE id = $request_id");

            // Send notification
            sendNotification($requesterId, "Join Request Accepted", $_SESSION['user_name'] . " accepted your join request!", "request_accepted", $group_id);

            header("Location: groups.php?group_id=$group_id");
            exit();
        }
    }

    if ($request && $action === 'reject') {
        $rejectStmt = $conn->prepare("UPDATE join_requests SET status = 'rejected' WHERE id = ? AND to_user_id = ?");
        $rejectStmt->bind_param('ii', $request_id, $user_id);
        $rejectStmt->execute();
    }

    header("Location: join_requests.php");
    exit();
}

require_once '../includes/user_header.php';

$requests = $conn->query("
    SELECT jr.*, u.full_name, u.trust_score, u.verification_status, t.from_location, t.to_location, t.travel_date
    FROM join_requests jr
    LEFT JOIN users u ON jr.from_user_id = u.id
    LEFT JOIN trips t ON jr.trip_id = t.id
    WHERE jr.to_user_id = $user_id AND jr.status = 'pending'
    ORDER BY jr.created_at DESC
");
?>

<div class="page-header-gradient">
    <div class="page-header-kicker">
        <a href="dashboard.php" class="join-back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <h1><i class="fas fa-user-plus"></i> Join Requests</h1>
    <p>Review and manage pending requests to join your trips.</p>
</div>

<?php if ($requests && $requests->num_rows > 0): ?>
<div class="join-request-card">
    <?php while($req = $requests->fetch_assoc()): ?>
    <div class="join-request-item">
        <div class="join-request-user">
            <div class="join-request-avatar">
                <?php echo strtoupper(substr($req['full_name'], 0, 1)); ?>
            </div>
            <div class="join-request-user-info">
                <div class="join-request-name-row">
                    <h4 class="join-request-name"><?php echo htmlspecialchars($req['full_name']); ?></h4>
                    <?php if($req['verification_status'] == 'verified'): ?>
                        <span class="join-request-verified"><i class="fas fa-check-circle"></i> Verified</span>
                    <?php endif; ?>
                </div>
                <div class="join-request-trust">
                    <i class="fas fa-star"></i> Trust Score: <strong><?php echo (int)$req['trust_score']; ?>/100</strong>
                </div>
            </div>
        </div>
        
        <div class="join-request-trip">
            <div class="join-request-trip-route">
                <i class="fas fa-route"></i>
                <span><?php echo htmlspecialchars($req['from_location']); ?></span>
                <i class="fas fa-arrow-right"></i>
                <span><?php echo htmlspecialchars($req['to_location']); ?></span>
            </div>
            <div class="join-request-trip-date">
                <i class="fas fa-calendar"></i>
                <?php echo date('d M Y', strtotime($req['travel_date'])); ?>
            </div>
        </div>
        
        <div class="join-request-actions">
            <form method="POST" class="join-action-form">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                <button type="submit" name="request_action" value="accept" class="join-btn join-btn-accept" onclick="return confirm('Accept this request?')">
                    <i class="fas fa-check"></i>
                    <span>Accept</span>
                </button>
            </form>
            <form method="POST" class="join-action-form">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="request_id" value="<?php echo (int) $req['id']; ?>">
                <button type="submit" name="request_action" value="reject" class="join-btn join-btn-reject" onclick="return confirm('Reject this request?')">
                    <i class="fas fa-times"></i>
                    <span>Reject</span>
                </button>
            </form>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="st-card">
    <div class="st-card-body st-empty-state">
        <i class="fas fa-inbox" style="font-size: 4rem; color: #e5e7eb; margin-bottom: 20px;"></i>
        <h3>No Pending Requests</h3>
        <p>Join requests from other travelers will appear here.</p>
    </div>
</div>
<?php endif; ?>

<style>
.join-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
    margin-bottom: 8px;
}

.join-back-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.join-request-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
}

.join-request-item {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px;
    border-bottom: 1px solid #f1f5f9;
}

.join-request-item:last-child {
    border-bottom: none;
}

.join-request-user {
    display: flex;
    align-items: center;
    gap: 16px;
}

.join-request-avatar {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.join-request-user-info {
    flex: 1;
}

.join-request-name-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.join-request-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.join-request-verified {
    background: #f0fdf4;
    color: #16a34a;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

.join-request-verified i {
    margin-right: 4px;
}

.join-request-trust {
    color: #64748b;
    font-size: 0.9rem;
}

.join-request-trust i {
    color: #f59e0b;
}

.join-request-trip {
    background: #f8fafc;
    padding: 16px;
    border-radius: 12px;
}

.join-request-trip-route {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1e293b;
    font-weight: 500;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.join-request-trip-route i {
    color: #0f766e;
}

.join-request-trip-route .fa-arrow-right {
    color: #94a3b8;
}

.join-request-trip-date {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 0.85rem;
}

.join-request-trip-date i {
    color: #94a3b8;
}

.join-request-actions {
    display: flex;
    gap: 12px;
}

.join-action-form {
    flex: 1;
}

.join-btn {
    width: 100%;
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.join-btn i {
    font-size: 1rem;
}

.join-btn-accept {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.join-btn-accept:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.join-btn-reject {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.join-btn-reject:hover {
    background: #fee2e2;
}

.join-request-empty {
    text-align: center;
    padding: 60px 20px;
}

.join-request-empty i {
    font-size: 4rem;
    color: #e5e7eb;
    margin-bottom: 20px;
}

.join-request-empty h3 {
    color: #475569;
    margin: 0 0 10px;
}

.join-request-empty p {
    color: #94a3b8;
    margin: 0;
}

@media (min-width: 768px) {
    .join-request-item {
        flex-direction: row;
        align-items: center;
    }
    
    .join-request-user {
        flex: 1;
        min-width: 200px;
    }
    
    .join-request-trip {
        flex: 1;
        min-width: 200px;
    }
    
    .join-request-actions {
        flex: none;
    }
    
    .join-action-form {
        flex: none;
    }
}

[data-theme="dark"] .join-requests-header h1,
[data-theme="dark"] .join-request-name,
[data-theme="dark"] .join-request-trip-route {
    color: #f1f5f9;
}

[data-theme="dark"] .join-request-card {
    background: #1e293b;
}

[data-theme="dark"] .join-request-item {
    border-bottom-color: #334155;
}

[data-theme="dark"] .join-request-trip {
    background: #334155;
}

[data-theme="dark"] .join-request-empty h3 {
    color: #94a3b8;
}

[data-theme="dark"] .join-back-btn {
    background: #334155;
    color: #cbd5e1;
}

[data-theme="dark"] .join-back-btn:hover {
    background: #475569;
}
</style>

<?php require_once '../includes/user_footer.php'; ?>