<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Profile';
require_once '../includes/user_header.php';

$user_id = getUserId();
$success = '';
$error = '';

$user = getUserById($user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    verify_csrf_or_abort();

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    $age = (int) ($_POST['age'] ?? 0);

    if (!preg_match('/^[a-zA-Z\s]{3,100}$/', $full_name)) {
        $error = 'Enter a valid full name.';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Enter a valid 10-digit phone number.';
    } elseif ($age < 18 || $age > 100) {
        $error = 'Age must be between 18 and 100.';
    } else {
        $phoneCheck = $conn->prepare('SELECT id FROM users WHERE phone = ? AND id != ? LIMIT 1');
        $phoneCheck->bind_param('si', $phone, $user_id);
        $phoneCheck->execute();

        if ($phoneCheck->get_result()->num_rows > 0) {
            $error = 'That phone number is already in use.';
        } else {
            $updateStmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ?, age = ? WHERE id = ?');
            $updateStmt->bind_param('ssii', $full_name, $phone, $age, $user_id);
            $updateStmt->execute();
            $_SESSION['user_name'] = $full_name;
            $success = 'Profile updated!';
            $user = getUserById($user_id);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    verify_csrf_or_abort();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword !== $confirmPassword) {
            $error = "Passwords don't match!";
        } else {
            $passwordErrors = validate_password_strength($newPassword);
            if (!empty($passwordErrors)) {
                $error = implode(' ', $passwordErrors);
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $passwordStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $passwordStmt->bind_param('si', $hashed, $user_id);
                $passwordStmt->execute();
                $success = 'Password changed!';
                log_activity($user_id, 'password_change', 'Password changed');
            }
        }
    } else {
        $error = "Current password incorrect!";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_privacy'])) {
    verify_csrf_or_abort();
    
    $is_private = isset($_POST['profile_private']) ? 1 : 0;
    $stmt = $conn->prepare('UPDATE users SET is_profile_private = ? WHERE id = ?');
    $stmt->bind_param('ii', $is_private, $user_id);
    $stmt->execute();
    $success = 'Privacy settings updated!';
    $user = getUserById($user_id);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout_all'])) {
    verify_csrf_or_abort();
    invalidate_all_sessions($user_id);
    session_destroy();
    header('Location: ../index.php?message=logged_out_all');
    exit();
}

$badges = getUserBadges($user_id);
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Identity Hub</span>
    </div>
    <h1><i class="fas fa-user"></i> My Profile</h1>
    <p>Update your personal information and account settings.</p>
</div>

<?php if ($success): ?>
<div class="st-alert st-alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="st-alert st-alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
        <div class="st-card" style="margin-bottom: 20px;">
            <div class="st-card-header">
                <h3><i class="fas fa-edit"></i> Edit Profile</h3>
            </div>
            <div class="st-card-body">
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="input-full">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="input-full">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required class="input-full">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Age</label>
                            <input type="number" name="age" value="<?php echo $user['age']; ?>" required class="input-full">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="st-btn st-btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

        <div class="st-card" style="margin-bottom: 20px;">
            <div class="st-card-header">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
            </div>
            <div class="st-card-body">
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Current Password</label>
                            <input type="password" name="current_password" required class="input-full">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">New Password (min <?php echo PASSWORD_MIN_LENGTH; ?> chars)</label>
                            <input type="password" name="new_password" required class="input-full">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Confirm Password</label>
                            <input type="password" name="confirm_password" required class="input-full">
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="st-btn st-btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>

        <div class="st-card" style="margin-bottom: 20px;">
            <div class="st-card-header">
                <h3><i class="fas fa-shield-alt"></i> Privacy Settings</h3>
            </div>
            <div class="st-card-body">
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="profile_private" value="1" <?php echo ($user['is_profile_private'] ?? 0) ? 'checked' : ''; ?>>
                        <span>Make my profile private (hide from other users)</span>
                    </label>
                    <button type="submit" name="update_privacy" class="st-btn st-btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-save"></i> Save Privacy Settings
                    </button>
                </form>
            </div>
        </div>

        <div class="st-card" style="margin-bottom: 20px;">
            <div class="st-card-header">
                <h3><i class="fas fa-sign-out-alt"></i> Security</h3>
            </div>
            <div class="st-card-body">
                <p style="color: #667085; margin-bottom: 15px;">Logout from all devices where you're currently logged in.</p>
                <form method="POST" onsubmit="return confirm('This will log you out from all devices. Continue?');">
                    <?php echo csrf_input(); ?>
                    <button type="submit" name="logout_all" style="background: linear-gradient(135deg, #f97316, #ea580c); color: white; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-sign-out-alt"></i> Logout All Devices
                    </button>
                </form>
            </div>
        </div>

        <div class="st-card" style="border: 2px solid #ef4444;">
            <div class="st-card-header" style="background: rgba(239, 68, 68, 0.1);">
                <h3 style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
            </div>
            <div class="st-card-body">
                <p style="color: #667085; margin-bottom: 15px;">Once you delete your account, there is no going back. All your data will be permanently deleted.</p>
                <form method="POST" onsubmit="return confirm('Are you sure? This cannot be undone!');">
                    <?php echo csrf_input(); ?>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Type DELETE to confirm</label>
                        <input type="text" name="confirm_delete" required pattern="DELETE" class="input-full" placeholder="DELETE">
                    </div>
                    <button type="submit" name="delete_account" class="st-btn st-btn-danger">
                        <i class="fas fa-trash"></i> Delete My Account
                    </button>
                </form>
            </div>
        </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>