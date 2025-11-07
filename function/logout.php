<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect back to return URL if provided and safe
$return = isset($_GET['return']) ? trim($_GET['return']) : '';
function _is_safe_return($r) {
    if (!$r) return false;
    if (stripos($r, 'http://') !== false || stripos($r, 'https://') !== false) return false;
    if (strpos($r, '//') !== false) return false;
    if (strpos($r, '/fashionstore') === 0 || strpos($r, '/index.php') === 0 || strpos($r, 'index.php') === 0) return true;
    return false;
}

if (!empty($return) && _is_safe_return($return)) {
    header('Location: ' . $return);
} else {
    header('Location: /fashionstore/index.php');
}
exit;

?>