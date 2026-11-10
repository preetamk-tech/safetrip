<?php
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf_or_abort();

    $email = trim(strtolower($_POST['email'] ?? ''));
    $stmt = $conn->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result();

    if ($user->num_rows > 0) {
        $user_data = $user->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update_stmt = $conn->prepare('UPDATE users SET reset_token = ?, otp_expiry = ? WHERE id = ?');
        $update_stmt->bind_param('ssi', $token, $expiry, $user_data['id']);
        $update_stmt->execute();

        $reset_link = APP_URL . "/auth/reset_password.php?token=$token";
        $subject = "Password Reset - SafeTrip";
        $message = "Hello " . e($user_data['full_name']) . ",<br><br>Click this link to reset your password:<br><a href='$reset_link'>$reset_link</a><br><br>Valid for 1 hour.";
        sendEmail($email, $subject, $message);
    }

    $success = "If that email exists, a reset link has been sent.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SafeTrip</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <span class="page-kicker"><i class="fas fa-key"></i> Account Recovery</span>
            <h2 style="text-align:center; margin-bottom:10px;">Forgot Password</h2>
            <p style="text-align:center; color:#667085; margin-bottom:24px;">Enter your email and we’ll send a secure reset link.</p>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your registered email" autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                <p style="text-align:center; margin-top:20px;"><a href="../index.php">Back to Login</a></p>
            </form>
        </div>
    </div>
</body>
</html>
