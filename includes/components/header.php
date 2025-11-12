<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : '';

// Current request path to use as return URL after login/logout
$current_request = $_SERVER['REQUEST_URI'] ?? '/fashionstore/index.php';
$login_href = '/fashionstore/index.php?page=login&return=' . urlencode($current_request);
$logout_href = '/fashionstore/index.php?page=logout&return=' . urlencode($current_request);

// Tính số lượng mục trong giỏ hàng để hiển thị ở header
$cart_count = 0;
// Lấy user id từ session (hỗ trợ cả hai kiểu lưu session)
$user_id = 0;
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['user_id'])) {
    $user_id = (int)$_SESSION['user']['user_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}

if ($user_id) {
    // đảm bảo có kết nối $conn
    if (!isset($conn)) {
        @include_once __DIR__ . '/../../config/config.php';
    }
    if (isset($conn) && $conn) {
        $cart = mysqli_fetch_assoc(mysqli_query($conn, "SELECT cart_id FROM Carts WHERE user_id = {$user_id} LIMIT 1"));
        if ($cart) {
            $tot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(quantity),0) AS total FROM CartItems WHERE cart_id = {$cart['cart_id']}"));
            $cart_count = (int)($tot['total'] ?? 0);
        }
    }
}
?>
<header>
    <div class="logo">
        <a href="/fashionstore/index.php" style="text-decoration:none;color:white;">Vogue Lane Clothing</a>
    </div>
    <nav class="navbar">
        <ul>
            <li><a href="/fashionstore/index.php" class="<?= ($current_page=='')?'active':'' ?>">Trang chủ</a></li>
            <!-- Chuyển sang route qua index.php?page=product để giữ layout/chung CSS -->
            <li><a href="/fashionstore/index.php?page=product" class="<?= ($current_page=='product')?'active':'' ?>">Sản phẩm</a></li>
            <li class="dropdown">
                <a href="#" class="toggle-btn">☰ Danh mục</a>
                <div class="mega-menu">
                    <div class="mega-column">
                        <h4>Sản phẩm áo</h4>
                        <a href="#">Áo Polo</a>
                        <a href="#">Áo Thun</a>
                        <a href="#">Áo Khoác</a>
                    </div>
                    <div class="mega-column">
                        <h4>Sản phẩm quần</h4>
                        <a href="#">Quần Jean</a>
                        <a href="#">Quần short</a>
                    </div>
                    <div class="mega-column">
                        <h4>Phụ kiện</h4>
                        <a href="#">Thắt Lưng</a>
                        <a href="#">Dây Chuyền</a>
                    </div>
                    <div class="mega-column">
                        <h4>Ưu đãi đặc biệt</h4>
                        <a href="#">Hàng Mới</a>
                        <a href="#">Bán Chạy</a>
                    </div>
                </div>
            </li>
        </ul>  
    </nav>

    <div class="header-right">
        <input type="text" placeholder="Tìm kiếm..." />
        <div class="auth-links">
            <?php if (!empty($_SESSION['user'])): ?>
                <div class="user-dropdown">
                    <span class="user-name toggle-btn">Xin chào, <?= htmlspecialchars($_SESSION['user']['full_name']); ?></span>
                    <div class="user-menu">
                        <a href="/fashionstore/index.php?page=information" style="text-decoration:none">Thông tin cá nhân</a>
                        <a href="/fashionstore/index.php?page=purchase_order"style="text-decoration:none">Đơn hàng của tôi</a>
                        <a href="<?= $logout_href ?>" class="logout-btn" style="color:#c33;text-decoration:none;"><i class="fas fa-sign-in-alt"></i> Đăng xuất</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?= $login_href ?>"><i class="fa-solid fa-user"></i></a>
            <?php endif; ?>
            <a href="/fashionstore/index.php?page=cart" class="cart-icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-count"><?= $cart_count ?></span>
            </a>
        </div>
    </div>
</header>