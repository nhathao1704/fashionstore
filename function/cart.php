<?php
require_once __DIR__ . '/../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Giỏ hàng - FashionStore';
$extra_css = ['css/cart.css'];
$extra_js = ['js/cart.js'];

// Kiểm tra đăng nhập
if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
    echo "<script>
        alert('Bạn chưa đăng nhập!');
        window.location.href = '/fashionstore/function/login.php?return=" . urlencode('/fashionstore/function/cart.php') . "';
    </script>";
    exit;
}

$user_id = !empty($_SESSION['user']) ? (int)$_SESSION['user']['user_id'] : (int)$_SESSION['user_id'];

// Lấy thông tin giỏ hàng
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

ob_start();
?>

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
                <div class="cart-item" data-id="<?= $item['cart_item_id'] ?>">
                    <div class="item-image">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                             alt="<?= htmlspecialchars($item['product_name']) ?>">
                    </div>
                                
                    <div class="item-details">
                        <h3>
                            <a href="index.php?page=product_detail&id=<?= $item['product_id'] ?>">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </a>
                        </h3>
                        <p class="size">Size: <?= htmlspecialchars($item['size']) ?></p>
                        <p class="price"><?= number_format($item['price_at_added'], 0, ",", ".") ?>đ</p>
                    </div>
                                
                    <div class="item-quantity">
                        <div class="quantity-control">
                            <button type="button" class="qty-btn minus">-</button>
                            <input type="number" value="<?= $item['quantity'] ?>" 
                                 min="1" max="<?= $item['stock_quantity'] ?>"
                                 class="qty-input"
                                 data-id="<?= $item['cart_item_id'] ?>"
                                 data-price="<?= $item['price_at_added'] ?>">
                            <button type="button" class="qty-btn plus">+</button>
                        </div>
                        <button class="remove-item">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                                
                    <div class="item-total">
                        <?= number_format($item['price_at_added'] * $item['quantity'], 0, ",", ".") ?>đ
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
                    
            <div class="cart-summary">
                <h3>Tổng giỏ hàng</h3>
                <div class="summary-row">
                    <span>Tổng sản phẩm:</span>
                    <span class="total-items"><?= $total_items ?></span>
                </div>
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span class="total-amount"><?= number_format($total_amount, 0, ",", ".") ?>đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span>Miễn phí</span>
                </div>
                <div class="summary-row total">
                    <span>Thành tiền:</span>
                    <span class="final-total"><?= number_format($total_amount, 0, ",", ".") ?>đ</span>
                </div>
                        
                <div class="cart-actions">
                    <a href="index.php?page=product" class="btn-outline">
                        <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                    <a href="index.php?page=checkout" class="btn-primary">
                        Thanh toán <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.cart-items');
    if (!container) return;

    function updateTotals() {
        let totalItems = 0;
        let totalAmount = 0;
                
        document.querySelectorAll('.cart-item').forEach(item => {
            const qty = parseInt(item.querySelector('.qty-input').value);
            const price = parseFloat(item.querySelector('.qty-input').dataset.price);
            totalItems += qty;
            totalAmount += qty * price;
            item.querySelector('.item-total').textContent = 
                new Intl.NumberFormat('vi-VN').format(qty * price) + 'đ';
        });
        document.querySelector('.total-items').textContent = totalItems;
        document.querySelector('.total-amount').textContent = 
            new Intl.NumberFormat('vi-VN').format(totalAmount) + 'đ';
        document.querySelector('.final-total').textContent = 
            new Intl.NumberFormat('vi-VN').format(totalAmount) + 'đ';
        updateCartCount(totalItems);
    }

    // Cập nhật số lượng hiển thị ở header (giỏ hàng)
    function updateCartCount(n) {
        const el = document.querySelector('.cart-count');
        if (!el) return;
        el.textContent = n;
    }

    container.addEventListener('click', function(e) {
        if (!e.target.classList.contains('qty-btn')) return;
                
        const input = e.target.parentNode.querySelector('.qty-input');
        const currentVal = parseInt(input.value);
        const max = parseInt(input.max);
                
        if (e.target.classList.contains('minus')) {
            if (currentVal > 1) input.value = currentVal - 1;
        } else {
            if (currentVal < max) input.value = currentVal + 1;
        }
                
        updateQuantity(input);
    });

    container.addEventListener('change', function(e) {
        if (!e.target.classList.contains('qty-input')) return;
        updateQuantity(e.target);
    });

    container.addEventListener('click', async function(e) {
        const removeBtn = e.target.closest('.remove-item');
        if (!removeBtn) return;
                
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
                
        const item = removeBtn.closest('.cart-item');
        const itemId = item.dataset.id;
                
        try {
            const response = await fetch('api/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cart_item_id: itemId })
            });
                    
            const result = await response.json();
            if (!response.ok) throw new Error(result.error);
                    
            item.remove();
            updateTotals();
                    
            if (document.querySelectorAll('.cart-item').length === 0) {
                location.reload(); 
            }
                    
            showMessage('Đã xóa sản phẩm khỏi giỏ hàng');
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    async function updateQuantity(input) {
        const newQty = parseInt(input.value);
        const itemId = input.dataset.id;
                
        try {
            const response = await fetch('api/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_item_id: itemId,
                    quantity: newQty
                })
            });
                    
            const result = await response.json();
            if (!response.ok) throw new Error(result.error);
                    
            updateTotals();
        } catch (error) {
            showMessage(error.message, true);
            input.value = input.defaultValue;
            updateTotals();
        }
    }
});
</script>

