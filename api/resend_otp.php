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

if (!isset($_SESSION['temp_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please register again.']);
    exit;
}

$resend_available_at = $_SESSION['otp_resend_available_at'] ?? 0;
if (time() < $resend_available_at) {
    $wait_time = $resend_available_at - time();
    echo json_encode(['success' => false, 'message' => "Please wait {$wait_time} seconds before resending OTP."]);
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

$otp = generateNumericOtp(6);
$otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?");
$stmt->bind_param('ssi', $otp, $otp_expiry, $user_id);
$stmt->execute();

$_SESSION['otp_resend_available_at'] = time() + 60;

$subject = 'SafeTrip Email Verification OTP (Resent)';
$message = "Hello " . e($user['full_name']) . ",<br><br>Your SafeTrip verification OTP is <b>{$otp}</b>.<br><br>This OTP is valid for 10 minutes.";
sendEmail($user['email'], $subject, $message);

echo json_encode(['success' => true, 'message' => 'OTP sent successfully!']);