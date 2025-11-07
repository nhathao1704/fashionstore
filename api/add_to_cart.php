<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/config.php';

function json_error($msg, $code = 400) {
    http_response_code($code);
    die(json_encode(['error' => $msg]));
}

function json_success($data = null) {
    die(json_encode(['success' => true, 'data' => $data]));
}

if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
    json_error('Vui lòng đăng nhập để thêm vào giỏ hàng', 401);
}

if (!empty($_SESSION['user'])) {
    $user_id = (int)$_SESSION['user']['user_id'];
} else {
    $user_id = (int)$_SESSION['user_id'];
}
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($variant_id <= 0 || $quantity <= 0) {
    json_error('Dữ liệu không hợp lệ');
}

$stmt = mysqli_prepare($conn, "
    SELECT v.variant_id, v.product_id, v.price, v.stock_quantity,
           v.size, p.product_name
    FROM ProductVariants v
    JOIN Products p ON v.product_id = p.product_id
    WHERE v.variant_id = ? AND v.product_id = ?
");
mysqli_stmt_bind_param($stmt, 'ii', $variant_id, $product_id);
mysqli_stmt_execute($stmt);
$variant = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$variant) {
    json_error('Biến thể không tồn tại');
}

if ($quantity > $variant['stock_quantity']) {
    json_error('Số lượng vượt quá tồn kho');
}

mysqli_begin_transaction($conn);
try {
    
    $cart = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT cart_id FROM Carts WHERE user_id = {$user_id} LIMIT 1"
    ));
    
    if ($cart) {
        $cart_id = $cart['cart_id'];
        mysqli_query($conn, "UPDATE Carts SET updated_at = NOW() WHERE cart_id = {$cart_id}");
    } else {
        mysqli_query($conn, "INSERT INTO Carts (user_id, created_at, updated_at) VALUES ({$user_id}, NOW(), NOW())");
        $cart_id = mysqli_insert_id($conn);
    }

    $existing = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT cart_item_id, quantity FROM CartItems 
         WHERE cart_id = {$cart_id} AND variant_id = {$variant_id} LIMIT 1"
    ));

    if ($existing) {
        $new_qty = $existing['quantity'] + $quantity;
        if ($new_qty > $variant['stock_quantity']) {
            json_error('Tổng số lượng vượt quá tồn kho');
        }
        mysqli_query($conn, 
            "UPDATE CartItems 
             SET quantity = {$new_qty}, 
                 price_at_added = {$variant['price']},
                 added_at = NOW()
             WHERE cart_item_id = {$existing['cart_item_id']}"
        );
    } else {
        mysqli_query($conn,
            "INSERT INTO CartItems (cart_id, variant_id, quantity, price_at_added, added_at)
             VALUES ({$cart_id}, {$variant_id}, {$quantity}, {$variant['price']}, NOW())"
        );
    }

    $total = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(quantity) as total FROM CartItems WHERE cart_id = {$cart_id}"
    ));

    mysqli_commit($conn);
    json_success([
        'cart_count' => (int)$total['total'],
        'message' => "Đã thêm {$quantity} {$variant['product_name']} - Size {$variant['size']} vào giỏ"
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    json_error('Có lỗi xảy ra, vui lòng thử lại');
}