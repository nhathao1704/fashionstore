<?php
require_once __DIR__ . '/../../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Giỏ hàng - FashionStore';
$extra_css = ['css/cart.css'];
$extra_js = ['js/cart.js'];

// Kiểm tra đăng nhập
if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
    echo "<script>
        alert('Bạn chưa đăng nhập!');
        window.location.href = '/fashionstore/index.php?page=login&return=" . urlencode('/fashionstore/index.php?page=cart') . "';
    </script>";
    exit;
}

$user_id = !empty($_SESSION['user']) ? (int)$_SESSION['user']['user_id'] : (int)$_SESSION['user_id'];



// Xử lý cập nhật số lượng

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $item_id = (int)$_POST['cart_item_id'];
    $new_qty = (int)$_POST['quantity'];
    if ($new_qty < 1) $new_qty = 1;

    mysqli_query($conn, "UPDATE CartItems SET quantity = {$new_qty} WHERE cart_item_id = {$item_id}");

    header("Location: /fashionstore/index.php?page=cart");
    exit;
}



// Xử lý xóa sản phẩm

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $item_id = (int)$_POST['cart_item_id'];

    mysqli_query($conn, "DELETE FROM CartItems WHERE cart_item_id = {$item_id}");

    header("Location: /fashionstore/index.php?page=cart");
    exit;
}


//   Lấy thông tin giỏ hàng

$cart = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT cart_id FROM Carts WHERE user_id = {$user_id} LIMIT 1"
));

$items = [];
$total_amount = 0;
$total_items = 0;

if ($cart) {
    $sql = "
        SELECT ci.cart_item_id, ci.quantity, ci.price_at_added,
             p.product_id, p.product_name,
             v.size, v.stock_quantity,
             COALESCE(pi.image_url, 'uploads/no-image.jpg') as image_url
        FROM CartItems ci
        JOIN ProductVariants v ON ci.variant_id = v.variant_id
        JOIN Products p ON v.product_id = p.product_id
        LEFT JOIN (
            SELECT product_id, MIN(image_url) as image_url 
            FROM ProductImages 
            GROUP BY product_id
        ) pi ON p.product_id = pi.product_id
        WHERE ci.cart_id = {$cart['cart_id']}
        ORDER BY ci.added_at DESC
    ";
        
    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
        $total_amount += $row['price_at_added'] * $row['quantity'];
        $total_items += $row['quantity'];
    }
}



//  Xử lý bấm nút THANH TOÁN

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'checkout') {

    $checkout_payload = [
        'user_id' => $user_id,
        'cart_id' => $cart['cart_id'] ?? null,
        'items' => [],
        'total_items' => $total_items,
        'total_amount' => $total_amount,
        'created_at' => date('Y-m-d H:i:s')
    ];

    foreach ($items as $it) {
        $checkout_payload['items'][] = [
            'product_id' => $it['product_id'],
            'product_name' => $it['product_name'],
            'variant' => $it['size'],
            'quantity' => (int)$it['quantity'],
            'price' => (float)$it['price_at_added'],
            'image' => $it['image_url']
        ];
    }

    $_SESSION['checkout_order'] = $checkout_payload;

    header("Location: /fashionstore/index.php?page=pay");
    exit;
}

ob_start();
?>

<!-- HTML HIỂN THỊ GIỎ HÀNG-->

<main class="cart-page">
    <div class="cart-container">
        <h1>Giỏ hàng của bạn</h1>
                
        <?php if (empty($items)): ?>
        <div class="cart-empty">
            <i class="fas fa-shopping-cart"></i>
            <p>Giỏ hàng trống</p>
            <a href="index.php?page=product" class="btn-primary">Tiếp tục mua sắm</a>
        </div>
        <?php else: ?>
                
        <div class="cart-content">
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                <div class="cart-item">

                    <!-- Hình ảnh -->
                    <div class="item-image">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>">
                    </div>
                                
                    <!-- Chi tiết sản phẩm -->
                    <div class="item-details">
                        <h3>
                            <a href="index.php?page=product_detail&id=<?= $item['product_id'] ?>">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </a>
                        </h3>
                        <p class="size">Size: <?= htmlspecialchars($item['size']) ?></p>
                        <p class="price"><?= number_format($item['price_at_added'], 0, ",", ".") ?>đ</p>
                    </div>
                                
                    <!-- Số lượng -->
                    <div class="item-quantity">
                        <form method="post">
                            <div class="quantity-control">
                                <button type="submit" name="update_qty" class="qty-btn minus"
                                        onclick="this.parentNode.querySelector('input').stepDown()">
                                    -
                                </button>

                                <input type="number"
                                    name="quantity"
                                    value="<?= $item['quantity'] ?>"
                                    min="1"
                                    max="<?= $item['stock_quantity'] ?>"
                                    class="qty-input">

                                <button type="submit" name="update_qty" class="qty-btn plus"
                                        onclick="this.parentNode.querySelector('input').stepUp()">
                                    +
                                </button>
                            </div>

                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                        </form>

                        <!-- nút xóa -->
                        <form method="post">
                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                            <button type="submit" name="remove_item" class="remove-item">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </form>
                    </div>
                                
                    <!-- Thành tiền -->
                    <div class="item-total">
                        <?= number_format($item['price_at_added'] * $item['quantity'], 0, ",", ".") ?>đ
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
                    
            <!-- Tóm tắt giỏ hàng -->
            <div class="cart-summary">
                <h3>Tổng giỏ hàng</h3>
                <div class="summary-row"><span>Tổng sản phẩm:</span><span><?= $total_items ?></span></div>
                <div class="summary-row"><span>Tạm tính:</span><span><?= number_format($total_amount, 0, ",", ".") ?>đ</span></div>
                <div class="summary-row"><span>Phí vận chuyển:</span><span>Miễn phí</span></div>
                <div class="summary-row total">
                    <span>Thành tiền:</span><span><?= number_format($total_amount, 0, ",", ".") ?>đ</span>
                </div>
                        
                <div class="cart-actions">
                    <a href="index.php?page=product" class="btn-outline">
                        <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                    </a>

                    <form method="post">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn-primary">
                            Thanh toán <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <?php endif; ?>
    </div>
</main>

<?php $content = ob_get_clean(); 
require __DIR__ . '/../../includes/layouts/' . $layout . '.php'; 
exit;
