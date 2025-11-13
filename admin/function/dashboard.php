<?php
require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: login-admin.php?return=' . urlencode('dashboard.php'));
    exit;
}

function h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$stats = [
    'orders_total'     => 0,
    'orders_completed' => 0,
    'products_total'   => 0,
    'customers_total'  => 0,
];

if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders")) {
    $stats['orders_total'] = (int)mysqli_fetch_assoc($res)['c'];
}

// Đếm đơn hoàn thành: status_id = 4 (Hoàn thành)
if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status_id = 4")) {
    $stats['orders_completed'] = (int)mysqli_fetch_assoc($res)['c'];
}

if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM products")) {
    $stats['products_total'] = (int)mysqli_fetch_assoc($res)['c'];
}

if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role_id <> 1")) {
    $stats['customers_total'] = (int)mysqli_fetch_assoc($res)['c'];
}

$recent_orders = [];
// Query đơn hàng gần đây với JOIN bảng order_status để lấy status_name
$sql_recent_orders = "
    SELECT 
        o.order_id AS id,
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

if ($res = mysqli_query($conn, $sql_recent_orders)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recent_orders[] = $row;
    }
}

function order_status_meta($status)
{
    if (empty($status)) {
        return ['class' => 'order-status pending', 'label' => 'Chưa xác định'];
    }
    
    $status = trim((string)$status);
    $status_lower = mb_strtolower($status, 'UTF-8');

    // Xử lý status_name tiếng Việt từ bảng order_status
    if (mb_strpos($status_lower, 'hoàn thành') !== false || mb_strpos($status_lower, 'completed') !== false) {
        return ['class' => 'order-status done', 'label' => $status];
    } elseif (mb_strpos($status_lower, 'hủy') !== false || mb_strpos($status_lower, 'cancelled') !== false || mb_strpos($status_lower, 'canceled') !== false) {
        return ['class' => 'order-status cancel', 'label' => $status];
    } elseif (mb_strpos($status_lower, 'giao hàng') !== false || mb_strpos($status_lower, 'shipped') !== false) {
        return ['class' => 'order-status done', 'label' => $status];
    } elseif (mb_strpos($status_lower, 'xử lý') !== false || mb_strpos($status_lower, 'processing') !== false) {
        return ['class' => 'order-status pending', 'label' => $status];
    } elseif (mb_strpos($status_lower, 'xác nhận') !== false || mb_strpos($status_lower, 'pending') !== false) {
        return ['class' => 'order-status pending', 'label' => $status];
    } else {
        // Mặc định hiển thị status_name như nó có
        return ['class' => 'order-status pending', 'label' => $status];
    }
}

function format_money($value)
{
    return number_format((float)$value, 0, ',', '.') . 'đ';
}
?>
<?php include "../layout/head.php"; ?>
<?php include "../layout/sidebar.php"; ?>
            <h1 class="page-title">Tổng quan hệ thống</h1>

            <section id="dashboard">
                <div class="box-container">
                    <div class="box box1">
                        <div class="text">
                            <h2 class="topic-heading"><?php echo $stats['orders_total']; ?></h2>
                            <h2 class="topic">Tổng đơn hàng</h2>
                        </div>
                        <img
                            src="https://media.geeksforgeeks.org/wp-content/uploads/20221210184645/Untitled-design-(31).png"
                            alt="orders">
                    </div>

                    <div class="box box2">
                        <div class="text">
                            <h2 class="topic-heading"><?php echo $stats['orders_completed']; ?></h2>
                            <h2 class="topic">Đơn hoàn thành</h2>
                        </div>
                        <img
                            src="https://media.geeksforgeeks.org/wp-content/uploads/20221210185030/14.png"
                            alt="completed">
                    </div>

                    <div class="box box3">
                        <div class="text">
                            <h2 class="topic-heading"><?php echo $stats['products_total']; ?></h2>
                            <h2 class="topic">Sản phẩm</h2>
                        </div>
                        <img
                            src="https://media.geeksforgeeks.org/wp-content/uploads/20221210184645/Untitled-design-(32).png"
                            alt="products">
                    </div>

                    <div class="box box4">
                        <div class="text">
                            <h2 class="topic-heading"><?php echo $stats['customers_total']; ?></h2>
                            <h2 class="topic">Khách hàng</h2>
                        </div>
                        <img
                            src="https://media.geeksforgeeks.org/wp-content/uploads/20221210185029/13.png"
                            alt="customers">
                    </div>
                </div>

                <div class="report-container">
                    <div class="report-header">
                        <h1 class="recent-Articles">Đơn hàng gần đây</h1>
                        <button class="view" onclick="window.location.href='orders.php'">Xem tất cả</button>
                    </div>

                    <div class="report-body">
                        <div class="report-topic-heading">
                            <h3 class="t-op">Mã đơn</h3>
                            <h3 class="t-op">Khách hàng</h3>
                            <h3 class="t-op">Tổng tiền</h3>
                            <h3 class="t-op">Trạng thái</h3>
                        </div>

                        <div class="items">
                            <?php if ($recent_orders): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <?php $status_meta = order_status_meta($order['status']); ?>
                                    <div class="item1">
                                        <h3 class="t-op-nextlvl">#<?php echo h($order['id']); ?></h3>
                                        <h3 class="t-op-nextlvl"><?php echo h($order['customer_name']); ?></h3>
                                        <h3 class="t-op-nextlvl"><?php echo format_money($order['total_amount']); ?></h3>
                                        <h3 class="t-op-nextlvl">
                                            <span class="<?php echo h($status_meta['class']); ?>">
                                                <?php echo h($status_meta['label']); ?>
                                            </span>
                                        </h3>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="item1">
                                    <h3 class="t-op-nextlvl" style="grid-column: span 4;">Chưa có đơn hàng nào</h3>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
<?php include "../layout/footer.php"; ?>
</body>
</html>