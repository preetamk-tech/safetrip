<?php
// Skip database include - not needed for AI chat
//require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><title>SafeTrip AI Ready</title><body>SafeTrip AI endpoint ready.</body>';
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'response' => 'Method not allowed']);
    exit;
}

$message = trim($_POST['message'] ?? '');

if ($message === '') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
}

if (empty($message)) {
    echo json_encode(['success' => false, 'response' => 'Message is required']);
    exit;
}

// Load API key - absolute path
$GROQ_API_KEY = '';
$envPath = __DIR__ . '/../.env';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, 'GROQ_API_KEY=')) {
            $GROQ_API_KEY = trim(str_replace('GROQ_API_KEY=', '', $line), '"');
            break;
        }
    }
}

if (empty($GROQ_API_KEY)) {
    echo json_encode(['success' => true, 'response' => 'API key not found. You need to create an account first. Click Sign Up Free button!']);
    exit;
}

$api_url = 'https://api.groq.com/openai/v1/chat/completions';

$system_msg = 'You are SafeTrip AI Assistant. Keep answers short in English.

Safety Tips:
Travel with verified users. Share your trip details with a trusted contact. Enable Emergency SOS. Set up check-ins. Do not share ID with strangers.

Create Trip:
You need an account. Click Sign Up Free button to register!

Emergency SOS:
Go to dashboard. Police 100, Ambulance 102, and trusted contacts can be alerted.

Privacy:
Do not share phone number or location publicly. Only share with verified travelers.';

$ch = curl_init($api_url);

$data = [
    'model' => 'llama-3.1-8b-instant',
    'messages' => [
        ['role' => 'system', 'content' => $system_msg],
        ['role' => 'user', 'content' => $message]
    ],
    'temperature' => 0.7,
    'max_tokens' => 200
];

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $GROQ_API_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo json_encode(['success' => true, 'response' => 'Connection error. Please try again later.']);
    exit;
}

if ($http_code !== 200) {
    echo json_encode(['success' => true, 'response' => 'Server error. Iske liye aapko account chahiye. Sign Up Free click karo!']);
    exit;
}

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? '';

if ($reply) {
    echo json_encode(['success' => true, 'response' => $reply]);
} else {
    echo json_encode(['success' => true, 'response' => 'Kuch gadbad hai. Phir se try karo!']);
}
