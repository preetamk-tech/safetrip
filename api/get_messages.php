<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$group_id = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if ($group_id <= 0) {
    echo json_encode([]);
    exit();
}

$membershipStmt = $conn->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1');
$membershipStmt->bind_param('ii', $group_id, $user_id);
$membershipStmt->execute();
if ($membershipStmt->get_result()->num_rows === 0) {
    echo json_encode([]);
    exit();
}

$messagesStmt = $conn->prepare('
    SELECT m.id, m.user_id, m.message, m.created_at, u.full_name AS user_name
    FROM messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.group_id = ?
    ORDER BY m.created_at ASC
');
$messagesStmt->bind_param('i', $group_id);
$messagesStmt->execute();
$messages = $messagesStmt->get_result();

$result = [];
while ($msg = $messages->fetch_assoc()) {
    $result[] = [
        'id' => (int) $msg['id'],
        'user_id' => (int) $msg['user_id'],
        'user_name' => $msg['user_name'],
        'message' => $msg['message'],
        'created_at' => $msg['created_at'],
    ];
}

echo json_encode($result);
