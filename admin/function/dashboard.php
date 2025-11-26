<?php
require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: login-admin.php?return=' . urlencode('dashboard.php'));
    exit;
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function format_money($value) {
    return number_format((float)$value, 0, ',', '.') . 'đ';
}

// THỐNG KÊ
$stats = [
    'orders_total'     => 0,
    'orders_completed' => 0,
    'products_total'   => 0,
    'customers_total'  => 0,
];

$q1 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders");
$stats['orders_total'] = (int)mysqli_fetch_assoc($q1)['c'];

$q2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status_id = 4");
$stats['orders_completed'] = (int)mysqli_fetch_assoc($q2)['c'];

$q3 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM products");
$stats['products_total'] = (int)mysqli_fetch_assoc($q3)['c'];

$q4 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role_id <> 1");
$stats['customers_total'] = (int)mysqli_fetch_assoc($q4)['c'];


// ĐƠN HÀNG GẦN ĐÂY
$recent_orders = [];

$sql_recent_orders = "
    SELECT 
        o.id_order AS id,
        o.total_amount,
        os.status_name AS status,
        o.order_date AS created_at,
        COALESCE(u.full_name, u.email, 'Khách vãng lai') AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_status os ON o.status_id = os.status_id
    ORDER BY o.order_date DESC
    LIMIT 5
";

$res = mysqli_query($conn, $sql_recent_orders);
while ($row = mysqli_fetch_assoc($res)) {
    $recent_orders[] = $row;
}

function order_status_meta($status) {
    $lower = mb_strtolower($status ?? '', 'UTF-8');

    if (str_contains($lower, 'hoàn thành') || str_contains($lower, 'completed'))
        return ['class' => 'order-status done', 'label' => $status];

    if (str_contains($lower, 'hủy') || str_contains($lower, 'cancel'))
        return ['class' => 'order-status cancel', 'label' => $status];

    if (str_contains($lower, 'giao hàng') || str_contains($lower, 'ship'))
        return ['class' => 'order-status ship', 'label' => $status];

    return ['class' => 'order-status pending', 'label' => $status];
}

?>

<!-- HTML Dashboard bắt đầu từ đây, KHÔNG include layout -->
<h1 class="page-title">Tổng quan hệ thống</h1>

<section id="dashboard">

    <div class="box-container">
        <div class="box box1">
            <div class="text">
                <h2 class="topic-heading"><?= h($stats['orders_total']) ?></h2>
                <h2 class="topic">Tổng đơn hàng</h2>
            </div>
            <img src="https://media.geeksforgeeks.org/wp-content/uploads/20221210184645/Untitled-design-(31).png">
        </div>

        <div class="box box2">
            <div class="text">
                <h2 class="topic-heading"><?= h($stats['orders_completed']) ?></h2>
                <h2 class="topic">Đơn hoàn thành</h2>
            </div>
            <img src="https://media.geeksforgeeks.org/wp-content/uploads/20221210185030/14.png">
        </div>

        <div class="box box3">
            <div class="text">
                <h2 class="topic-heading"><?= h($stats['products_total']) ?></h2>
                <h2 class="topic">Sản phẩm</h2>
            </div>
            <img src="https://media.geeksforgeeks.org/wp-content/uploads/20221210184645/Untitled-design-(32).png">
        </div>

        <div class="box box4">
            <div class="text">
                <h2 class="topic-heading"><?= h($stats['customers_total']) ?></h2>
                <h2 class="topic">Khách hàng</h2>
            </div>
            <img src="https://media.geeksforgeeks.org/wp-content/uploads/20221210185029/13.png">
        </div>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h1>Đơn hàng gần đây</h1>
            <button class="view" onclick="window.location.href='index.php?page=orders'">Xem tất cả</button>
        </div>

        <div class="report-body">
            <div class="report-topic-heading">
                <h3>Mã đơn</h3>
                <h3>Khách hàng</h3>
                <h3>Tổng tiền</h3>
                <h3>Trạng thái</h3>
            </div>

            <div class="items">
                <?php foreach ($recent_orders as $order): ?>
                    <?php $m = order_status_meta($order['status']); ?>
                    <div class="item1">
                        <h3><?= h($order['id']) ?></h3>
                        <h3><?= h($order['customer_name']) ?></h3>
                        <h3><?= format_money($order['total_amount']) ?></h3>
                        <h3><span class="<?= $m['class'] ?>"><?= h($m['label']) ?></span></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</section>
