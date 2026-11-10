<?php
require_once '../config/database.php';

require_json_request_or_abort();

$data = read_json_input();
$user_id = current_user_id_or_abort_json();
$group_id = (int) ($data['group_id'] ?? 0);
$message = trim((string) ($data['message'] ?? ''));

if ($group_id <= 0 || $message === '' || mb_strlen($message) > 2000) {
    json_response(['success' => false, 'message' => 'Invalid message payload.'], 422);
}

$membershipStmt = $conn->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1');
$membershipStmt->bind_param('ii', $group_id, $user_id);
$membershipStmt->execute();
if ($membershipStmt->get_result()->num_rows === 0) {
    json_response(['success' => false, 'message' => 'Not a member of this group.'], 403);
}

$insertStmt = $conn->prepare('INSERT INTO messages (group_id, user_id, message) VALUES (?, ?, ?)');
$insertStmt->bind_param('iis', $group_id, $user_id, $message);

if (!$insertStmt->execute()) {
    json_response(['success' => false, 'message' => 'Unable to send message.'], 500);
}

// Get sender name
$senderName = $_SESSION['user_name'] ?? 'Someone';

// Get group info - get trip name if group name is null
$groupInfo = $conn->query("
    SELECT tg.name, tg.group_code, t.from_location, t.to_location 
    FROM travel_groups tg 
    LEFT JOIN trips t ON tg.trip_id = t.id 
    WHERE tg.id = $group_id
")->fetch_assoc();

if ($groupInfo) {
    $groupName = !empty($groupInfo['name']) 
        ? $groupInfo['name'] 
        : ($groupInfo['from_location'] . ' to ' . $groupInfo['to_location']);
} else {
    $groupName = 'Group';
}

// Get all other members and send notifications
$otherMembers = $conn->query("SELECT user_id FROM group_members WHERE group_id = $group_id AND user_id != $user_id");
$notifiedCount = 0;
if ($otherMembers) {
    while ($member = $otherMembers->fetch_assoc()) {
        $notifResult = sendNotification(
            (int)$member['user_id'],
            "New message in " . $groupName,
            $senderName . ": " . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
            'group_message',
            $group_id
        );
        if ($notifResult) $notifiedCount++;
    }
}

json_response(['success' => true, 'notified' => $notifiedCount]);
