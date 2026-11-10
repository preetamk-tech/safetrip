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
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $routes = get_favorite_routes($user_id, $limit);
        json_response(['success' => true, 'routes' => $routes]);
        break;

    case 'add':
        require_json_request_or_abort();

        $data = read_json_input();
        $from = trim($data['from_location'] ?? '');
        $to = trim($data['to_location'] ?? '');
        $mode = $data['transport_mode'] ?? 'train';

        if (empty($from) || empty($to)) {
            json_response(['success' => false, 'message' => 'From and To locations required']);
        }

        $added = add_favorite_route($user_id, $from, $to, $mode);
        json_response(['success' => $added, 'message' => $added ? 'Route added to favorites' : 'Failed to add route']);
        break;

    case 'remove':
        require_json_request_or_abort();

        $data = read_json_input();
        $route_id = (int) ($data['route_id'] ?? 0);

        if ($route_id <= 0) {
            json_response(['success' => false, 'message' => 'Invalid route ID']);
        }

        $removed = remove_favorite_route($user_id, $route_id);
        json_response(['success' => $removed, 'message' => $removed ? 'Route removed from favorites' : 'Failed to remove route']);
        break;

    case 'check':
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');

        $is_favorite = is_route_favorite($user_id, $from, $to);
        json_response(['success' => true, 'is_favorite' => $is_favorite]);
        break;

    case 'count':
        $count = get_favorite_routes_count($user_id);
        json_response(['success' => true, 'count' => $count]);
        break;

    default:
        json_response(['success' => false, 'message' => 'Invalid action']);
}