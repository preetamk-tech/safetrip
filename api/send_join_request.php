<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

verify_csrf_header_or_abort();

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$from_user_id = (int) $_SESSION['user_id'];
$trip_id = (int) ($data['trip_id'] ?? 0);

if ($trip_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$tripOwnerStmt = $conn->prepare('SELECT from_location, to_location, user_id FROM trips WHERE id = ? AND status = "active" LIMIT 1');
$tripOwnerStmt->bind_param('i', $trip_id);
$tripOwnerStmt->execute();
$trip = $tripOwnerStmt->get_result()->fetch_assoc();

if (!$trip) {
    echo json_encode(['success' => false, 'message' => 'Trip not available.']);
    exit();
}

$to_user_id = (int) $trip['user_id'];

if ($to_user_id === $from_user_id) {
    echo json_encode(['success' => false, 'message' => 'Cannot join your own trip.']);
    exit();
}

$checkStmt = $conn->prepare('SELECT id FROM join_requests WHERE from_user_id = ? AND to_user_id = ? AND trip_id = ? AND status = "pending" LIMIT 1');
$checkStmt->bind_param('iii', $from_user_id, $to_user_id, $trip_id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Request already sent.']);
    exit();
}

$insertStmt = $conn->prepare('INSERT INTO join_requests (from_user_id, to_user_id, trip_id) VALUES (?, ?, ?)');
$insertStmt->bind_param('iii', $from_user_id, $to_user_id, $trip_id);

if (!$insertStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Could not send request.']);
    exit();
}

$message = $_SESSION['user_name'] . ' wants to join your trip from ' . $trip['from_location'] . ' to ' . $trip['to_location'];
sendNotification($to_user_id, 'New Join Request', $message, 'join_request', $insertStmt->insert_id);

echo json_encode(['success' => true]);
