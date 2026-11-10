<?php
require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$field = isset($_GET['field']) ? $_GET['field'] : 'from'; // 'from' or 'to'

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// Get unique locations based on field
if ($field === 'from') {
    $sql = "SELECT DISTINCT from_location FROM trips 
           WHERE from_location LIKE ? 
           AND status = 'active'
           AND travel_date >= CURDATE()
           ORDER BY from_location LIMIT 10";
} else {
    $sql = "SELECT DISTINCT to_location FROM trips 
           WHERE to_location LIKE ? 
           AND status = 'active'
           AND travel_date >= CURDATE()
           ORDER BY to_location LIMIT 10";
}

$stmt = $conn->prepare($sql);
$searchTerm = $query . '%';
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $field === 'from' ? $row['from_location'] : $row['to_location'];
}

echo json_encode($locations);