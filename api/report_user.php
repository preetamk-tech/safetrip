<?php
require_once '../config/database.php';

require_json_request_or_abort();

$data = read_json_input();
$reporter_id = current_user_id_or_abort_json();
$reported_user_id = (int) ($data['reported_user_id'] ?? 0);
$reason = trim((string) ($data['reason'] ?? ''));

if ($reported_user_id <= 0 || $reporter_id === $reported_user_id) {
    json_response(['success' => false, 'message' => 'Cannot report this user.'], 422);
}

if ($reason === '' || strlen($reason) > 1000) {
    json_response(['success' => false, 'message' => 'Please provide a valid reason.'], 422);
}

$userStmt = $conn->prepare('SELECT id, full_name, is_admin, is_blocked FROM users WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $reported_user_id);
$userStmt->execute();
$reported_user = $userStmt->get_result()->fetch_assoc();

if (!$reported_user) {
    json_response(['success' => false, 'message' => 'Reported user was not found.'], 404);
}

if (!empty($reported_user['is_admin'])) {
    json_response(['success' => false, 'message' => 'Admin accounts cannot be reported here.'], 422);
}

if (!empty($reported_user['is_blocked'])) {
    json_response(['success' => false, 'message' => 'This user is already blocked.'], 409);
}

$checkStmt = $conn->prepare('SELECT id FROM reports WHERE reporter_id = ? AND reported_user_id = ? AND status = "pending" LIMIT 1');
$checkStmt->bind_param('ii', $reporter_id, $reported_user_id);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    json_response(['success' => false, 'message' => 'Already reported this user.'], 409);
}

$insertStmt = $conn->prepare('INSERT INTO reports (reporter_id, reported_user_id, reason) VALUES (?, ?, ?)');
$insertStmt->bind_param('iis', $reporter_id, $reported_user_id, $reason);

if (!$insertStmt->execute()) {
    json_response(['success' => false, 'message' => 'Could not submit report.'], 500);
}

sendNotification(1, 'New User Report', 'Report against ' . ($reported_user['full_name'] ?? 'traveler'), 'system', $insertStmt->insert_id);

json_response(['success' => true, 'message' => 'Report submitted successfully.']);
