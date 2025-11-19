<?php
require_once __DIR__ . '/../../config/config.php';

$layout = 'main';
$page_title = 'Xác nhận đơn hàng - FashionStore';

// =============================
// HỦY ĐƠN HÀNG
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_order') {

    if (empty($_SESSION['user'])) {
        header('Location: /fashionstore/index.php?page=login');
        exit;
    }

    $order_id = (int)$_POST['order_id'];
    $user_id  = (int)$_SESSION['user']['user_id'];

    $stmt = mysqli_prepare($conn, "SELECT status_id FROM orders WHERE order_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo "<p>Không tìm thấy đơn hàng.</p>";
        exit;
    }

    if ($row['status_id'] != 1) {
        echo "<p>Đơn hàng đã được xác nhận hoặc đang xử lý — KHÔNG thể hủy.</p>";
        exit;
    }

    $up = mysqli_prepare($conn, "UPDATE orders SET status_id = 5 WHERE order_id = ?");
    mysqli_stmt_bind_param($up, "i", $order_id);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);

    header("Location: index.php?page=purchase_order");
    exit;
}



// =============================
// XEM DANH SÁCH ĐƠN HÀNG
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (empty($_SESSION['user'])) {
        header('Location: /fashionstore/index.php?page=login');
        exit;
    }

    $user_id = $_SESSION['user']['user_id'];

    $orders = [];

    $stmt = mysqli_prepare($conn, "
        SELECT o.*, s.status_name
        FROM orders o
        LEFT JOIN order_status s ON o.status_id = s.status_id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($r = mysqli_fetch_assoc($result)) {
        $orders[] = $r;
    }

    mysqli_stmt_close($stmt);

    ob_start();
    ?>
<main style="padding:120px 20px;">
    <div class="orders-container">
        <h1>Đơn hàng của tôi</h1>

        <?php if (empty($orders)): ?>
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="index.php?page=product" class="btn-primary">Mua sắm ngay</a>

        <?php else: ?>

            <div class="orders-list">
                <?php foreach ($orders as $order): ?>

                    <?php
                    // Mapping status → class màu
                    switch ($order['status_id']) {
                        case 3:
                        case 4:
                            $status_class = 'done';
                            break;
                        case 5:
                            $status_class = 'cancel';
                            break;
                        default:
                            $status_class = 'pending';
                    }
                    ?>

                    <div class="order-card">

                        <div class="order-header">
                            <div class="order-info">
                                <h3>Mã đơn hàng: <?= htmlspecialchars($order['id_order']) ?></h3>
                                <p>Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                            </div>

                            <div class="order-status">
                                <span class="status <?= $status_class ?>">
                                    <?= htmlspecialchars($order['status_name']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-items">
                            <?php
                            $items = json_decode($order['items'], true);
                            if ($items):
                                foreach ($items as $it):
                            ?>
                                <div class="order-item">
                                    <img src="<?= htmlspecialchars($it['image'] ?? '/fashionstore/uploads/no-image.jpg') ?>">

                                    <div class="item-details">
                                        <h4><?= htmlspecialchars($it['product_name']) ?></h4>
                                        <p>Số lượng: <?= (int)$it['quantity'] ?></p>

                                        <?php if (!empty($it['variant'])): ?>
                                            <p>Size: <?= htmlspecialchars($it['variant']) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="item-price">
                                        <?= number_format($it['price'] * $it['quantity'], 0, ',', '.') ?>đ
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <div class="order-total">
                            <?php if ($order['discount_amount'] > 0): ?>
                                <p><strong>Giảm giá:</strong> -<?= number_format($order['discount_amount'], 0, ',', '.') ?>đ</p>
                            <?php endif; ?>

                            <p><strong>Tổng tiền:</strong> <?= number_format($order['total_amount'], 0, ',', '.') ?>đ</p>
                        </div>

                        <?php if ($order['status_id'] == 1): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="cancel_order">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <button class="btn-outline" style="color:red;border-color:red;margin-top:10px;">
                                    Hủy đơn hàng
                                </button>
                            </form>
                        <?php elseif ($order['status_id'] == 5): ?>
                            <p style="color:red;margin-top:10px;"><b>Đơn hàng đã hủy</b></p>
                        <?php else: ?>
                            <p style="color:#555;margin-top:10px;">(Không thể hủy đơn)</p>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</main>


    <?php
    $content = ob_get_clean();
    require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
    exit;
}



// TẠO ĐƠN HÀNG MỚI


if (empty($_SESSION['user'])) {
    echo "<p>Bạn cần đăng nhập.</p>";
    exit;
}

$user_id = $_SESSION['user']['user_id'];


// Validate form
$required = ['full_name', 'phone', 'city', 'district', 'address'];
$data = [];

foreach ($required as $f) {
    $v = trim($_POST[$f] ?? '');
    if ($v === '') {
        echo "<p>Trường $f là bắt buộc.</p>";
        exit;
    }
    $data[$f] = $v;
}

$data['note'] = trim($_POST['note'] ?? '');


// Lấy giỏ
$checkout = $_SESSION['checkout_order'] ?? null;
if (empty($checkout['items'])) {
    echo "<p>Giỏ hàng rỗng hoặc không hợp lệ.</p>";
    exit;
}


// 🔥 LẤY THÔNG TIN GIẢM GIÁ
$promotion_id   = $_POST['promotion_id']   ?? null;
$discount_amount = $_POST['discount_amount'] ?? 0;
$discount_amount = (int)$discount_amount;

// 🔥 Tổng tiền cuối cùng sau giảm
$final_total = $checkout['total_amount'] - $discount_amount;
if ($final_total < 0) $final_total = 0;


// Tạo mã đơn hàng ODRxxxxx
$res = mysqli_query($conn, "
    SELECT id_order 
    FROM orders 
    WHERE id_order IS NOT NULL 
    ORDER BY id_order DESC 
    LIMIT 1
");
$row = mysqli_fetch_assoc($res);

if (!$row) {
    $new_id_order = "ODR00001";
} else {
    $lastNumber = intval(substr($row['id_order'], 3));
    $nextNumber = $lastNumber + 1;
    $new_id_order = "ODR" . str_pad($nextNumber, 5, "0", STR_PAD_LEFT);
}




// INSERT ĐƠN HÀNG
// 
$shipping_address = $data['address'] . ', ' . $data['district'] . ', ' . $data['city'];
$items_json = json_encode($checkout['items'], JSON_UNESCAPED_UNICODE);

$stmt = mysqli_prepare($conn, "
    INSERT INTO orders (user_id, status_id, order_date, total_amount, discount_amount, promotion_id, shipping_address, items, id_order)
    VALUES (?, 1, NOW(), ?, ?, ?, ?, ?, ?)
");

mysqli_stmt_bind_param(
    $stmt,
    "idiisss",
    $user_id,
    $final_total,
    $discount_amount,
    $promotion_id,
    $shipping_address,
    $items_json,
    $new_id_order
);


mysqli_stmt_execute($stmt);
$order_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);




// XÓA SESSION GIỎ + KHUYẾN MÃI

unset($_SESSION['checkout_order']);
unset($_SESSION['applied_promo']);



// VIEW XÁC NHẬN
ob_start();
?>
<main style="padding:120px 20px;">
    <div class="po-container">

        <div class="po-header">
            <div class="status-icon"><i class="fas fa-check-circle"></i></div>
            <div class="status-text">
                <h2>Đặt hàng thành công</h2>
                <p>Mã đơn hàng: <b><?= htmlspecialchars($new_id_order) ?></b></p>
            </div>
        </div>

        <div class="po-card delivery-info">
            <h3>Địa chỉ nhận hàng</h3>
            <div class="info-content">
                <p class="name"><?= htmlspecialchars($data['full_name']) ?></p>
                <p class="phone"><?= htmlspecialchars($data['phone']) ?></p>
                <p class="address">
                    <?= htmlspecialchars($data['address']) ?>,
                    <?= htmlspecialchars($data['district']) ?>,
                    <?= htmlspecialchars($data['city']) ?>
                </p>
            </div>
        </div>

        <div class="po-card order-items">
            <h3>Sản phẩm đã đặt</h3>

            <div class="items-list">
                <?php foreach ($checkout['items'] as $item): ?>
                <div class="item">
                    <div class="item-image">
                        <img src="<?= htmlspecialchars($item['image'] ?? '/fashionstore/uploads/no-image.jpg') ?>" alt="">
                    </div>
                    <div class="item-details">
                        <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                        <?php if (!empty($item['variant'])): ?>
                        <p class="variant">Size: <?= htmlspecialchars($item['variant']) ?></p>
                        <?php endif; ?>
                        <p class="quantity">Số lượng: <?= (int)$item['quantity'] ?></p>
                    </div>
                    <div class="item-price">
                        <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Tổng tiền hàng</span>
                    <span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span>
                </div>

                <?php if ($discount_amount > 0): ?>
                <div class="summary-row">
                    <span>Giảm giá</span>
                    <span style="color:green;">-<?= number_format($discount_amount, 0, ',', '.') ?>đ</span>
                </div>
                <?php endif; ?>

                <div class="summary-row total">
                    <span>Tổng thanh toán</span>
                    <span><?= number_format($final_total, 0, ',', '.') ?>đ</span>
                </div>
            </div>

            <div class="po-actions">
                <a href="index.php?page=purchase_order" class="btn-outline">Xem đơn hàng</a>
                <a href="index.php?page=product" class="btn-primary">Tiếp tục mua sắm</a>
            </div>
        </div>

    </div>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
exit;

