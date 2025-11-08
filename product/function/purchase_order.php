<?php
require_once __DIR__ . '/../../config/config.php';

// Simple purchase order handler: accepts POST from pay.php
// Saves customer information under data/information/<order_id>.json
// Saves full order under data/purchase_orders/<order_id>.json

$layout = 'main';
$page_title = 'Xác nhận đơn hàng - FashionStore';

// Only accept POST
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
file_put_contents($orderFile, json_encode($orderPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// Optionally: clear session checkout and optionally clear cart in DB (not doing DB deletes automatically)
unset($_SESSION['checkout_order']);
unset($_SESSION['checkout_token']);

// Render a simple confirmation using the site's layout
ob_start();
?>
<main style="padding:120px 20px;">
  <div class="container">
    <div class="confirmation">
      <h1>Đơn hàng của bạn đã được lưu</h1>
      <p>Mã đơn hàng: <strong><?= htmlspecialchars($orderId) ?></strong></p>
      <p>Tổng tiền: <strong><?= number_format($orderPayload['total_amount'], 0, ',', '.') ?>đ</strong></p>
      <p>Chúng tôi đã lưu thông tin giao hàng và đơn hàng của bạn. Nhân viên sẽ liên hệ lại theo số điện thoại: <strong><?= htmlspecialchars($data['phone']) ?></strong></p>

      <div style="margin-top:18px;">
        <a href="index.php?page=product" class="btn-primary">Tiếp tục mua sắm</a>
        <a href="index.php?page=orders" class="btn-outline">Xem đơn hàng</a>
      </div>
    </div>
  </div>
</main>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';

exit;
