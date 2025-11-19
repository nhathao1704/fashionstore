<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : '';

// Current request path
$current_request = $_SERVER['REQUEST_URI'] ?? '/fashionstore/index.php';
$login_href = '/fashionstore/index.php?page=login&return=' . urlencode($current_request);
$logout_href = '/fashionstore/index.php?page=logout&return=' . urlencode($current_request);

// Tính số lượng giỏ hàng
$cart_count = 0;
$user_id = 0;
if (!empty($_SESSION['user']['user_id'])) {
    $user_id = (int)$_SESSION['user']['user_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
}

// Đảm bảo có kết nối DB
if (!isset($conn)) {
    @include_once __DIR__ . '/../../config/config.php';
}

// Lấy danh mục
$categories = [];
if (isset($conn)) {
    $rs = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_id ASC");
    while ($row = mysqli_fetch_assoc($rs)) {
        $categories[] = $row;
    }
}

// Đếm giỏ hàng
if ($user_id && isset($conn)) {
    $cart = mysqli_fetch_assoc(mysqli_query($conn, "SELECT cart_id FROM Carts WHERE user_id = {$user_id} LIMIT 1"));
    if ($cart) {
        $tot = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COALESCE(SUM(quantity),0) AS total FROM CartItems WHERE cart_id = {$cart['cart_id']}"
        ));
        $cart_count = (int)($tot['total'] ?? 0);
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

            <li>
                <a href="/fashionstore/index.php?page=product" class="<?= ($current_page=='product')?'active':'' ?>">
                    Sản phẩm
                </a>
            </li>

            <!-- DANH MỤC LẤY TỪ DB -->
            <li class="dropdown">
                <a href="#" class="toggle-btn">☰ Danh mục</a>

                <div class="mega-menu">
                    <?php foreach ($categories as $cat): ?>
                        <div class="mega-column">
                            <h4><?= htmlspecialchars($cat['category_name']) ?></h4>
                            <a href="/fashionstore/index.php?page=product&cat=<?= $cat['category_id'] ?>"
                               style="text-decoration:none;">
                                Xem tất cả <?= htmlspecialchars($cat['category_name']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </li>
        </ul>  
    </nav>

    <div class="header-right">

        <!-- TÌM KIẾM -->
        <form action="/fashionstore/index.php" method="GET" style="display:flex;">
            <input type="hidden" name="page" value="product">

            <input type="text"
                   name="search"
                   placeholder="Tìm kiếm..."
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                   style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;">
        </form>

        <div class="auth-links">
            <?php if (!empty($_SESSION['user'])): ?>
                <div class="user-dropdown">
                    <span class="user-name toggle-btn">Xin chào, <?= htmlspecialchars($_SESSION['user']['full_name']); ?></span>
                    <div class="user-menu">
                        <a href="/fashionstore/index.php?page=information">Thông tin cá nhân</a>
                        <a href="/fashionstore/index.php?page=purchase_order">Đơn hàng của tôi</a>
                        <a href="<?= $logout_href ?>" class="logout-btn" style="color:#c33;">
                            <i class="fas fa-sign-in-alt"></i> Đăng xuất
                        </a>
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
