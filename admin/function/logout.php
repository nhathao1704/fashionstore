<?php
// Dùng cùng session name với login
session_name("admin_session");

session_start();

// Xóa toàn bộ session
$_SESSION = [];

// Xóa cookie session nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hủy session hoàn toàn
session_destroy();

// Chuyển về trang login admin
header("Location: login-admin.php");
exit;
