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
if (!isset($data['cart_item_id'])) {
    json_error('Missing cart_item_id');
}

$cart_item_id = (int)$data['cart_item_id'];
$user_id = (int)$_SESSION['user']['user_id'];
$check = mysqli_query($conn, "
    SELECT ci.cart_item_id
    FROM CartItems ci
    JOIN Carts c ON ci.cart_id = c.cart_id
    WHERE ci.cart_item_id = {$cart_item_id}
    AND c.user_id = {$user_id}
");

if (!mysqli_fetch_assoc($check)) {
    json_error('Item not found', 404);
}
mysqli_query($conn, "DELETE FROM CartItems WHERE cart_item_id = {$cart_item_id}");

die(json_encode(['success' => true]));