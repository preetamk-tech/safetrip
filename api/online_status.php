<?php

require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Only authenticated users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'ping';

switch ($action) {
    case 'ping':
        // Update user's online status (called periodically by client)
        update_user_online_status($user_id);
        echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')]);
        break;

    case 'go_offline':
        // User is leaving/closing page
        update_user_offline_status($user_id);
        echo json_encode(['success' => true]);
        break;

    case 'status':
        // Get status of specific users
        $user_ids = isset($_GET['user_ids']) ? explode(',', $_GET['user_ids']) : [];
        $user_ids = array_map('intval', $user_ids);
        $status = get_users_online_status($user_ids);
        echo json_encode(['success' => true, 'status' => $status]);
        break;

    case 'all_online':
        // Get all online users count
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_online = 1 AND is_blocked = 0");
        $count = $result->fetch_assoc()['count'];
        echo json_encode(['success' => true, 'online_count' => (int) $count]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}