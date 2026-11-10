<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Not logged in'], 401);
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'create':
        require_json_request_or_abort();
        $data = read_json_input();
        $trip_id = (int) ($data['trip_id'] ?? 0);
        $duration = (int) ($data['duration_hours'] ?? 24);

        if ($trip_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid trip ID']);
        }

        $session = create_escort_session($trip_id, $user_id, $duration);
        if ($session) {
            $link = generate_escort_link($session['escort_code']);
            json_response(['success' => true, 'session' => $session, 'link' => $link]);
        } else {
            json_response(['success' => false, 'message' => 'Failed to create escort session']);
        }
        break;

    case 'end':
        require_json_request_or_abort();
        $data = read_json_input();
        $session_id = (int) ($data['session_id'] ?? 0);

        if ($session_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid session ID']);
        }

        $success = end_escort_session($session_id, $user_id);
        json_response(['success' => $success, 'message' => $success ? 'Escort session ended' : 'Failed']);
        break;

    case 'status':
        require_json_request_or_abort();
        $data = read_json_input();
        $trip_id = (int) ($data['trip_id'] ?? 0);

        if ($trip_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid trip ID']);
        }

        $session = get_active_escort_session($trip_id, $user_id);
        json_response(['success' => true, 'session' => $session]);
        break;

    case 'update_location':
        require_json_request_or_abort();
        $data = read_json_input();
        $session_id = (int) ($data['session_id'] ?? 0);
        $lat = (float) ($data['lat'] ?? 0);
        $lng = (float) ($data['lng'] ?? 0);

        if ($session_id <= 0 || $lat == 0 || $lng == 0) {
            json_response(['success' => false, 'message' => 'Invalid data']);
        }

        $success = update_escort_location($session_id, $lat, $lng);
        json_response(['success' => $success]);
        break;

    case 'list':
        $sessions = get_user_escort_sessions($user_id);
        json_response(['success' => true, 'sessions' => $sessions]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action']);
}
?>