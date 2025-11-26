<?php
session_name("admin_session");
session_start();

require_once __DIR__ . '/../config/config.php';

/* ========= ROUTER ========= */
$page = $_GET['page'] ?? 'dashboard';

$valid_pages = [
    // trang hệ thống
    'dashboard'      => 'dashboard.php',
    'products'       => 'products.php',
    'orders'         => 'orders.php',
    'users'          => 'users.php',
    'search'         => 'search.php',  
    
    // trang auth
    'login-admin'    => 'login-admin.php',
    'register-admin' => 'register-admin.php',
    'logout'         => 'logout.php',
];

$auth_pages = ['login-admin', 'register-admin', 'logout'];

/* === CHECK QUYỀN ADMIN === */
if (!in_array($page, $auth_pages)) {
    if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
        header("Location: index.php?page=login-admin&return=/fashionstore/admin/");
        exit;
    }
}

/* Nếu page không tồn tại → trả về login */
if (!isset($valid_pages[$page])) {
    $page = 'login-admin';
}

?>
<!DOCTYPE html>
<html lang="vi">

<?php if (!in_array($page, $auth_pages)): ?>
    <!-- Trang hệ thống có giao diện admin -->
    <?php include __DIR__ . "/layout/head.php"; ?>
<?php endif; ?>

<body>

<?php if (!in_array($page, $auth_pages)): ?>
    <?php ob_start(); ?>
    <?php include __DIR__ . "/layout/sidebar.php"; ?>
    <?php ob_end_flush(); ?>
    <div class="main">
<?php endif; ?>

<!-- NỘI DUNG TRANG -->
<?php include __DIR__ . "/function/" . $valid_pages[$page]; ?>

<?php if (!in_array($page, $auth_pages)): ?>
    </div>
    <?php include __DIR__ . "/layout/footer.php"; ?>
<?php endif; ?>

</body>
</html>
