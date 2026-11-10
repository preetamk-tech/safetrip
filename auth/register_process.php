<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

verify_csrf_or_abort();

// Rate limiting - max 5 registrations per IP per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM users WHERE registration_ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)');
$rate_stmt->bind_param('s', $ip);
$rate_stmt->execute();
$rate_result = $rate_stmt->get_result()->fetch_assoc();
if ((int)$rate_result['cnt'] >= 5) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Too many registration attempts. Please try again later.'
    ]);
    exit();
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['password_confirmation'] ?? '';
$phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
$gender = $_POST['gender'] ?? '';
$age = (int)($_POST['age'] ?? 0);
$id_type = $_POST['id_type'] ?? '';
$id_number = strtoupper(trim($_POST['id_number'] ?? ''));

$errors = [];
$allowed_genders = ['male', 'female', 'other'];
$allowed_id_types = ['aadhaar', 'pan', 'driving_license'];

// Store old input for repopulating form
$_SESSION['old_input'] = [
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone,
    'gender' => $gender,
    'age' => $age,
    'id_type' => $id_type,
    'id_number' => $id_number
];

// Full name validation
if (!preg_match('/^[a-zA-Z\s]{3,100}$/', $full_name)) {
    $errors[] = 'Please enter a valid full name (3-100 characters, letters only).';
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Password validation - strong
$password_errors = validate_password_strength($password);
if (!empty($password_errors)) {
    $errors = array_merge($errors, $password_errors);
}

// Confirm password
if ($password !== $confirm_password) {
    $errors[] = 'Password confirmation does not match.';
}

// Phone validation
if (!preg_match('/^\d{10}$/', $phone)) {
    $errors[] = 'Please enter a valid 10-digit phone number.';
}

// Gender & ID type
if (!in_array($gender, $allowed_genders, true)) {
    $errors[] = 'Please select a valid gender.';
}
if (!in_array($id_type, $allowed_id_types, true)) {
    $errors[] = 'Please select a valid ID type.';
}

// Age validation
if ($age < 18 || $age > 100) {
    $errors[] = 'You must be between 18 and 100 years old.';
}

// ID number validation
if (!preg_match('/^[A-Z0-9\-]{6,25}$/', $id_number)) {
    $errors[] = 'Please enter a valid ID number (6-25 characters).';
}

// Check if errors found
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => implode(' ', $errors)
    ]);
    exit();
}

$id_number_hash = hash_id_number($id_number);
$masked_id_number = mask_id_number($id_number);

// Check for duplicate - check each field separately to identify which one matches
$match_type = null;
$match_value = null;

// Check email
$email_check = $conn->prepare('SELECT id FROM users WHERE email = ?');
$email_check->bind_param('s', $email);
$email_check->execute();
$email_check->store_result();
if ($email_check->num_rows > 0) {
    $match_type = 'email';
    $match_value = $email;
}
$email_check->close();

// Check phone
if (!$match_type) {
    $phone_check = $conn->prepare('SELECT id FROM users WHERE phone = ?');
    $phone_check->bind_param('s', $phone);
    $phone_check->execute();
    $phone_check->store_result();
    if ($phone_check->num_rows > 0) {
        $match_type = 'phone';
        $match_value = $phone;
    }
    $phone_check->close();
}

// Check ID number hash
if (!$match_type) {
    $id_check = $conn->prepare('SELECT id FROM users WHERE id_number_hash = ?');
    $id_check->bind_param('s', $id_number_hash);
    $id_check->execute();
    $id_check->store_result();
    if ($id_check->num_rows > 0) {
        $match_type = 'ID number';
        $match_value = $id_number;
    }
    $id_check->close();
}

if ($match_type) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => "This $match_type is already registered. Please use a different $match_type.",
        'field' => $match_type === 'email' ? 'reg_email' : ($match_type === 'phone' ? 'reg_phone' : 'reg_id_number')
    ]);
    exit();
}

// Check for common passwords
if (check_common_passwords($password)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'This password is too common. Please choose a more secure password.',
        'field' => 'reg_password'
    ]);
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$otp = generateNumericOtp(6);
$otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Get client IP
$client_ip = get_client_ip();

$insert_stmt = $conn->prepare(
    'INSERT INTO users (full_name, email, password, phone, gender, age, id_type, id_number, id_number_hash, verification_status, otp, otp_expiry, otp_fail_count, otp_locked_until, registration_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", ?, ?, 0, NULL, ?)'
);
$insert_stmt->bind_param('sssssissssss', $full_name, $email, $hashed_password, $phone, $gender, $age, $id_type, $masked_id_number, $id_number_hash, $otp, $otp_expiry, $client_ip);

if ($insert_stmt->execute()) {
    $_SESSION['temp_user_id'] = $insert_stmt->insert_id;
    $_SESSION['otp_resend_available_at'] = time() + 60;
    
    // Clear form data
    unset($_SESSION['register_data']);

    // Try to send email, but don't fail if it doesn't work
    $email_sent = @sendEmail($email, 'SafeTrip OTP', "Your OTP is: $otp");

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'otp_sent' => $email_sent,
        'message' => $email_sent ? 'OTP sent to email!' : 'Registration successful! Check your email for OTP.'
    ]);
    exit();
}

header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'error' => 'Registration failed. Please try again.'
]);
exit();
?>
