<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Not logged in'], 401);
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
    case 'pending':
        $checkins = get_pending_checkins($user_id);
        json_response(['success' => true, 'checkins' => $checkins]);
        break;

    case 'checkin':
        require_json_request_or_abort();
        $data = read_json_input();
        $checkin_id = (int) ($data['checkin_id'] ?? 0);

        if ($checkin_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid checkin ID']);
        }

        $success = perform_checkin($checkin_id, $user_id);
        json_response(['success' => $success, 'message' => $success ? 'Checked in successfully!' : 'Failed']);
        break;

    case 'skip':
        require_json_request_or_abort();
        $data = read_json_input();
        $checkin_id = (int) ($data['checkin_id'] ?? 0);

        if ($checkin_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid checkin ID']);
        }

        $success = skip_checkin($checkin_id, $user_id);
        json_response(['success' => $success, 'message' => $success ? 'Check-in skipped' : 'Failed']);
        break;

    case 'settings':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf_or_abort();
            $data = read_json_input();
            $success = save_checkin_settings($user_id, $data);
            json_response(['success' => $success]);
        } else {
            $settings = get_user_checkin_settings($user_id);
            json_response(['success' => true, 'settings' => $settings]);
        }
        break;

    case 'summary':
        $trip_id = (int) ($_GET['trip_id'] ?? 0);
        if ($trip_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid trip ID']);
        }
        $summary = get_trip_checkin_summary($trip_id, $user_id);
        json_response(['success' => true, 'summary' => $summary]);
        break;

    case 'missed_count':
        $count = get_missed_checkins($user_id);
        json_response(['success' => true, 'count' => $count]);
        break;

    case 'schedule':
        require_json_request_or_abort();
        $data = read_json_input();
        $trip_id = (int) ($data['trip_id'] ?? 0);
        $interval = (int) ($data['interval_minutes'] ?? 30);

        if ($trip_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid trip ID']);
        }

        $success = schedule_checkins_for_trip($trip_id, $user_id, $interval);
        json_response(['success' => $success, 'message' => $success ? 'Check-ins scheduled' : 'Failed']);
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action']);
}

function require_csrf_or_abort() {
    $token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (!$session_token || !$token || !hash_equals($session_token, $token)) {
        json_response(['success' => false, 'message' => 'Security check failed'], 403);
    }
}
?>