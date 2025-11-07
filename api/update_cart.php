<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/config.php';

function json_error($msg, $code = 400) {
    http_response_code($code);
    die(json_encode(['error' => $msg]));
}

if (empty($_SESSION['user'])) {
    json_error('Unauthorized', 401);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['cart_item_id']) || !isset($data['quantity'])) {
    json_error('Missing required fields');
}

$cart_item_id = (int)$data['cart_item_id'];
$quantity = (int)$data['quantity'];
$user_id = (int)$_SESSION['user']['user_id'];

if ($quantity < 1) {
    json_error('Số lượng không hợp lệ');
}
$check = mysqli_query($conn, "
    SELECT ci.cart_item_id, v.stock_quantity, c.user_id
    FROM CartItems ci
    JOIN Carts c ON ci.cart_id = c.cart_id
    JOIN ProductVariants v ON ci.variant_id = v.variant_id
    WHERE ci.cart_item_id = {$cart_item_id}
    AND c.user_id = {$user_id}
");

$item = mysqli_fetch_assoc($check);
if (!$item) {
    json_error('Item not found', 404);
}
if ($quantity > $item['stock_quantity']) {
    json_error('Số lượng vượt quá tồn kho');
}
mysqli_query($conn, "
    UPDATE CartItems 
    SET quantity = {$quantity}
    WHERE cart_item_id = {$cart_item_id}
");

die(json_encode(['success' => true]));