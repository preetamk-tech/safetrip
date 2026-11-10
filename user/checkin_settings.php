<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Check-In Settings';
$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_or_abort();
    $auto_enabled = isset($_POST['auto_checkin']) ? 1 : 0;
    $interval = (int) ($_POST['checkin_interval'] ?? 30);
    $reminder = (int) ($_POST['reminder_before'] ?? 5);

    $saved = save_checkin_settings($user_id, [
        'auto_checkin_enabled' => $auto_enabled,
        'checkin_interval_minutes' => $interval,
        'reminder_before_minutes' => $reminder
    ]);

    if ($saved) {
        header('Location: checkin_settings.php?saved=1');
        exit();
    }
}

require_once '../includes/user_header.php';

$settings = get_user_checkin_settings($user_id);
$missed_count = get_missed_checkins($user_id);
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Safety Features</span>
    </div>
    <h1><i class="fas fa-clipboard-check"></i> Check-In Settings</h1>
    <p>Configure your safety check-in preferences and notifications.</p>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="st-alert st-alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully!</div>
<?php endif; ?>

<?php if ($missed_count > 2): ?>
<div class="st-alert" style="background: linear-gradient(135deg, #7a1d2f, #d62839); color: white; margin-bottom: 20px;">
    <i class="fas fa-exclamation-triangle"></i> <strong>Check-In Alert:</strong> You have <?php echo $missed_count; ?> missed check-ins in the last 24 hours.
</div>
<?php endif; ?>

<form method="POST">
    <?php echo csrf_input(); ?>
    
    <div class="st-card" style="margin-bottom: 20px;">
        <div class="st-card-header">
            <h3><i class="fas fa-cog"></i> Check-In Configuration</h3>
        </div>
        <div class="st-card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <h4 style="margin: 0 0 5px;"><i class="fas fa-bell"></i> Auto Check-In Reminders</h4>
                    <p style="margin: 0; color: #667085; font-size: 0.9rem;">Receive periodic reminders during trips</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="auto_checkin" value="1" <?php echo $settings['auto_checkin_enabled'] ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <h4 style="margin: 0 0 5px;"><i class="fas fa-clock"></i> Check-In Interval</h4>
                    <p style="margin: 0; color: #667085; font-size: 0.9rem;">How often should we prompt you?</p>
                </div>
                <select name="checkin_interval" style="padding: 10px 15px; border-radius: 10px; border: 1px solid #e5e7eb;">
                    <option value="15" <?php echo $settings['checkin_interval_minutes'] == 15 ? 'selected' : ''; ?>>Every 15 minutes</option>
                    <option value="30" <?php echo $settings['checkin_interval_minutes'] == 30 ? 'selected' : ''; ?>>Every 30 minutes</option>
                    <option value="45" <?php echo $settings['checkin_interval_minutes'] == 45 ? 'selected' : ''; ?>>Every 45 minutes</option>
                    <option value="60" <?php echo $settings['checkin_interval_minutes'] == 60 ? 'selected' : ''; ?>>Every hour</option>
                </select>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0;">
                <div>
                    <h4 style="margin: 0 0 5px;"><i class="fas fa-bullhorn"></i> Reminder Before</h4>
                    <p style="margin: 0; color: #667085; font-size: 0.9rem;">Minutes before check-in to send reminder</p>
                </div>
                <select name="reminder_before" style="padding: 10px 15px; border-radius: 10px; border: 1px solid #e5e7eb;">
                    <option value="2" <?php echo $settings['reminder_before_minutes'] == 2 ? 'selected' : ''; ?>>2 minutes</option>
                    <option value="5" <?php echo $settings['reminder_before_minutes'] == 5 ? 'selected' : ''; ?>>5 minutes</option>
                    <option value="10" <?php echo $settings['reminder_before_minutes'] == 10 ? 'selected' : ''; ?>>10 minutes</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="st-card" style="margin-bottom: 20px; background: rgba(15, 118, 110, 0.05); border: 1px solid #bbf7d0;">
        <div class="st-card-header">
            <h3><i class="fas fa-info-circle"></i> How It Works</h3>
        </div>
        <div class="st-card-body">
            <ul style="color: #667085; line-height: 2; margin: 0; padding-left: 20px;">
                <li><i class="fas fa-check" style="color: #0f766e;"></i> When you start a trip, check-ins are automatically scheduled</li>
                <li><i class="fas fa-check" style="color: #0f766e;"></i> You'll receive reminders before each check-in</li>
                <li><i class="fas fa-check" style="color: #0f766e;"></i> If you miss a check-in, trusted contacts are notified</li>
                <li><i class="fas fa-check" style="color: #0f766e;"></i> Your check-in history helps build trust score</li>
            </ul>
        </div>
    </div>
    
    <div style="display: flex; gap: 15px;">
        <button type="submit" class="st-btn st-btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        <a href="dashboard.php" class="st-btn st-btn-secondary">Cancel</a>
    </div>
</form>

<?php require_once '../includes/user_footer.php'; ?>

<style>
.toggle-switch { position: relative; width: 60px; height: 32px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; border-radius: 32px; transition: 0.3s; }
.toggle-slider:before { position: absolute; content: ""; height: 24px; width: 24px; left: 4px; bottom: 4px; background: white; border-radius: 50%; transition: 0.3s; }
.toggle-switch input:checked + .toggle-slider { background: #0f766e; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(28px); }
</style>