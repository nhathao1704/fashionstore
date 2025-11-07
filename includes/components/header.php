<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : '';
?>
<header>
    <div class="logo">
        <a href="/fashionstore/index.php" style="text-decoration:none;color:white;">Vogue Lane Clothing</a>
    </div>
    <nav class="navbar">
        <ul>
            <li><a href="/fashionstore/index.php" class="<?= ($current_page=='')?'active':'' ?>">Trang chủ</a></li>
            <li><a href="/fashionstore/function/product.php" class="<?= ($current_page=='product')?'active':'' ?>">Sản phẩm</a></li>
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
                <span class="user-name">Xin chào, <?= htmlspecialchars($_SESSION['user']['full_name']); ?></span>
                <a href="/fashionstore/function/logout.php" class="logout-btn" style="color:#c33;text-decoration:none;">Đăng xuất</a>
                <?php else: ?>
                <a href="/fashionstore/function/login.php"><i class="fa-solid fa-user"></i></a>
            <?php endif; ?>
            <a href="/fashionstore/index.php?page=cart" class="cart-icon">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-count">0</span>
            </a>
        </div>
    </div>
</header>