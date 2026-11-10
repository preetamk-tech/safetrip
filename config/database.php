<?php
require_once __DIR__ . '/env.php';

loadEnv(dirname(__DIR__) . '/.env');

date_default_timezone_set('Asia/Kolkata');

define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', (string) env('APP_NAME', 'SafeTrip'));
define('APP_URL', rtrim((string) env('APP_URL', 'https://safetrip.kesug.com/safetrip'), '/'));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));

if (session_status() === PHP_SESSION_NONE) {
    $is_https = (bool) env('SESSION_SECURE_COOKIE', false) || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name((string) env('SESSION_NAME', 'safe_trip_session'));
    session_start();
}

error_reporting(APP_DEBUG ? E_ALL : 0);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/php-error.log');

header_remove('X-Powered-By');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');

$servername = (string) env('DB_HOST', '127.0.0.1');
$username = (string) env('DB_USER', 'root');
$password = (string) env('DB_PASS', '');
$dbname = (string) env('DB_NAME', 'safetrip');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed.');
}

$conn->set_charset('utf8mb4');

require_once APP_ROOT . '/includes/mailer.php';

const PASSWORD_MIN_LENGTH = 8;

function ensure_users_security_columns(): void {
    global $conn;

    static $done = false;

    if ($done) {
        return;
    }

    $done = true;
    $required = [
        'id_number_hash' => "ALTER TABLE users ADD COLUMN id_number_hash CHAR(64) NULL AFTER id_number",
        'login_fail_count' => "ALTER TABLE users ADD COLUMN login_fail_count INT NOT NULL DEFAULT 0 AFTER reset_token",
        'login_locked_until' => "ALTER TABLE users ADD COLUMN login_locked_until DATETIME NULL AFTER login_fail_count",
        'otp_fail_count' => "ALTER TABLE users ADD COLUMN otp_fail_count INT NOT NULL DEFAULT 0 AFTER login_locked_until",
        'otp_locked_until' => "ALTER TABLE users ADD COLUMN otp_locked_until DATETIME NULL AFTER otp_fail_count",
    ];

    $existing = [];
    $result = $conn->query("SHOW COLUMNS FROM users");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[$row['Field']] = true;
        }
    }

    foreach ($required as $column => $sql) {
        if (!isset($existing[$column])) {
            $conn->query($sql);
        }
    }

    $conn->query("UPDATE users SET id_number_hash = SHA2(UPPER(id_number), 256) WHERE id_number_hash IS NULL OR id_number_hash = ''");
    $conn->query("
        UPDATE users
        SET id_number = CONCAT(REPEAT('*', GREATEST(CHAR_LENGTH(id_number) - 4, 0)), RIGHT(id_number, LEAST(4, CHAR_LENGTH(id_number))))
        WHERE id_number IS NOT NULL AND id_number NOT LIKE '%*%'
    ");

    $indexResult = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'unique_id_number_hash'");
    if ($indexResult && $indexResult->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD UNIQUE KEY unique_id_number_hash (id_number_hash)");
    }
}

ensure_users_security_columns();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function escape($str) {
    global $conn;
    return mysqli_real_escape_string($conn, $str);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_or_abort() {
    $token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (!$session_token || !$token || !hash_equals($session_token, $token)) {
        http_response_code(403);
        exit('Security check failed. Please refresh and try again.');
    }
}

function verify_csrf_header_or_abort() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token && function_exists('apache_request_headers')) {
        $apacheHeaders = apache_request_headers();
        $token = $apacheHeaders['X-CSRF-Token'] ?? $apacheHeaders['x-csrf-token'] ?? '';
    }
    if (!$token && function_exists('getallheaders')) {
        $allHeaders = getallheaders();
        foreach ($allHeaders as $name => $value) {
            if (strtolower($name) === 'x-csrf-token') {
                $token = $value;
                break;
            }
        }
    }
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (!$session_token || !$token || !hash_equals($session_token, $token)) {
        json_response(['success' => false, 'message' => 'Security check failed.'], 403);
    }
}

function json_response(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

function require_post_request_or_abort_json() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
}

function require_json_request_or_abort() {
    require_post_request_or_abort_json();
    verify_csrf_header_or_abort();
}

function current_user_id_or_abort_json(): int {
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Not logged in.'], 401);
    }

    return (int) $_SESSION['user_id'];
}

function read_json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function normalize_coordinate($value, float $min, float $max): ?float {
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    $number = (float) $value;

    if ($number < $min || $number > $max) {
        return null;
    }

    return $number;
}

function mask_id_number(string $idNumber): string {
    $visible = min(4, strlen($idNumber));
    $maskedLength = max(0, strlen($idNumber) - $visible);

    return str_repeat('*', $maskedLength) . substr($idNumber, -$visible);
}

function hash_id_number(string $idNumber): string {
    return hash('sha256', strtoupper(trim($idNumber)));
}

function secure_login_user(array $user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['is_admin'] = (int)$user['is_admin'];
    $_SESSION['admin_name'] = $user['full_name'];
    $_SESSION['trust_score'] = (int)$user['trust_score'];
    unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
}

function reset_login_throttle(int $userId): void {
    global $conn;

    $stmt = $conn->prepare('UPDATE users SET login_fail_count = 0, login_locked_until = NULL WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function register_login_failure(int $userId): void {
    global $conn;

    $stmt = $conn->prepare('UPDATE users SET login_fail_count = login_fail_count + 1 WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $lockStmt = $conn->prepare('UPDATE users SET login_fail_count = 0, login_locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ? AND login_fail_count >= 5');
    $lockStmt->bind_param('i', $userId);
    $lockStmt->execute();
}

function is_login_locked(array $user): bool {
    if (empty($user['login_locked_until'])) {
        return false;
    }

    return strtotime((string) $user['login_locked_until']) > time();
}

function record_otp_failure(int $userId): void {
    global $conn;

    $stmt = $conn->prepare('UPDATE users SET otp_fail_count = otp_fail_count + 1 WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $lockStmt = $conn->prepare('UPDATE users SET otp_fail_count = 0, otp_locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ? AND otp_fail_count >= 5');
    $lockStmt->bind_param('i', $userId);
    $lockStmt->execute();
}

function reset_otp_throttle(int $userId): void {
    global $conn;

    $stmt = $conn->prepare('UPDATE users SET otp_fail_count = 0, otp_locked_until = NULL WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function is_otp_locked(array $user): bool {
    if (empty($user['otp_locked_until'])) {
        return false;
    }

    return strtotime((string) $user['otp_locked_until']) > time();
}

function generateNumericOtp($length = 6) {
    $otp = '';

    for ($i = 0; $i < $length; $i++) {
        $otp .= (string)random_int(0, 9);
    }

    return $otp;
}

function getUserById($id) {
    global $conn;

    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function sendNotification($user_id, $title, $message, $type = 'system', $related_id = null) {
    global $conn;

    $related_id = $related_id !== null ? (int)$related_id : null;
    $type = in_array($type, ['join_request','request_accepted','emergency','system','group_message']) ? $type : 'system';
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $user_id, $title, $message, $type, $related_id);

    return $stmt->execute();
}

function getUnreadCount($user_id) {
    global $conn;

    $stmt = $conn->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    return (int)$data['count'];
}

function updateTrustScore($user_id) {
    global $conn;

    $rating_stmt = $conn->prepare('SELECT AVG(rating) AS avg_rating FROM trip_ratings WHERE rated_user_id = ?');
    $rating_stmt->bind_param('i', $user_id);
    $rating_stmt->execute();
    $rating_data = $rating_stmt->get_result()->fetch_assoc();

    $score = 50;
    if (!empty($rating_data['avg_rating'])) {
        $score = 50 + ($rating_data['avg_rating'] * 10);
    }

    $user = getUserById($user_id);
    if (!empty($user) && $user['verification_status'] === 'verified') {
        $score += 20;
    }

    $score = min(100, max(0, (int)round($score)));
    $update_stmt = $conn->prepare('UPDATE users SET trust_score = ? WHERE id = ?');
    $update_stmt->bind_param('ii', $score, $user_id);
    $update_stmt->execute();

    return $score;
}

function generateGroupCode() {
    global $conn;

    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $stmt = $conn->prepare('SELECT id FROM travel_groups WHERE group_code = ? LIMIT 1');
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
    } while ($exists);

    return $code;
}

function getUserBadges($user_id) {
    global $conn;

    $stmt = $conn->prepare('SELECT * FROM user_badges WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $badges = [];

    while ($row = $result->fetch_assoc()) {
        $badges[] = $row;
    }

    return $badges;
}

function validate_password_strength(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&* etc).';
    }
    
    return $errors;
}

function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    return $ip;
}

function enforce_ip_rate_limit(int $max_attempts = 20, int $block_minutes = 15): bool {
    global $conn;
    
    $ip = get_client_ip();
    $current_time = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare('SELECT COUNT(*) as attempts FROM login_attempts WHERE ip = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
    $stmt->bind_param('si', $ip, $block_minutes);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ((int)$result['attempts'] >= $max_attempts) {
        return false; // Rate limited
    }
    
    return true;
}

function record_failed_login(string $email): void {
    global $conn;
    
    $ip = get_client_ip();
    $stmt = $conn->prepare('INSERT INTO login_attempts (email, ip, attempt_time, success) VALUES (?, ?, NOW(), 0)');
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
}

function record_success_login(int $user_id): void {
    global $conn;
    
    $ip = get_client_ip();
    $stmt = $conn->prepare('INSERT INTO login_attempts (user_id, ip, attempt_time, success) VALUES (?, ?, NOW(), 1)');
    $stmt->bind_param('is', $user_id, $ip);
    $stmt->execute();
    
    // Clear failed attempts for this user
    $conn->query("DELETE FROM login_attempts WHERE user_id = $user_id AND success = 0");
}

function check_common_passwords(string $password): bool {
    $common = [
        'password', '12345678', 'password123', '123456789', 'qwerty', 'abc123',
        'password1', 'iloveyou', 'admin', 'letmein', 'welcome', 'monkey',
        'dragon', 'master', 'admin123', 'login123', 'sunshine', 'princess'
    ];
    
    return in_array(strtolower($password), $common);
}

function log_activity(int $user_id, string $action, ?string $details = null): void {
    global $conn;
    
    $ip = get_client_ip();
    $stmt = $conn->prepare('INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $user_id, $action, $details, $ip);
    $stmt->execute();
}

function get_user_activity(int $user_id, int $limit = 50): array {
    global $conn;
    
    $stmt = $conn->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    return $activities;
}

function update_last_login(int $user_id): void {
    global $conn;
    
    $stmt = $conn->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
}

function generate_share_code(): string {
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function invalidate_all_sessions(int $user_id): void {
    global $conn;
    
    // Clear any remember me tokens and session data
    $stmt = $conn->prepare('UPDATE users SET session_token = NULL WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    
    // Clear login attempts
    $conn->query("DELETE FROM login_attempts WHERE user_id = $user_id");
}

// =============================================
// ONLINE STATUS TRACKING FUNCTIONS
// =============================================

function ensure_users_online_columns(): void {
    global $conn;

    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $existing = [];
    $result = $conn->query("SHOW COLUMNS FROM users");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[$row['Field']] = true;
        }
    }

    $required = [
        'is_online' => "ALTER TABLE users ADD COLUMN is_online TINYINT(1) NOT NULL DEFAULT 0 AFTER trust_score",
        'last_seen' => "ALTER TABLE users ADD COLUMN last_seen DATETIME NULL AFTER is_online",
    ];

    foreach ($required as $column => $sql) {
        if (!isset($existing[$column])) {
            $conn->query($sql);
        }
    }
}

ensure_users_online_columns();

function update_user_online_status(int $user_id): void {
    global $conn;

    $stmt = $conn->prepare('UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
}

function update_user_offline_status(int $user_id): void {
    global $conn;

    $stmt = $conn->prepare('UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
}

function get_user_online_status(int $user_id): array {
    global $conn;

    $stmt = $conn->prepare('SELECT is_online, last_seen FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?: ['is_online' => 0, 'last_seen' => null];
}

function get_users_online_status(array $user_ids): array {
    global $conn;

    if (empty($user_ids)) {
        return [];
    }

    $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, is_online, last_seen FROM users WHERE id IN ($placeholders)");
    $types = str_repeat('i', count($user_ids));
    $stmt->bind_param($types, ...$user_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $status = [];
    while ($row = $result->fetch_assoc()) {
        $status[$row['id']] = $row;
    }

    return $status;
}

function format_online_status(array $user_status): string {
    if (empty($user_status)) {
        return '<span class="online-status offline"><i class="fas fa-circle"></i> Offline</span>';
    }

    $is_online = (int) $user_status['is_online'];
    $last_seen = $user_status['last_seen'];

    if ($is_online) {
        return '<span class="online-status online"><i class="fas fa-circle"></i> Online</span>';
    }

    if ($last_seen) {
        $diff = time() - strtotime($last_seen);
        if ($diff < 60) {
            return '<span class="online-status offline"><i class="fas fa-circle"></i> Just now</span>';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return '<span class="online-status away"><i class="fas fa-circle"></i> ' . $mins . 'm ago</span>';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return '<span class="online-status away"><i class="fas fa-circle"></i> ' . $hours . 'h ago</span>';
        }
    }

    return '<span class="online-status offline"><i class="fas fa-circle"></i> Offline</span>';
}

function mark_all_users_offline(): void {
    global $conn;

    // Mark users offline if no activity in 5 minutes
    $conn->query("UPDATE users SET is_online = 0 WHERE is_online = 1 AND (last_seen IS NULL OR last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE))");
}

mark_all_users_offline();

// =============================================
// FAVORITE ROUTES FUNCTIONS
// =============================================

function ensure_favorite_routes_table(): void {
    global $conn;

    $result = $conn->query("SHOW TABLES LIKE 'favorite_routes'");
    if ($result && $result->num_rows == 0) {
        $conn->query("
            CREATE TABLE favorite_routes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                from_location VARCHAR(255) NOT NULL,
                to_location VARCHAR(255) NOT NULL,
                transport_mode VARCHAR(50) DEFAULT 'train',
                usage_count INT DEFAULT 1,
                last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_route (user_id, from_location, to_location)
            )
        ");
    }
}

ensure_favorite_routes_table();

function add_favorite_route(int $user_id, string $from, string $to, string $mode = 'train'): bool {
    global $conn;

    // Check if exists, update count or insert new
    $check = $conn->prepare('SELECT id, usage_count FROM favorite_routes WHERE user_id = ? AND from_location = ? AND to_location = ?');
    $check->bind_param('iss', $user_id, $from, $to);
    $check->execute();
    $result = $check->get_result();

    if ($row = $result->fetch_assoc()) {
        // Update usage count and timestamp
        $new_count = $row['usage_count'] + 1;
        $update = $conn->prepare('UPDATE favorite_routes SET usage_count = ?, last_used = NOW() WHERE id = ?');
        $update->bind_param('ii', $new_count, $row['id']);
        return $update->execute();
    } else {
        // Insert new
        $insert = $conn->prepare('INSERT INTO favorite_routes (user_id, from_location, to_location, transport_mode) VALUES (?, ?, ?, ?)');
        $insert->bind_param('isss', $user_id, $from, $to, $mode);
        return $insert->execute();
    }
}

function remove_favorite_route(int $user_id, int $route_id): bool {
    global $conn;

    $stmt = $conn->prepare('DELETE FROM favorite_routes WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $route_id, $user_id);
    return $stmt->execute();
}

function get_favorite_routes(int $user_id, int $limit = 10): array {
    global $conn;

    $stmt = $conn->prepare('SELECT * FROM favorite_routes WHERE user_id = ? ORDER BY usage_count DESC, last_used DESC LIMIT ?');
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $routes = [];
    while ($row = $result->fetch_assoc()) {
        $routes[] = $row;
    }

    return $routes;
}

function is_route_favorite(int $user_id, string $from, string $to): bool {
    global $conn;

    $stmt = $conn->prepare('SELECT id FROM favorite_routes WHERE user_id = ? AND from_location = ? AND to_location = ?');
    $stmt->bind_param('iss', $user_id, $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

function get_favorite_routes_count(int $user_id): int {
    global $conn;

    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM favorite_routes WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['count'];
}

// =============================================
// TRIP SHARING FUNCTIONS
// =============================================

function ensure_trip_share_columns(): void {
    global $conn;

    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $existing = [];
    $result = $conn->query("SHOW COLUMNS FROM trips");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing[$row['Field']] = true;
        }
    }

    $required = [
        'is_shared' => "ALTER TABLE trips ADD COLUMN is_shared TINYINT(1) NOT NULL DEFAULT 0 AFTER share_code",
        'share_view_count' => "ALTER TABLE trips ADD COLUMN share_view_count INT DEFAULT 0 AFTER is_shared",
        'escort_enabled' => "ALTER TABLE trips ADD COLUMN escort_enabled TINYINT(1) DEFAULT 0 AFTER share_view_count",
    ];

    foreach ($required as $column => $sql) {
        if (!isset($existing[$column])) {
            $conn->query($sql);
        }
    }
}

ensure_trip_share_columns();

function get_trip_by_share_code(string $share_code): ?array {
    global $conn;

    $stmt = $conn->query("
        SELECT t.*, u.full_name, u.trust_score, u.verification_status, u.is_online, u.last_seen
        FROM trips t
        JOIN users u ON t.user_id = u.id
        WHERE t.share_code = '$share_code' AND t.status = 'active'
        LIMIT 1
    ");

    return $stmt->fetch_assoc();
}

function enable_trip_sharing(int $trip_id, int $user_id, bool $escort_enabled = false): bool {
    global $conn;

    // Verify ownership
    $check = $conn->prepare('SELECT id FROM trips WHERE id = ? AND user_id = ?');
    $check->bind_param('ii', $trip_id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        return false;
    }

    $stmt = $conn->prepare('UPDATE trips SET is_shared = 1, escort_enabled = ? WHERE id = ?');
    $escort = $escort_enabled ? 1 : 0;
    $stmt->bind_param('ii', $escort, $trip_id);
    return $stmt->execute();
}

function disable_trip_sharing(int $trip_id, int $user_id): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE trips SET is_shared = 0 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $trip_id, $user_id);
    return $stmt->execute();
}

function increment_share_view(string $share_code): void {
    global $conn;

    $conn->query("UPDATE trips SET share_view_count = share_view_count + 1 WHERE share_code = '$share_code'");
}

function generate_shareable_link(string $share_code): string {
    return APP_URL . '/share.php?code=' . $share_code;
}

// =============================================
// CHECK-IN SYSTEM FUNCTIONS
// =============================================

function ensure_checkin_tables(): void {
    global $conn;

    $result = $conn->query("SHOW TABLES LIKE 'trip_checkins'");
    if ($result && $result->num_rows == 0) {
        $conn->query("
            CREATE TABLE trip_checkins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT NOT NULL,
                user_id INT NOT NULL,
                checkin_type ENUM('start', 'periodic', 'manual', 'sos') DEFAULT 'manual',
                status ENUM('pending', 'checked_in', 'missed', 'escalated') DEFAULT 'pending',
                scheduled_at DATETIME NULL,
                checked_in_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_trip_user (trip_id, user_id),
                INDEX idx_scheduled (scheduled_at, status)
            )
        ");

        $conn->query("
            CREATE TABLE checkin_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                auto_checkin_enabled TINYINT(1) DEFAULT 1,
                checkin_interval_minutes INT DEFAULT 30,
                reminder_before_minutes INT DEFAULT 5,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }
}

ensure_checkin_tables();

function get_user_checkin_settings(int $user_id): array {
    global $conn;

    $stmt = $conn->prepare('SELECT * FROM checkin_settings WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row;
    }

    // Return defaults
    return [
        'auto_checkin_enabled' => 1,
        'checkin_interval_minutes' => 30,
        'reminder_before_minutes' => 5
    ];
}

function save_checkin_settings(int $user_id, array $settings): bool {
    global $conn;

    $auto = isset($settings['auto_checkin_enabled']) ? 1 : 0;
    $interval = (int) ($settings['checkin_interval_minutes'] ?? 30);
    $reminder = (int) ($settings['reminder_before_minutes'] ?? 5);

    $stmt = $conn->prepare("
        INSERT INTO checkin_settings (user_id, auto_checkin_enabled, checkin_interval_minutes, reminder_before_minutes)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            auto_checkin_enabled = VALUES(auto_checkin_enabled),
            checkin_interval_minutes = VALUES(checkin_interval_minutes),
            reminder_before_minutes = VALUES(reminder_before_minutes)
    ");
    $stmt->bind_param('iiii', $user_id, $auto, $interval, $reminder);

    return $stmt->execute();
}

function schedule_checkins_for_trip(int $trip_id, int $user_id, int $interval_minutes = 30): bool {
    global $conn;

    // Get trip info
    $trip = $conn->query("SELECT * FROM trips WHERE id = $trip_id")->fetch_assoc();
    if (!$trip) return false;

    $start_time = strtotime($trip['travel_date'] . ' ' . $trip['travel_time']);
    $end_time = $start_time + (8 * 3600); // Assume 8 hours max trip duration

    // Clear existing scheduled checkins
    $conn->query("DELETE FROM trip_checkins WHERE trip_id = $trip_id AND user_id = $user_id AND status = 'pending'");

    // Schedule new checkins
    $current = $start_time;
    $stmt = $conn->prepare('INSERT INTO trip_checkins (trip_id, user_id, checkin_type, status, scheduled_at) VALUES (?, ?, ?, ?, ?)');

    while ($current <= $end_time) {
        $scheduled = date('Y-m-d H:i:s', $current);
        $type = $current == $start_time ? 'start' : 'periodic';
        $status = 'pending';

        $stmt->bind_param('iisss', $trip_id, $user_id, $type, $status, $scheduled);
        $stmt->execute();

        $current += $interval_minutes * 60;
    }

    return true;
}

function get_pending_checkins(int $user_id): array {
    global $conn;

    $stmt = $conn->query("
        SELECT tc.*, t.from_location, t.to_location, t.travel_date, t.travel_time
        FROM trip_checkins tc
        JOIN trips t ON tc.trip_id = t.id
        WHERE tc.user_id = $user_id
        AND tc.status = 'pending'
        AND tc.scheduled_at <= DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        AND t.status = 'active'
        ORDER BY tc.scheduled_at ASC
        LIMIT 10
    ");

    $checkins = [];
    while ($row = $stmt->fetch_assoc()) {
        $checkins[] = $row;
    }

    return $checkins;
}

function get_missed_checkins(int $user_id): int {
    global $conn;

    $stmt = $conn->prepare('
        SELECT COUNT(*) as count FROM trip_checkins
        WHERE user_id = ? AND status = "missed"
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return (int) $result->fetch_assoc()['count'];
}

function perform_checkin(int $checkin_id, int $user_id): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE trip_checkins SET status = "checked_in", checked_in_at = NOW() WHERE id = ? AND user_id = ? AND status = "pending"');
    $stmt->bind_param('ii', $checkin_id, $user_id);

    return $stmt->execute();
}

function skip_checkin(int $checkin_id, int $user_id): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE trip_checkins SET status = "missed" WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $checkin_id, $user_id);

    return $stmt->execute();
}

function escalate_missed_checkin(int $checkin_id): void {
    global $conn;

    $conn->query("UPDATE trip_checkins SET status = 'escalated' WHERE id = $checkin_id");

    // Get checkin info
    $checkin = $conn->query("SELECT * FROM trip_checkins WHERE id = $checkin_id")->fetch_assoc();
    if ($checkin) {
        // Get trusted contacts
        $contacts = $conn->query("SELECT * FROM trusted_contacts WHERE user_id = " . $checkin['user_id']);
        while ($contact = $contacts->fetch_assoc()) {
            $message = "Safety Alert: " . $_SESSION['user_name'] . " missed a check-in on their trip. Last check-in was scheduled at " . $checkin['scheduled_at'];
            sendNotification($checkin['user_id'], 'Check-in Missed', $message, 'safety', $checkin_id);
        }
    }
}

function get_trip_checkin_summary(int $trip_id, int $user_id): array {
    global $conn;

    $total = $conn->query("SELECT COUNT(*) as c FROM trip_checkins WHERE trip_id = $trip_id AND user_id = $user_id")->fetch_assoc()['c'];
    $checked_in = $conn->query("SELECT COUNT(*) as c FROM trip_checkins WHERE trip_id = $trip_id AND user_id = $user_id AND status = 'checked_in'")->fetch_assoc()['c'];
    $missed = $conn->query("SELECT COUNT(*) as c FROM trip_checkins WHERE trip_id = $trip_id AND user_id = $user_id AND status = 'missed'")->fetch_assoc()['c'];

    return [
        'total' => $total,
        'checked_in' => $checked_in,
        'missed' => $missed,
        'rate' => $total > 0 ? round(($checked_in / $total) * 100) : 0
    ];
}

// =============================================
// ESCORT MODE FUNCTIONS
// =============================================

function ensure_escort_tables(): void {
    global $conn;

    $result = $conn->query("SHOW TABLES LIKE 'escort_sessions'");
    if ($result && $result->num_rows == 0) {
        $conn->query("
            CREATE TABLE escort_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT NOT NULL,
                user_id INT NOT NULL,
                escort_code VARCHAR(32) NOT NULL UNIQUE,
                is_active TINYINT(1) DEFAULT 1,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                last_location_lat DECIMAL(10, 8) NULL,
                last_location_lng DECIMAL(11, 8) NULL,
                last_updated DATETIME NULL,
                view_count INT DEFAULT 0,
                INDEX idx_escort_code (escort_code),
                INDEX idx_user (user_id),
                INDEX idx_active (is_active, expires_at)
            )
        ");
    }
}

ensure_escort_tables();

function generate_escort_code(): string {
    return 'ESCORT-' . strtoupper(bin2hex(random_bytes(4)));
}

function create_escort_session(int $trip_id, int $user_id, int $duration_hours = 24): ?array {
    global $conn;

    // Verify trip ownership
    $trip = $conn->query("SELECT * FROM trips WHERE id = $trip_id AND user_id = $user_id")->fetch_assoc();
    if (!$trip) return null;

    // End any existing active sessions
    $conn->query("UPDATE escort_sessions SET is_active = 0 WHERE trip_id = $trip_id AND user_id = $user_id AND is_active = 1");

    $escort_code = generate_escort_code();
    $expires_at = date('Y-m-d H:i:s', time() + ($duration_hours * 3600));

    $stmt = $conn->prepare('INSERT INTO escort_sessions (trip_id, user_id, escort_code, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiss', $trip_id, $user_id, $escort_code, $expires_at);

    if ($stmt->execute()) {
        return [
            'id' => $conn->insert_id,
            'escort_code' => $escort_code,
            'expires_at' => $expires_at
        ];
    }

    return null;
}

function get_active_escort_session(int $trip_id, int $user_id): ?array {
    global $conn;

    $stmt = $conn->query("
        SELECT es.*, t.from_location, t.to_location, t.travel_date, t.travel_time, t.transport_mode
        FROM escort_sessions es
        JOIN trips t ON es.trip_id = t.id
        WHERE es.trip_id = $trip_id AND es.user_id = $user_id AND es.is_active = 1 AND es.expires_at > NOW()
    ");

    return $stmt->fetch_assoc();
}

function end_escort_session(int $session_id, int $user_id): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE escort_sessions SET is_active = 0 WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $session_id, $user_id);

    return $stmt->execute();
}

function get_escort_session_by_code(string $code): ?array {
    global $conn;

    $stmt = $conn->prepare('SELECT es.*, t.from_location, t.to_location, t.travel_date, t.travel_time, t.transport_mode, u.full_name, u.phone
                           FROM escort_sessions es
                           JOIN trips t ON es.trip_id = t.id
                           JOIN users u ON t.user_id = u.id
                           WHERE es.escort_code = ? AND es.is_active = 1 AND es.expires_at > NOW()');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

function update_escort_location(int $session_id, float $lat, float $lng): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE escort_sessions SET last_location_lat = ?, last_location_lng = ?, last_updated = NOW() WHERE id = ?');
    $stmt->bind_param('dii', $lat, $lng, $session_id);

    return $stmt->execute();
}

function increment_escort_views(int $session_id): void {
    global $conn;

    $conn->query("UPDATE escort_sessions SET view_count = view_count + 1 WHERE id = $session_id");
}

function generate_escort_link(string $escort_code): string {
    return APP_URL . '/escort.php?code=' . $escort_code;
}

function get_user_escort_sessions(int $user_id): array {
    global $conn;

    $stmt = $conn->query("
        SELECT es.*, t.from_location, t.to_location, t.travel_date
        FROM escort_sessions es
        JOIN trips t ON es.trip_id = t.id
        WHERE es.user_id = $user_id
        ORDER BY es.started_at DESC
        LIMIT 20
    ");

    $sessions = [];
    while ($row = $stmt->fetch_assoc()) {
        $sessions[] = $row;
    }

    return $sessions;
}

// =============================================
// FAKE TRIP DETECTION FUNCTIONS
// =============================================

function ensure_trip_flags_table(): void {
    global $conn;

    $result = $conn->query("SHOW TABLES LIKE 'trip_flags'");
    if ($result && $result->num_rows == 0) {
        $conn->query("
            CREATE TABLE trip_flags (
                id INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT NOT NULL,
                flag_type ENUM('suspicious_price', 'vague_details', 'multiple_flags', 'reported') DEFAULT 'suspicious_price',
                severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
                reason TEXT NULL,
                reported_by INT NULL,
                status ENUM('pending', 'reviewed', 'cleared', 'confirmed') DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                reviewed_at DATETIME NULL,
                reviewed_by INT NULL,
                INDEX idx_trip (trip_id),
                INDEX idx_status (status),
                INDEX idx_severity (severity)
            )
        ");
    }
}

ensure_trip_flags_table();

function flag_trip(int $trip_id, string $flag_type, string $severity = 'low', ?string $reason = null, ?int $reported_by = null): bool {
    global $conn;

    $stmt = $conn->prepare('INSERT INTO trip_flags (trip_id, flag_type, severity, reason, reported_by) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $trip_id, $flag_type, $severity, $reason, $reported_by);

    return $stmt->execute();
}

function get_trip_flags(int $trip_id): array {
    global $conn;

    $stmt = $conn->query("SELECT * FROM trip_flags WHERE trip_id = $trip_id ORDER BY created_at DESC");

    $flags = [];
    while ($row = $stmt->fetch_assoc()) {
        $flags[] = $row;
    }

    return $flags;
}

function get_flagged_trips(?string $severity = null, string $status = 'pending'): array {
    global $conn;

    $query = "SELECT tf.*, t.*, u.full_name as owner_name
              FROM trip_flags tf
              JOIN trips t ON tf.trip_id = t.id
              JOIN users u ON t.user_id = u.id
              WHERE tf.status = '$status'";

    if ($severity) {
        $query .= " AND tf.severity = '$severity'";
    }

    $query .= " ORDER BY
                CASE tf.severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END, tf.created_at DESC";

    return $conn->query($query);
}

function review_trip_flag(int $flag_id, string $status, int $reviewer_id): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE trip_flags SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
    $stmt->bind_param('sii', $status, $reviewer_id, $flag_id);

    return $stmt->execute();
}

function auto_flag_suspicious_trips(): int {
    global $conn;

    $flagged_count = 0;

    // Flag trips with very low seats but no description
    $trips = $conn->query("
        SELECT t.* FROM trips t
        WHERE t.status = 'active'
        AND (
            t.seats_available > 5
            OR t.from_location = t.to_location
            OR CHAR_LENGTH(t.from_location) < 3
            OR CHAR_LENGTH(t.to_location) < 3
        )
    ");

    while ($trip = $trips->fetch_assoc()) {
        // Check if already flagged
        $existing = $conn->query("SELECT id FROM trip_flags WHERE trip_id = " . $trip['id'] . " AND flag_type = 'vague_details' AND status = 'pending'");
        if ($existing->num_rows == 0) {
            $reason = 'Trip details appear vague or suspicious';
            flag_trip($trip['id'], 'vague_details', 'medium', $reason);
            $flagged_count++;
        }
    }

    return $flagged_count;
}

function get_user_trip_flag_count(int $user_id): int {
    global $conn;

    $stmt = $conn->query("
        SELECT COUNT(DISTINCT t.id) as count
        FROM trips t
        JOIN trip_flags tf ON t.id = tf.trip_id
        WHERE t.user_id = $user_id AND tf.status = 'pending'
    ");

    return (int) $stmt->fetch_assoc()['count'];
}

function is_trip_flagged(int $trip_id): bool {
    global $conn;

    $stmt = $conn->query("SELECT id FROM trip_flags WHERE trip_id = $trip_id AND status = 'pending' AND severity IN ('high', 'critical') LIMIT 1");

    return $stmt->num_rows > 0;
}

// =============================================
// COST SPLITTING FUNCTIONS
// =============================================

function ensure_cost_split_tables(): void {
    global $conn;

    $result = $conn->query("SHOW TABLES LIKE 'trip_expenses'");
    if ($result && $result->num_rows == 0) {
        $conn->query("
            CREATE TABLE trip_expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                trip_id INT NOT NULL,
                group_id INT NOT NULL,
                added_by INT NOT NULL,
                description VARCHAR(255) NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                category ENUM('fuel', 'toll', 'food', 'ticket', 'accommodation', 'other') DEFAULT 'other',
                receipt_url VARCHAR(500) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_trip (trip_id),
                INDEX idx_group (group_id)
            )
        ");

        $conn->query("
            CREATE TABLE expense_splits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                expense_id INT NOT NULL,
                user_id INT NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                settled TINYINT(1) DEFAULT 0,
                settled_at DATETIME NULL,
                INDEX idx_expense (expense_id),
                INDEX idx_user (user_id)
            )
        ");
    }
}

ensure_cost_split_tables();

function add_trip_expense(int $trip_id, int $group_id, int $user_id, string $description, float $amount, string $category = 'other'): ?int {
    global $conn;

    $stmt = $conn->prepare('INSERT INTO trip_expenses (trip_id, group_id, added_by, description, amount, category) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiisds', $trip_id, $group_id, $user_id, $description, $amount, $category);

    if ($stmt->execute()) {
        return $conn->insert_id;
    }

    return null;
}

function get_trip_expenses(int $group_id): array {
    global $conn;

    $stmt = $conn->query("
        SELECT te.*, u.full_name as added_by_name
        FROM trip_expenses te
        JOIN users u ON te.added_by = u.id
        WHERE te.group_id = $group_id
        ORDER BY te.created_at DESC
    ");

    $expenses = [];
    while ($row = $stmt->fetch_assoc()) {
        $row['splits'] = get_expense_splits($row['id']);
        $expenses[] = $row;
    }

    return $expenses;
}

function get_expense_splits(int $expense_id): array {
    global $conn;

    $stmt = $conn->query("
        SELECT es.*, u.full_name
        FROM expense_splits es
        JOIN users u ON es.user_id = u.id
        WHERE es.expense_id = $expense_id
    ");

    $splits = [];
    while ($row = $stmt->fetch_assoc()) {
        $splits[$row['user_id']] = $row;
    }

    return $splits;
}

function split_expense_equally(int $expense_id, array $user_ids): bool {
    global $conn;

    if (empty($user_ids)) return false;

    $amount_per = 0;
    $expense = $conn->query("SELECT amount FROM trip_expenses WHERE id = $expense_id")->fetch_assoc();
    if ($expense) {
        $amount_per = $expense['amount'] / count($user_ids);
    }

    $stmt = $conn->prepare('INSERT INTO expense_splits (expense_id, user_id, amount) VALUES (?, ?, ?)');

    foreach ($user_ids as $user_id) {
        $stmt->bind_param('iid', $expense_id, $user_id, $amount_per);
        $stmt->execute();
    }

    return true;
}

function get_group_balances(int $group_id): array {
    global $conn;

    // Get all group members
    $members = $conn->query("SELECT user_id FROM group_members WHERE group_id = $group_id");
    $member_ids = [];
    while ($m = $members->fetch_assoc()) {
        $member_ids[] = $m['user_id'];
    }

    if (empty($member_ids)) return [];

    // Calculate who owes whom
    $balances = [];
    foreach ($member_ids as $id) {
        $balances[$id] = 0;
    }

    // Get all expenses for this group
    $expenses = $conn->query("SELECT te.*, es.user_id as paid_by FROM trip_expenses te JOIN expense_splits es ON te.id = es.expense_id WHERE te.group_id = $group_id");

    while ($e = $expenses->fetch_assoc()) {
        // Who paid gets credited
        $balances[$e['added_by']] += $e['amount'];
    }

    return $balances;
}

function get_total_trip_cost(int $group_id): float {
    global $conn;

    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM trip_expenses WHERE group_id = $group_id");

    return (float) $result->fetch_assoc()['total'];
}

function mark_split_settled(int $expense_id, int $user_id): bool {
    global $conn;

    $stmt = $conn->prepare('UPDATE expense_splits SET settled = 1, settled_at = NOW() WHERE expense_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $expense_id, $user_id);

    return $stmt->execute();
}

function get_user_owes_group(int $user_id, int $group_id): float {
    global $conn;

    $result = $conn->query("
        SELECT COALESCE(SUM(es.amount), 0) as owed
        FROM expense_splits es
        JOIN trip_expenses te ON es.expense_id = te.id
        WHERE te.group_id = $group_id AND es.user_id = $user_id AND es.settled = 0
    ");

    return (float) $result->fetch_assoc()['owed'];
}
?>
