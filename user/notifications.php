<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$user_id = getUserId();

if (isset($_GET['action']) && $_GET['action'] === 'mark_all') {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header("Location: notifications.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $id AND user_id = $user_id");
    header("Location: notifications.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM notifications WHERE id = $id AND user_id = $user_id");
    header("Location: notifications.php");
    exit();
}

$page_title = 'Notifications';
require_once '../includes/user_header.php';

$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");
$unread_count = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = $user_id AND is_read = 0")->fetch_assoc()['c'];
?>

<div class="notifications-wrapper">
    <div class="notifications-header">
        <div class="notifications-header-left">
            <span class="notifications-kicker">Notifications</span>
            <h1>Your Notifications</h1>
            <?php if ($unread_count > 0): ?>
            <p class="notifications-subtitle"><?php echo $unread_count; ?> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?></p>
            <?php endif; ?>
        </div>
        <?php if ($unread_count > 0): ?>
        <a href="?action=mark_all" class="notifications-mark-all-btn">
            <i class="fas fa-check-double"></i> Mark All Read
        </a>
        <?php endif; ?>
    </div>

    <div class="notifications-list">
        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <?php while($notif = $notifications->fetch_assoc()): ?>
            <div class="notification-item <?php echo $notif['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
                <div class="notification-icon">
                    <?php
                    $icon_map = [
                        'trip_created' => ['fa-route', '#10b981'],
                        'trip_joined' => ['fa-user-plus', '#8b5cf6'],
                        'join_request' => ['fa-user-clock', '#f59e0b'],
                        'message' => ['fa-comment', '#3b82f6'],
                        'checkin' => ['fa-map-marker-alt', '#06b6d4'],
                        'checkin_safe' => ['fa-shield-check', '#10b981'],
                        'system' => ['fa-cog', '#64748b'],
                        'emergency' => ['fa-exclamation-triangle', '#ef4444'],
                        'safety_alert' => ['fa-shield-alt', '#f59e0b'],
                        'group_message' => ['fa-comments', '#8b5cf6'],
                        'request_accepted' => ['fa-check-circle', '#10b981']
                    ];
                    $icon_info = $icon_map[$notif['type']] ?? ['fa-bell', '#64748b'];
                    ?>
                    <i class="fas <?php echo $icon_info[0]; ?>" style="color: <?php echo $icon_info[1]; ?>;"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-header-row">
                        <?php 
                        $notif_type = $notif['type'] ?? '';
                        $related_id = (int)($notif['related_id'] ?? 0);
                        if ($notif_type === 'group_message' && $related_id > 0): 
                        ?>
                        <a href="groups.php?group_id=<?php echo $related_id; ?>" class="notification-title-link">
                            <i class="fas fa-comments"></i> <?php echo htmlspecialchars($notif['title']); ?>
                        </a>
                        <?php else: ?>
                        <h4 class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></h4>
                        <?php endif; ?>
                        <?php if (!$notif['is_read']): ?>
                        <span class="notification-unread-dot"></span>
                        <?php endif; ?>
                    </div>
                    <p class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></p>
                    <small class="notification-time">
                        <i class="fas fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                    </small>
                </div>
                <div class="notification-actions">
                    <?php if (!$notif['is_read']): ?>
                    <a href="?action=read&id=<?php echo $notif['id']; ?>" class="notification-action-read" title="Mark as Read">
                        <i class="fas fa-check"></i>
                    </a>
                    <?php endif; ?>
                    <a href="?action=delete&id=<?php echo $notif['id']; ?>" class="notification-action-delete" title="Delete" onclick="return confirm('Delete this notification?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="notifications-empty">
            <div class="notifications-empty-icon">
                <i class="fas fa-bell-slash"></i>
            </div>
            <h3>No notifications yet</h3>
            <p>We'll notify you when something happens!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-wrapper {
    padding: 24px;
    width: 100%;
    box-sizing: border-box;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 24px;
}

.notifications-header-left {
    display: flex;
    flex-direction: column;
}

.notifications-kicker {
    font-size: 0.8rem;
    color: #0f766e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.notifications-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px;
}

.notifications-subtitle {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
}

.notifications-mark-all-btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.notifications-mark-all-btn:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.notifications-list {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background: #fafafa;
}

.notification-unread {
    background: linear-gradient(90deg, rgba(15,118,110,0.03) 0%, transparent 100%);
}

.notification-read {
    opacity: 0.7;
}

.notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.notification-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.notification-title-link {
    font-size: 1rem;
    font-weight: 600;
    color: #8b5cf6;
    margin: 0;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}

.notification-title-link:hover {
    color: #7c3aed;
    text-decoration: underline;
}

.notification-unread-dot {
    width: 8px;
    height: 8px;
    background: #0f766e;
    border-radius: 50%;
    flex-shrink: 0;
}

.notification-message {
    color: #64748b;
    font-size: 0.9rem;
    margin: 0 0 8px;
    line-height: 1.5;
}

.notification-time {
    color: #94a3b8;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.notification-time i {
    font-size: 0.75rem;
}

.notification-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.notification-action-read,
.notification-action-delete {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.notification-action-read {
    background: #f0fdf4;
    color: #16a34a;
}

.notification-action-read:hover {
    background: #dcfce7;
}

.notification-action-delete {
    background: #fef2f2;
    color: #dc2626;
}

.notification-action-delete:hover {
    background: #fee2e2;
}

.notifications-empty {
    text-align: center;
    padding: 60px 20px;
}

.notifications-empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.notifications-empty-icon i {
    font-size: 2rem;
    color: #94a3b8;
}

.notifications-empty h3 {
    color: #475569;
    margin: 0 0 8px;
    font-size: 1.1rem;
}

.notifications-empty p {
    color: #94a3b8;
    margin: 0;
}

[data-theme="dark"] .notifications-header h1,
[data-theme="dark"] .notification-title {
    color: #f1f5f9;
}

[data-theme="dark"] .notifications-list {
    background: #1e293b;
}

[data-theme="dark"] .notification-item {
    border-bottom-color: #334155;
}

[data-theme="dark"] .notification-item:hover {
    background: #263344;
}

[data-theme="dark"] .notification-unread {
    background: linear-gradient(90deg, rgba(20,184,166,0.08) 0%, transparent 100%);
}

[data-theme="dark"] .notification-message {
    color: #94a3b8;
}

[data-theme="dark"] .notifications-empty h3 {
    color: #94a3b8;
}

[data-theme="dark"] .notification-title-link {
    color: #a78bfa;
}

[data-theme="dark"] .notification-title-link:hover {
    color: #c4b5fd;
}

[data-theme="dark"] .notifications-mark-all-btn {
    background: #334155;
    color: #cbd5e1;
    border-color: #475569;
}

[data-theme="dark"] .notifications-mark-all-btn:hover {
    background: #475569;
}
</style>

<?php require_once '../includes/user_footer.php'; ?>