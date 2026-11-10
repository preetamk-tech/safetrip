<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotAdmin();

$page_title = 'Verify Users';
require_once '../includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'], $_POST['user_id'])) {
    verify_csrf_or_abort();
    $user_id = (int) $_POST['user_id'];
    $action = $_POST['verify'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'verified', trust_score = trust_score + 20 WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = getUserById($user_id);
        sendNotification($user_id, "Account Verified", "Your account has been verified! You can now create and join trips.", "system");
        sendEmail($user['email'], "Account Verified - SafeTrip", "Congratulations! Your account has been verified. Start your safe journey with SafeTrip.");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'rejected' WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }
    header("Location: verify_users.php");
    exit();
}

$pending_users = $conn->query("SELECT * FROM users WHERE verification_status = 'pending' AND is_admin = 0 ORDER BY created_at ASC");
?>

<div class="st-page-header">
    <div>
        <span class="st-page-kicker"><i class="fas fa-id-card"></i> Verification Queue</span>
        <h1><i class="fas fa-user-check"></i> Pending User Verifications</h1>
        <p>Review identity details and approve only genuine travelers.</p>
    </div>
</div>

<div class="st-card">
    <div class="st-card-header">
        <h3><i class="fas fa-users"></i> Queue (<?php echo $pending_users ? $pending_users->num_rows : 0; ?>)</h3>
    </div>
    <div class="st-card-body admin-table-wrapper">
        <?php if ($pending_users && $pending_users->num_rows > 0): ?>
        <table class="st-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>ID Type</th>
                    <th>ID Number</th>
                    <th>Registered</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $pending_users->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><span class="admin-badge"><?php echo ucfirst($user['id_type']); ?></span></td>
                    <td><code class="admin-badge"><?php echo htmlspecialchars($user['id_number']); ?></code></td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <form method="POST" class="admin-inline-form">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                            <button type="submit" name="verify" value="approve" class="st-btn st-btn-success st-btn-sm" onclick="return confirm('Approve this user?')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" class="admin-inline-form">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                            <button type="submit" name="verify" value="reject" class="st-btn st-btn-danger st-btn-sm" onclick="return confirm('Reject this user?')">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="admin-empty-state">
            <i class="fas fa-check-circle"></i>
            <p>No pending verifications. All caught up!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>