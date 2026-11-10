<?php

require_once '../config/database.php';

if (!isset($_SESSION['temp_user_id'])) {
    $user_id_from_url = $_GET['user_id'] ?? 0;
    if ($user_id_from_url > 0) {
        $_SESSION['temp_user_id'] = (int)$user_id_from_url;
    }
    if (!isset($_SESSION['temp_user_id'])) {
        $user_id_check = $_SESSION['user_id'] ?? 0;
        if ($user_id_check > 0) {
            $checkUser = getUserById($user_id_check);
            if ($checkUser && $checkUser['verification_status'] === 'pending' && !empty($checkUser['otp'])) {
                $_SESSION['temp_user_id'] = $user_id_check;
                header("Location: verify_otp.php?pending=1");
                exit();
            }
        }
        header("Location: ../index.php");
        exit();
    }
}

$user_id = $_SESSION['temp_user_id'];
$user = getUserById($user_id);
$pending = isset($_GET['pending']);

$error = '';
$success = '';
$message = $_GET['msg'] ?? '';
$resend_available_in = max(0, (int) (($_SESSION['otp_resend_available_at'] ?? 0) - time()));

if (!$user) {
    unset($_SESSION['temp_user_id']);
    header("Location: ../index.php");
    exit();
}

if (is_otp_locked($user)) {
    $error = 'Too many OTP attempts. Please wait 10 minutes before trying again.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    verify_csrf_or_abort();
    
    $entered_otp = trim($_POST['otp'] ?? '');
    
    if (is_otp_locked($user)) {
        $error = 'Too many OTP attempts. Please wait 10 minutes before trying again.';
    } elseif (!preg_match('/^\d{6}$/', $entered_otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } elseif ($entered_otp === $user['otp'] && strtotime($user['otp_expiry']) > time()) {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'pending', otp = NULL, otp_expiry = NULL, otp_fail_count = 0, otp_locked_until = NULL WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        reset_otp_throttle($user_id);
        unset($_SESSION['temp_user_id']);
        $success = "Email verified successfully! Your account is now pending admin verification.";
        header("Location: ../index.php?register_success=" . urlencode($success));
        exit();
    } else {
        record_otp_failure($user_id);
        $user = getUserById($user_id);
        $error = "Invalid or expired OTP!";
        if ($user && is_otp_locked($user)) {
            $error = 'Too many OTP attempts. Please wait 10 minutes before trying again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resend'])) {
    if ($resend_available_in <= 0) {
        $new_otp = generateNumericOtp(6);
        $new_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
        $stmt->bind_param('ssi', $new_otp, $new_expiry, $user_id);
        $stmt->execute();
        @sendEmail($user['email'], 'SafeTrip OTP', "Your new OTP is: $new_otp");
        $_SESSION['otp_resend_available_at'] = time() + 60;
        header("Location: verify_otp.php?msg=" . urlencode("New OTP sent!"));
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_email'])) {
    verify_csrf_or_abort();
    $_SESSION['temp_user_id'] = null;
    unset($_SESSION['temp_user_id']);
    header("Location: ../auth/register.php?action=change_email");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    verify_csrf_or_abort();
    $conn->query("DELETE FROM users WHERE id = $user_id");
    unset($_SESSION['temp_user_id']);
    header("Location: ../index.php?message=account_deleted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - SafeTrip</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
.verify-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative !important;
            margin-top: 50px;
        }
        .verify-close-btn {
            position: absolute !important;
            top: 10px !important;
            right: 10px !important;
            font-size: 28px !important;
            color: #999 !important;
            text-decoration: none !important;
            width: 32px !important;
            height: 32px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 50% !important;
            z-index: 100 !important;
            line-height: 1 !important;
        }
        .verify-close-btn:hover {
            background: #eee !important;
            color: #333 !important;
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            color: #999;
            text-decoration: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }
        .verify-box h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .verify-box p {
            color: #666;
            margin-bottom: 20px;
        }
        .otp-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 10px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-box">
            <a href="../index.php?pending_otp=1" class="verify-close-btn">&times;</a>
            <span class="page-kicker"><i class="fas fa-envelope-open-text"></i> Email Verification</span>
            <i class="fas fa-envelope" style="font-size: 4rem; color: #0f766e; margin-bottom: 20px;"></i>
            <h2>Verify Your Email</h2>
            <p>We've sent an OTP to <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo e($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <input type="text" name="otp" class="otp-input" placeholder="000000" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}">
                </div>
                <button type="submit" name="verify_otp" class="btn btn-primary btn-block">Verify OTP</button>
            </form>
            <form method="POST" style="margin-top: 15px;">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="resend" value="1">
                <button type="submit" class="btn btn-secondary btn-sm" id="resendOtpBtn" <?php echo $resend_available_in > 0 ? 'disabled' : ''; ?>>
                    <?php echo $resend_available_in > 0 ? 'Resend in ' . $resend_available_in . 's' : 'Resend OTP'; ?>
                </button>
            </form>
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="margin-bottom: 12px; font-size: 0.9rem; color: #666;">OTP is valid for 10 minutes.</p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <form method="POST" style="display: inline;">
                        <?php echo csrf_input(); ?>
                        <button type="submit" name="change_email" class="btn btn-outline btn-sm" onclick="return confirm('Change email address? You will need to register again.')">
                            <i class="fas fa-envelope"></i> Change Email
                        </button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <?php echo csrf_input(); ?>
                        <button type="submit" name="delete_account" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete your account? This cannot be undone.')">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var resendButton = document.getElementById('resendOtpBtn');
            var countdown = <?php echo $resend_available_in; ?>;

            if (!resendButton || countdown <= 0) {
                return;
            }

            var timer = setInterval(function () {
                countdown -= 1;
                if (countdown <= 0) {
                    resendButton.disabled = false;
                    resendButton.textContent = 'Resend OTP';
                    clearInterval(timer);
                    return;
                }

                resendButton.textContent = 'Resend in ' + countdown + 's';
            }, 1000);
        })();
    </script>
</body>
</html>
