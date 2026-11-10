<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$tripStmt = $conn->prepare('
    SELECT from_location, to_location, travel_date
    FROM trips
    WHERE user_id = ? AND status = "active" AND travel_date >= CURDATE()
');
$tripStmt->bind_param('i', $user_id);
$tripStmt->execute();
$user_trips = $tripStmt->get_result();
$matches = [];
$seen = [];

if ($user_trips && $user_trips->num_rows > 0) {
    $matchStmt = $conn->prepare('
        SELECT DISTINCT u.id, u.full_name, u.trust_score, u.verification_status,
               t.from_location, t.to_location, t.travel_date, t.id AS trip_id
        FROM trips t
        JOIN users u ON t.user_id = u.id
        WHERE t.from_location = ?
          AND t.to_location = ?
          AND DATE(t.travel_date) = DATE(?)
          AND t.user_id != ?
          AND t.status = "active"
          AND u.verification_status = "verified"
          AND u.is_blocked = 0
    ');

    while ($trip = $user_trips->fetch_assoc()) {
        $matchStmt->bind_param('sssi', $trip['from_location'], $trip['to_location'], $trip['travel_date'], $user_id);
        $matchStmt->execute();
        $matchResult = $matchStmt->get_result();

        while ($match = $matchResult->fetch_assoc()) {
            $key = $match['id'] . ':' . $match['trip_id'];
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $matches[] = $match;
        }
    }
}

echo json_encode($matches);
