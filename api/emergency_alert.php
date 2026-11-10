<?php
require_once '../config/database.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$user_id = (int) $_SESSION['user_id'];
$latitude = isset($data['latitude']) ? floatval($data['latitude']) : null;
$longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;

try {
    $insertStmt = $conn->prepare('INSERT INTO safety_alerts (user_id, alert_type, location_lat, location_lng) VALUES (?, ?, ?, ?)');
    $alertType = 'emergency';
    $insertStmt->bind_param('isdd', $user_id, $alertType, $latitude, $longitude);
    $insertStmt->execute();

    $logPath = APP_ROOT . '/logs/emergency.log';
    if (!is_dir(APP_ROOT . '/logs')) {
        @mkdir(APP_ROOT . '/logs', 0755, true);
    }
    $locationLink = ($latitude && $longitude) ? "https://maps.google.com/?q={$latitude},{$longitude}" : 'Location unavailable';
    $logEntry = sprintf("[%s] user_id=%d location=%s%s", date('Y-m-d H:i:s'), $user_id, $locationLink, PHP_EOL);
    @file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);

    $userStmt = $conn->prepare('SELECT full_name, email, phone FROM users WHERE id = ? LIMIT 1');
    $userStmt->bind_param('i', $user_id);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();

    $contactStmt = $conn->prepare('SELECT contact_name, contact_email FROM emergency_contacts WHERE user_id = ? AND contact_email IS NOT NULL AND contact_email != ""');
    $contactStmt->bind_param('i', $user_id);
    $contactStmt->execute();
    $contacts = $contactStmt->get_result();

    $emailSent = 0;
    while ($contact = $contacts->fetch_assoc()) {
        $subject = "🚨 EMERGENCY ALERT - {$user['full_name']} needs help!";
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='background: #dc2626; color: white; padding: 20px; text-align: center;'>
                <h1>🚨 EMERGENCY ALERT</h1>
            </div>
            <div style='padding: 20px;'>
                <h2>{$user['full_name']} has triggered an emergency alert!</h2>
                <p><strong>Time:</strong> " . date('d M Y, h:i A') . "</p>
                <p><strong>Phone:</strong> {$user['phone']}</p>
                <p><strong>Location:</strong> <a href='{$locationLink}'>{$locationLink}</a></p>
                <p>Please take immediate action and contact the user or emergency services.</p>
            </div>
        </body>
        </html>
        ";
        @sendEmail($contact['contact_email'], $subject, $message);
        $emailSent++;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Emergency alert recorded.',
        'contacts_available' => $contacts->num_rows,
        'emails_sent' => $emailSent,
        'location' => ['lat' => $latitude, 'lng' => $longitude],
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'message' => 'Alert recorded.']);
}
