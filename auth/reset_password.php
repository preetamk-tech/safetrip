<?php
require_once '../config/database.php';

$token = $_GET['token'] ?? '';
$token_stmt = $conn->prepare('SELECT id FROM users WHERE reset_token = ? AND otp_expiry > NOW() LIMIT 1');
$token_stmt->bind_param('s', $token);
$token_stmt->execute();
$user = $token_stmt->get_result();

if ($user->num_rows == 0) {
    die("Invalid or expired reset link. <a href='forgot_password.php'>Try again</a>");
}

$user_data = $user->fetch_assoc();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_or_abort();

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters!";
    } elseif ($password != $confirm) {
        $error = "Passwords do not match!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare('UPDATE users SET password = ?, reset_token = NULL, otp_expiry = NULL WHERE id = ?');
        $update_stmt->bind_param('si', $hashed, $user_data['id']);
        $update_stmt->execute();
        $success = "Password reset successfully! <a href='../index.php'>Login here</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SafeTrip</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <span class="page-kicker"><i class="fas fa-shield-keyhole"></i> Password Update</span>
            <h2 style="text-align:center; margin-bottom:10px;">Reset Password</h2>
            <p style="text-align:center; color:#667085; margin-bottom:24px;">Create a fresh password and lock your account back down.</p>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="POST">
                    <?php echo csrf_input(); ?>
                    <div class="form-group">
                        <label>New Password (min 8 characters)</label>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
