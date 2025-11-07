<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../login.php');
    exit;
}

$users_count = 0;
$products_count = 0;
$orders_count = 0;

if ($r = mysqli_query($conn, "SELECT COUNT(*) c FROM users")) {
    $users_count = mysqli_fetch_assoc($r)['c'];
}
if ($r = mysqli_query($conn, "SELECT COUNT(*) c FROM products")) {
    $products_count = mysqli_fetch_assoc($r)['c'];
}
if ($r = mysqli_query($conn, "SELECT COUNT(*) c FROM orders")) {
    $orders_count = mysqli_fetch_assoc($r)['c'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin - Dashboard</title>
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
    <div class="container" style="max-width:1100px; margin:30px auto;">
        <h1 class="mb-4">Dashboard</h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Users</h5>
                        <div class="display-6"><?php echo $users_count; ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Products</h5>
                        <div class="display-6"><?php echo $products_count; ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Orders</h5>
                        <div class="display-6"><?php echo $orders_count; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 text-center">
            <a class="btn btn-primary" href="users.php">Quản lý Users</a>
            <a class="btn btn-primary" href="products.php">Quản lý Products</a>
            <a class="btn btn-primary" href="orders.php">Quản lý Orders</a>
            <a class="btn btn-secondary" href="../index.php">Về trang chủ</a>
        </div>
    </div>
</body>
</html>
