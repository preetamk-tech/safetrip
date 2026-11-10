<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verify_csrf_header_or_abort();

$input = json_decode(file_get_contents('php://input'), true);
$entered_otp = trim($input['otp'] ?? '');

if (!isset($_SESSION['temp_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please register again.']);
    exit;
}

$user_id = $_SESSION['temp_user_id'];
$user = getUserById($user_id);

if (!$user) {
    unset($_SESSION['temp_user_id']);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please register again.']);
    exit;
}

if (is_otp_locked($user)) {
    echo json_encode(['success' => false, 'message' => 'Too many OTP attempts. Please wait 10 minutes before trying again.']);
    exit;
}

if (!preg_match('/^\d{6}$/', $entered_otp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit OTP.']);
    exit;
}

if ($entered_otp === $user['otp'] && strtotime($user['otp_expiry']) > time()) {
    $stmt = $conn->prepare("UPDATE users SET verification_status = 'pending', otp = NULL, otp_expiry = NULL, otp_fail_count = 0, otp_locked_until = NULL WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    reset_otp_throttle($user_id);
    
    unset($_SESSION['temp_user_id']);
    $_SESSION['otp_verified'] = true;
    $_SESSION['verified_email'] = $user['email'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Email verified! Your account is now pending admin verification. You will be notified once approved.'
    ]);
    exit;
} else {
    record_otp_failure($user_id);
    $user = getUserById($user_id);
    
    if ($user && is_otp_locked($user)) {
        echo json_encode(['success' => false, 'message' => 'Too many OTP attempts. Please wait 10 minutes before trying again.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP!']);
    }
    exit;
}