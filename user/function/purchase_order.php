<?php
require_once __DIR__ . '/../../config/config.php';

$layout = 'main';
$page_title = 'Xác nhận đơn hàng - FashionStore';


// ======================================================
// A. HỦY ĐƠN HÀNG
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_order') {

    if (empty($_SESSION['user'])) {
        header('Location: /fashionstore/index.php?page=login');
        exit;
    }

    $order_id = (int)$_POST['order_id'];
    $user_id  = (int)$_SESSION['user']['user_id'];

    // Kiểm tra trạng thái đơn
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

    // Chỉ hủy khi status = 1
    if ($row['status_id'] != 1) {
        echo "<p>Đơn hàng đã được xác nhận hoặc đang xử lý — KHÔNG thể hủy.</p>";
        echo "<p><a href='index.php?page=purchase_order'>Quay lại</a></p>";
        exit;
    }

    // Cập nhật trạng thái = 5 (ĐÃ HỦY)
    $up = mysqli_prepare($conn, "UPDATE orders SET status_id = 5 WHERE order_id = ?");
    mysqli_stmt_bind_param($up, "i", $order_id);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);

    header("Location: index.php?page=purchase_order");
    exit;
}



// ======================================================
// B. XEM DANH SÁCH ĐƠN HÀNG (GET)
// ======================================================

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

    // ==== VIEW ====
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
                        <div class="order-card">

                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Mã đơn hàng: <?= htmlspecialchars($order['id_order']) ?></h3>
                                    <p>Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                                </div>

                                <div class="order-status">
                                    <span class="status" data-status="<?= $order['status_id'] ?>">
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
                                <p><strong>Tổng tiền: <?= number_format($order['total_amount'], 0, ',', '.') ?>đ</strong></p>
                            </div>


                            <!-- HỦY ĐƠN -->
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



// ======================================================
// C. TẠO ĐƠN HÀNG MỚI (POST)
// ======================================================

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


// Lấy giỏ hàng
$checkout = $_SESSION['checkout_order'] ?? null;
if (empty($checkout['items'])) {
    echo "<p>Giỏ hàng rỗng hoặc không hợp lệ.</p>";
    exit;
}


// ======================================
// Tạo MÃ ĐƠN HÀNG ODR00001
// ======================================

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
    $lastNumber = intval(substr($row['id_order'], 3)); // lấy phần 00001
    $nextNumber = $lastNumber + 1;
    $new_id_order = "ODR" . str_pad($nextNumber, 5, "0", STR_PAD_LEFT);
}


// ======================================
// INSERT ĐƠN HÀNG
// ======================================

$shipping_address = $data['address'] . ', ' . $data['district'] . ', ' . $data['city'];
$items_json = json_encode($checkout['items'], JSON_UNESCAPED_UNICODE);

$stmt = mysqli_prepare($conn, "
    INSERT INTO orders (user_id, status_id, order_date, total_amount, shipping_address, items, id_order)
    VALUES (?, 1, NOW(), ?, ?, ?, ?)
");

mysqli_stmt_bind_param(
    $stmt,
    "idsss",
    $user_id,
    $checkout['total_amount'],
    $shipping_address,
    $items_json,
    $new_id_order
);

mysqli_stmt_execute($stmt);
$order_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);


// VIEW XÁC NHẬN
ob_start(); 
?> 
<main style="padding:120px 20px;"> 
    <div class="po-container"> 
        <div class="po-header"> 
            <div class="status-icon"> 
                <i class="fas fa-check-circle"></i> 
            </div> 
            <div class="status-text"> 
                <h2>Đặt hàng thành công</h2> 
                 <p>Mã đơn hàng: <b><?= htmlspecialchars($new_id_order) ?></b></p> 
            </div> 
        </div> 
        <div class="po-card delivery-info"> 
            <h3><i class="fas fa-map-marker-alt"></i> Địa chỉ nhận hàng</h3> 
            <div class="info-content"> 
                <p class="name">
                    <?= htmlspecialchars($data['full_name']) ?>
                </p> 
                <p class="phone">
                    <?= htmlspecialchars($data['phone']) ?>
                </p> 
                <p class="address"> 
                    <?= htmlspecialchars($data['address']) ?>, 
                    <?= htmlspecialchars($data['district']) ?>, 
                    <?= htmlspecialchars($data['city']) ?> 
                </p> 
                <?php if (!empty($data['note'])): ?> 
                <p class="note"><strong>Ghi chú:</strong> <?= htmlspecialchars($data['note']) ?></p> 
                <?php endif; ?> 
            </div> 
        </div> 
        <div class="po-card order-items"> 
            <h3><i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt</h3> 
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
                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ </div> 
                </div>
                 <?php endforeach; ?> 
            </div> 
            <div class="order-summary"> 
                <div class="summary-row"> 
                    <span>Tổng tiền hàng</span> 
                    <span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span> 
                </div> 
                <div class="summary-row"> 
                    <span>Phí vận chuyển</span> 
                    <span>Miễn phí</span> </div> 
                <div class="summary-row total"> 
                    <span>Tổng thanh toán</span> 
                    <span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span> 
                </div> 
            </div> 
            <div class="po-actions"> 
                <a href="index.php?page=purchase_order" class="btn-outline">Xem đơn hàng của tôi</a> 
                <a href="index.php?page=product" class="btn-primary">Tiếp tục mua sắm</a> 
            </div> 
        </div> 
    </div> 
</main> 
<?php $content = ob_get_clean(); 
require __DIR__ . '/../../includes/layouts/' . $layout . '.php'; 
exit;
