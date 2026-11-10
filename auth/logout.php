<?php
require_once '../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;

// Log activity before destroying session
if ($user_id) {
    log_activity($user_id, 'logout', 'User logged out');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
header('Location: ../index.php');
exit();
?>
