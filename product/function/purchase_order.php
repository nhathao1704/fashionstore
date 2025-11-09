<?php
require_once __DIR__ . '/../../config/config.php';

// Simple purchase order handler: accepts POST from pay.php
// Saves customer information under data/information/<order_id>.json
// Saves full order under data/purchase_orders/<order_id>.json

$layout = 'main';
$page_title = 'Xác nhận đơn hàng - FashionStore';

// Handle GET requests for viewing orders if user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['user'])) {
        header('Location: /fashionstore/index.php?page=login');
        exit;
    }
    // Show user's orders
    $user = $_SESSION['user'];
    $user_id = $user['user_id'] ?? null;

    if (!$user_id) {
        echo "<p>Không tìm thấy thông tin người dùng. <a href='index.php?page=login'>Đăng nhập lại</a></p>";
        exit;
    }

    // Fetch user orders from database
    $orders = [];
    if ($conn) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM PurchaseOrders WHERE user_id = ? ORDER BY created_at DESC");
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
                    <p>Ngày đặt: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['created_at']))) ?></p>
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
                        <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ
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

    <style>
    .orders-container {
      max-width: 800px;
      margin: 0 auto;
    }

    .orders-container h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #2c3e50;
    }

    .no-orders {
      text-align: center;
      padding: 50px 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .no-orders p {
      margin-bottom: 20px;
      color: #666;
    }

    .orders-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .order-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      background: #f8f8f8;
      border-bottom: 1px solid #eee;
    }

    .order-info h3 {
      margin: 0 0 5px 0;
      color: #2c3e50;
    }

    .order-info p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }

    .order-status .status {
      background: #27ae60;
      color: #fff;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .order-items {
      padding: 20px;
    }

    .order-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px 0;
      border-bottom: 1px solid #eee;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .order-item img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 6px;
    }

    .item-details h4 {
      margin: 0 0 5px 0;
      font-size: 16px;
      color: #2c3e50;
    }

    .item-details p {
      margin: 2px 0;
      color: #666;
      font-size: 14px;
    }

    .item-price {
      margin-left: auto;
      font-weight: 600;
      color: #e74c3c;
    }

    .order-total {
      padding: 20px;
      background: #f8f8f8;
      text-align: right;
      border-top: 1px solid #eee;
    }

    .btn-primary {
      background: #ff7a00;
      color: #fff;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 6px;
      display: inline-block;
      font-weight: 600;
    }

    .btn-primary:hover {
      background: #e66d00;
    }
    </style>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
    exit;
}

// Only accept POST for order creation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fashionstore/index.php?page=pay');
    exit;
}

// Collect and validate input
$required = ['full_name', 'phone', 'city', 'district', 'address'];
$data = [];
foreach ($required as $f) {
    $val = trim($_POST[$f] ?? '');
    if ($val === '') {
        // simple error response
        echo "<p>Trường $f là bắt buộc. <a href=\"index.php?page=pay\">Quay lại</a></p>";
        exit;
    }
    $data[$f] = $val;
}

$data['customer_type'] = $_POST['customer_type'] ?? 'guest';
$data['note'] = trim($_POST['note'] ?? '');
$data['created_at'] = date('Y-m-d H:i:s');

// Retrieve checkout payload: prefer session, fallback to token file if provided
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

// Ensure data directories exist
$infoDir = __DIR__ . '/../../data/information';
$orderDir = __DIR__ . '/../../data/purchase_orders';
if (!is_dir($infoDir)) @mkdir($infoDir, 0777, true);
if (!is_dir($orderDir)) @mkdir($orderDir, 0777, true);

// Create an order id and save both info and order
$orderId = 'PO' . strtoupper(uniqid());

$infoFile = $infoDir . '/' . $orderId . '.json';
$orderFile = $orderDir . '/' . $orderId . '.json';

$infoPayload = [
    'order_id' => $orderId,
    'customer' => $data,
    'created_at' => $data['created_at']
];
file_put_contents($infoFile, json_encode($infoPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$orderPayload = [
    'order_id' => $orderId,
    'user_id' => $checkout['user_id'] ?? null,
    'cart_id' => $checkout['cart_id'] ?? null,
    'items' => $checkout['items'],
    'total_items' => $checkout['total_items'] ?? 0,
    'total_amount' => $checkout['total_amount'] ?? 0,
    'customer' => $data,
    'created_at' => date('Y-m-d H:i:s')
];

// Insert into database
if ($conn) {
    $stmt = mysqli_prepare($conn, "INSERT INTO PurchaseOrders (order_id, user_id, items, total_amount, created_at) VALUES (?, ?, ?, ?, ?)");
    $items_json = json_encode($orderPayload['items']);
    mysqli_stmt_bind_param($stmt, "sisds", $orderId, $orderPayload['user_id'], $items_json, $orderPayload['total_amount'], $orderPayload['created_at']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

file_put_contents($orderFile, json_encode($orderPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// Optionally: clear session checkout and optionally clear cart in DB (not doing DB deletes automatically)
unset($_SESSION['checkout_order']);
unset($_SESSION['checkout_token']);

// Render a Shopee-like order confirmation
ob_start();
?>
<main style="padding:120px 20px;">
  <div class="po-container">
    <!-- Order Status Header -->
    <div class="po-header">
      <div class="status-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <div class="status-text">
        <h2>Đặt hàng thành công</h2>
        <p>Mã đơn hàng: <?= htmlspecialchars($orderId) ?></p>
      </div>
    </div>

    <!-- Delivery Info Card -->
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
        <p class="note">
          <strong>Ghi chú:</strong> <?= htmlspecialchars($data['note']) ?>
        </p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Order Items -->
    <div class="po-card order-items">
      <h3><i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt</h3>
      <div class="items-list">
        <?php foreach ($orderPayload['items'] as $item): ?>
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
          <span><?= number_format($orderPayload['total_amount'], 0, ',', '.') ?>đ</span>
        </div>
        <div class="summary-row">
          <span>Phí vận chuyển</span>
          <span>Miễn phí</span>
        </div>
        <div class="summary-row total">
          <span>Tổng thanh toán</span>
          <span><?= number_format($orderPayload['total_amount'], 0, ',', '.') ?>đ</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="po-actions">
      <a href="index.php?page=orders" class="btn-outline">Xem đơn hàng của tôi</a>
      <a href="index.php?page=product" class="btn-primary">Tiếp tục mua sắm</a>
    </div>
  </div>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';

exit;
