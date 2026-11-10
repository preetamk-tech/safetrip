<?php
session_start();
require_once '../config/database.php';

// Check what session variables are actually set
error_log("Session in API: " . print_r($_SESSION, true));

function isAdmin() {
    global $conn;
    
    // Try both possible session variable names
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['is_admin'] === 1;
    }
    
    return false;
}

function read_json_input() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Check session - try both possible keys
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$user_id) {
    json_response([
        'success' => false, 
        'message' => 'Not logged in. Session data: ' . json_encode(array_keys($_SESSION))
    ], 401);
}

if (!isAdmin()) {
    json_response(['success' => false, 'message' => 'Admin access required.'], 403);
}

// Rest of your code...
$data = read_json_input();
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

if (empty($current_password) || empty($new_password)) {
    json_response(['success' => false, 'message' => 'All fields are required.'], 422);
}

if (strlen($new_password) < 8) {
    json_response(['success' => false, 'message' => 'Password must be at least 8 characters.'], 422);
}

$stmt = $conn->prepare('SELECT password, is_admin FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    json_response(['success' => false, 'message' => 'User not found.'], 404);
}

$admin = $result->fetch_assoc();

if (!(int)$admin['is_admin']) {
    json_response(['success' => false, 'message' => 'Admin access required.'], 403);
}

if (!password_verify($current_password, $admin['password'])) {
    json_response(['success' => false, 'message' => 'Current password is incorrect.'], 403);
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
$update->bind_param('si', $hashed, $user_id);

if ($update->execute()) {
    json_response(['success' => true, 'message' => 'Password changed successfully.']);
} else {
    json_response(['success' => false, 'message' => 'Failed to update password.'], 500);
}
?>