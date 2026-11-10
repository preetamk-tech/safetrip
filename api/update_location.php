<?php
require_once '../config/database.php';

require_json_request_or_abort();

$data = read_json_input();
$user_id = current_user_id_or_abort_json();
$latitude = normalize_coordinate($data['latitude'] ?? null, -90, 90);
$longitude = normalize_coordinate($data['longitude'] ?? null, -180, 180);
$trip_id = (int) ($data['trip_id'] ?? 0);

if ($latitude === null || $longitude === null) {
    json_response(['success' => false, 'message' => 'Invalid coordinates.'], 422);
}

if ($trip_id > 0) {
    $tripStmt = $conn->prepare('SELECT id FROM trips WHERE id = ? AND user_id = ? LIMIT 1');
    $tripStmt->bind_param('ii', $trip_id, $user_id);
    $tripStmt->execute();

    if ($tripStmt->get_result()->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Trip not found.'], 404);
    }
}

$tripValue = $trip_id > 0 ? $trip_id : null;
$locationStmt = $conn->prepare(
    'INSERT INTO live_locations (user_id, trip_id, latitude, longitude)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE trip_id = VALUES(trip_id), latitude = VALUES(latitude), longitude = VALUES(longitude), last_updated = CURRENT_TIMESTAMP'
);
$locationStmt->bind_param('iidd', $user_id, $tripValue, $latitude, $longitude);

if (!$locationStmt->execute()) {
    json_response(['success' => false, 'message' => 'Unable to update location.'], 500);
}

json_response(['success' => true]);
