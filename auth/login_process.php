<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verify_csrf_or_abort();

// Rate limiting by IP - max 20 attempts per 15 minutes
if (!enforce_ip_rate_limit(20, 15)) {
    $_SESSION['login_error'] = 'Too many login attempts. Please try again later.';
    $_SESSION['show_login_modal'] = true;
    $_SESSION['old_input'] = ['email' => $_POST['email'] ?? ''];
    header('Location: ../index.php');
    exit();
}

$email = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

// Store old input
$_SESSION['old_input'] = ['email' => $email];

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['login_error'] = 'Please enter a valid email and password.';
    $_SESSION['show_login_modal'] = true;
    record_failed_login($email);
    header('Location: ../index.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user exists but has pending OTP (even with wrong password)
if ($user && !empty($user['otp']) && strtotime($user['otp_expiry']) > time()) {
    $_SESSION['login_error'] = 'OTP Verification Pending for this email. Please verify your email.';
    $_SESSION['show_login_modal'] = true;
    $_SESSION['pending_otp_email'] = $email;
    $_SESSION['pending_otp_user_id'] = (int)$user['id'];
    header('Location: ../index.php');
    exit();
}

// Check if account is blocked
if ($user && $user['is_blocked'] == 1) {
    $_SESSION['login_error'] = 'Your account has been blocked.';
    $_SESSION['show_login_modal'] = true;
    header('Location: ../index.php');
    exit();
}

// Check if account is locked
if ($user && is_login_locked($user)) {
    $_SESSION['login_error'] = 'Your account is temporarily locked. Please try again later.';
    $_SESSION['show_login_modal'] = true;
    record_failed_login($email);
    header('Location: ../index.php');
    exit();
}

$genericError = 'Invalid email or password.';

// Check if user exists and verify password
$is_valid_login = $user && password_verify($password, $user['password']);
if (!$is_valid_login) {
    if ($user) {
        register_login_failure((int) $user['id']);
    }
    record_failed_login($email);
    $_SESSION['login_error'] = $genericError;
    $_SESSION['show_login_modal'] = true;
    header('Location: ../index.php');
    exit();
}

// Check if email needs verification (OTP pending)
if (!empty($user['otp']) && !empty($user['otp_expiry']) && strtotime($user['otp_expiry']) > time()) {
    $_SESSION['temp_user_id'] = (int)$user['id'];
    unset($_SESSION['old_input']);
    record_success_login((int)$user['id']);
    header('Location: ../auth/verify_otp.php');
    exit();
}

// Check verification status
if ($user['verification_status'] !== 'verified' && (int)$user['is_admin'] !== 1) {
    $_SESSION['login_error'] = 'Your account is pending admin approval. You will receive an email once approved.';
    record_failed_login($email);
    header('Location: ../index.php');
    exit();
}

// Success - clear throttles and login
reset_login_throttle((int) $user['id']);
record_success_login((int)$user['id']);
update_last_login((int)$user['id']);
log_activity((int)$user['id'], 'login', 'User logged in');
secure_login_user($user);

// Clear old input
unset($_SESSION['old_input']);

if ((int)$user['is_admin'] === 1) {
    header('Location: ../admin/dashboard.php');
    exit();
}

header('Location: ../user/dashboard.php');
exit();
?>