<?php
require_once __DIR__ . '/../../config/config.php';

$layout = 'main';
$page_title = 'Xác nhận đơn hàng - FashionStore';

// --------------------
// Xử lý GET: xem danh sách đơn hàng
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['user'])) {
        header('Location: /fashionstore/index.php?page=login');
        exit;
    }

    $user = $_SESSION['user'];
    $user_id = $user['user_id'] ?? null;

    if (!$user_id) {
        echo "<p>Không tìm thấy thông tin người dùng. <a href='index.php?page=login'>Đăng nhập lại</a></p>";
        exit;
    }

    $orders = [];
    if ($conn) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($order = mysqli_fetch_assoc($result)) {
            $orders[] = $order;
        }

        mysqli_stmt_close($stmt);
    }

    ob_start();
    ?>
    <main style="padding:120px 20px;">
        <div class="orders-container">
            <h1>Đơn hàng của tôi</h1>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>Bạn chưa có đơn hàng nào.</p>
                    <a href="index.php?page=product" class="btn-primary">Mua sắm ngay</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <h3>Mã đơn: <?= htmlspecialchars($order['order_id']) ?></h3>
                                    <p>Ngày đặt: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['order_date']))) ?></p>
                                </div>
                                <div class="order-status">
                                    <span class="status">Đã đặt hàng</span>
                                </div>
                            </div>

                            <div class="order-items">
                                <?php
                                $items = json_decode($order['items'], true);
                                if ($items):
                                    foreach ($items as $item):
                                        ?>
                                        <div class="order-item">
                                            <img src="<?= htmlspecialchars($item['image'] ?? '/fashionstore/uploads/no-image.jpg') ?>" alt="">
                                            <div class="item-details">
                                                <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                                                <p>Số lượng: <?= (int)$item['quantity'] ?></p>
                                                <?php if (!empty($item['variant'])): ?>
                                                    <p>Size: <?= htmlspecialchars($item['variant']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-price">
                                                <div class="sub"><?= number_format($item['price'], 0, ',', '.') ?>đ</div>
                                                <div class="total"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</div>
                                            </div>
                                        </div>
                                    <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>

                            <div class="order-total">
                                <p><strong>Tổng tiền: <?= number_format($order['total_amount'], 0, ',', '.') ?>đ</strong></p>
                            </div>
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

// --------------------
// Xử lý POST: tạo đơn hàng mới
// --------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fashionstore/index.php?page=pay');
    exit;
}

// Kiểm tra login
if (empty($_SESSION['user'])) {
    echo "<p>Bạn cần đăng nhập để đặt hàng. <a href='index.php?page=login'>Đăng nhập</a></p>";
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Lấy dữ liệu form
$required = ['full_name', 'phone', 'city', 'district', 'address'];
$data = [];
foreach ($required as $f) {
    $val = trim($_POST[$f] ?? '');
    if ($val === '') {
        echo "<p>Trường $f là bắt buộc. <a href=\"index.php?page=pay\">Quay lại</a></p>";
        exit;
    }
    $data[$f] = $val;
}

$data['customer_type'] = $_POST['customer_type'] ?? 'guest';
$data['note'] = trim($_POST['note'] ?? '');
$data['created_at'] = date('Y-m-d H:i:s');

// Lấy giỏ hàng từ session hoặc file token
$checkout = $_SESSION['checkout_order'] ?? null;
$token = $_POST['checkout_token'] ?? ($_GET['token'] ?? null);

if (!$checkout && $token) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $token);
    $file = __DIR__ . '/../../data/orders/' . $safe . '.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $checkout = json_decode($json, true);
    }
}

if (empty($checkout) || empty($checkout['items'])) {
    echo "<p>Không tìm thấy thông tin giỏ hàng để tạo đơn. <a href=\"index.php?page=cart\">Quay lại giỏ hàng</a></p>";
    exit;
}


$shipping_address = $data['address'] . ', ' . $data['district'] . ', ' . $data['city'];
$items_json = json_encode($checkout['items'], JSON_UNESCAPED_UNICODE);

$stmt = mysqli_prepare($conn, "
    INSERT INTO orders (user_id, status_id, order_date, total_amount, shipping_address, items)
    VALUES (?, 1, NOW(), ?, ?, ?)
");

mysqli_stmt_bind_param(
    $stmt,
    "idss",
    $user_id,
    $checkout['total_amount'],
    $shipping_address,
    $items_json
);

mysqli_stmt_execute($stmt);

// Lấy order_id vừa insert
$order_id = mysqli_insert_id($conn); // đây chính là order_id trong bảng
mysqli_stmt_close($stmt);



// Hiển thị Xác nhận đơn hàng

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
                <p>Mã đơn hàng: <?= htmlspecialchars($order_id) ?></p>
            </div>
        </div>

        <div class="po-card delivery-info">
            <h3><i class="fas fa-map-marker-alt"></i> Địa chỉ nhận hàng</h3>
            <div class="info-content">
                <p class="name"><?= htmlspecialchars($data['full_name']) ?></p>
                <p class="phone"><?= htmlspecialchars($data['phone']) ?></p>
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
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span>Miễn phí</span>
                </div>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
exit;
