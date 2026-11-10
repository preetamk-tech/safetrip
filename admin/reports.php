<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotAdmin();

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $action = $_POST['action'] ?? '';
    $report_id = (int) ($_POST['report_id'] ?? 0);

    if ($action === 'resolve' && $report_id > 0) {
        $stmt = $conn->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
        $stmt->bind_param('i', $report_id);
        $stmt->execute();
        $_SESSION['admin_notice'] = 'Report marked as resolved.';
    }

    if ($action === 'reject' && $report_id > 0) {
        $stmt = $conn->prepare("UPDATE reports SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param('i', $report_id);
        $stmt->execute();
        $_SESSION['admin_notice'] = 'Report rejected.';
    }

    if ($action === 'block' && $report_id > 0) {
        $reportStmt = $conn->prepare('SELECT reported_user_id FROM reports WHERE id = ? LIMIT 1');
        $reportStmt->bind_param('i', $report_id);
        $reportStmt->execute();
        $report = $reportStmt->get_result()->fetch_assoc();
        $reported_user_id = (int) ($report['reported_user_id'] ?? 0);

        if ($reported_user_id > 0) {
            $adminCheck = $conn->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
            $adminCheck->bind_param('i', $reported_user_id);
            $adminCheck->execute();
            $reportedUser = $adminCheck->get_result()->fetch_assoc();

            if (!empty($reportedUser['is_admin'])) {
                $_SESSION['admin_error'] = 'Admin users cannot be blocked from reports.';
            } else {
                $blockStmt = $conn->prepare("UPDATE users SET is_blocked = 1, verification_status = 'rejected' WHERE id = ?");
                $blockStmt->bind_param('i', $reported_user_id);
                $blockStmt->execute();

                $resolveStmt = $conn->prepare("UPDATE reports SET status = 'resolved' WHERE reported_user_id = ? AND status = 'pending'");
                $resolveStmt->bind_param('i', $reported_user_id);
                $resolveStmt->execute();

                $_SESSION['admin_notice'] = 'User blocked and pending reports resolved.';
            }
        } else {
            $_SESSION['admin_error'] = 'Report target not found.';
        }
    }

    header("Location: reports.php");
    exit();
}

$notice = $_SESSION['admin_notice'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_notice'], $_SESSION['admin_error']);

$reports = $conn->query("
    SELECT r.*, u1.full_name as reporter_name, u1.email as reporter_email,
           u2.full_name as reported_name, u2.email as reported_email, u2.id as reported_id,
           u2.is_blocked as reported_is_blocked
    FROM reports r
    JOIN users u1 ON r.reporter_id = u1.id
    JOIN users u2 ON r.reported_user_id = u2.id
    ORDER BY r.created_at DESC
");

$page_title = 'Reports';
require_once '../includes/admin_header.php';
?>

<div class="st-page-header">
    <div>
        <span class="st-page-kicker"><i class="fas fa-flag"></i> Moderation</span>
        <h1><i class="fas fa-flag"></i> User Reports</h1>
    </div>
</div>

<?php if ($notice): ?>
<div class="st-alert st-alert-success" style="margin-bottom: 16px;">
    <i class="fas fa-check-circle"></i> <?php echo e($notice); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="st-alert st-alert-danger" style="margin-bottom: 16px;">
    <i class="fas fa-exclamation-triangle"></i> <?php echo e($error); ?>
</div>
<?php endif; ?>

<div class="st-card">
    <div class="st-card-header">
        <h3><i class="fas fa-exclamation-circle"></i> Reports (<?php echo $reports ? $reports->num_rows : 0; ?>)</h3>
    </div>
    <div class="st-card-body">
        <?php if ($reports && $reports->num_rows > 0): ?>
            <?php while($report = $reports->fetch_assoc()): ?>
            <?php $is_pending = $report['status'] == 'pending'; ?>
            <div class="admin-report-card" style="background: <?php echo $is_pending ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; border-left-color: <?php echo $is_pending ? '#ef4444' : '#10b981'; ?>;">
                <div class="admin-report-header">
                    <p class="admin-report-meta">
                        <span class="admin-report-label">Reporter:</span> <?php echo e($report['reporter_name']); ?> <span class="text-muted">(<?php echo e($report['reporter_email']); ?>)</span>
                    </p>
                    <p class="admin-report-meta">
                        <span class="admin-report-label">Reported:</span> <strong><?php echo e($report['reported_name']); ?></strong> <span class="text-muted">(<?php echo e($report['reported_email']); ?>)</span>
                        <?php if ((int) $report['reported_is_blocked'] === 1): ?>
                            <span class="admin-resolved-badge" style="margin-left: 8px; background: rgba(239,68,68,0.12); color: #dc2626;">
                                <i class="fas fa-ban"></i> Blocked
                            </span>
                        <?php endif; ?>
                    </p>
                    <p class="admin-report-meta">
                        <span class="admin-report-label">Reason:</span>
                    </p>
                    <p class="admin-report-reason"><?php echo nl2br(e($report['reason'])); ?></p>
                    <p class="admin-report-time">
                        <i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($report['created_at'])); ?>
                        <span style="margin-left: 10px; text-transform: capitalize;">Status: <strong><?php echo e($report['status']); ?></strong></span>
                    </p>
                </div>
                <div class="admin-report-actions">
                    <?php if($is_pending): ?>
                        <form method="POST" style="display: inline;">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="resolve">
                            <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                            <button type="submit" class="st-btn st-btn-success st-btn-sm">
                                <i class="fas fa-check"></i> Mark Resolved
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                            <button type="submit" class="st-btn st-btn-secondary st-btn-sm">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Block this user and resolve all pending reports against them?')">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="action" value="block">
                            <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                            <button type="submit" class="st-btn st-btn-danger st-btn-sm" <?php echo (int) $report['reported_is_blocked'] === 1 ? 'disabled' : ''; ?>>
                                <i class="fas fa-ban"></i> Block User
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="admin-resolved-badge">
                            <i class="fas fa-check"></i> Resolved
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="admin-empty-state">
            <i class="fas fa-check-circle"></i>
            <p>No reports found. Platform is running smoothly!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
