<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

verify_csrf_header_or_abort();

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$status = isset($data['status']) ? $data['status'] : '';

if ($status == 'safe') {
    $sql = "INSERT INTO safety_alerts (user_id, alert_type) VALUES ($user_id, 'checkin_safe')";
    $conn->query($sql);
    
    log_activity($user_id, 'safety_checkin', 'User confirmed safety');
    
    echo json_encode(['success' => true, 'message' => 'Safety confirmed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
}