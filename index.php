<?php
session_start();
include_once "config/config.php";

// Xác định trang cần hiển thị
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Danh sách trang hợp lệ
$allowed_pages = [
  'product' => 'function/product.php',
  'product_detail' => 'function/product_detail.php',
  'cart' => 'function/cart.php',
  'pay' => 'function/pay.php',
  'login' => 'auth/login.php',
  'register' => 'auth/register.php',
  'logout' => 'function/logout.php'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FashionStore - Vogue Lane Clothing</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <!-- ================= HEADER ================= -->
  <header>
    <div class="logo">
      <a href="index.php" style="text-decoration:none;color:white;">Vogue Lane Clothing</a>
    </div>
    <nav class="navbar">
      <ul>
        <li><a href="index.php" class="<?= ($page=='')?'active':'' ?>">Trang chủ</a></li>
        <li><a href="index.php?page=product" class="<?= ($page=='product')?'active':'' ?>">Sản phẩm</a></li>
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
          <a href="index.php?page=logout" class="logout-btn" style="color:#c33;text-decoration:none;">Đăng xuất</a>
        <?php else: ?>
          <a href="/FashionStore3/index.php?page=login"><i class="fa-solid fa-user"></i></a>
        <?php endif; ?>
        <a href="index.php?page=cart" class="cart-icon">
          <i class="fa-solid fa-cart-shopping"></i>
          <span class="cart-count">0</span>
        </a>
      </div>
    </div>
  </header>

  <!-- ================= NỘI DUNG CHÍNH ================= -->
  <main>
    <?php
      if ($page === '' || !array_key_exists($page, $allowed_pages)) {
          // === NỘI DUNG TRANG CHỦ (mặc định) ===
          ?>
          <div class="promotion-image">
            <div class="image-khuyenmai">
              <img src="uploads/khuyenmai.jpg" alt="khuyenmai"/>
            </div>
          </div>

          <section class="product-section">
            <h2>Sản phẩm mới nhất</h2>
            <div class="product-grid">
            <?php
              $sql = "SELECT p.product_id, p.product_name, c.category_name,
                             pi.image_url, MIN(v.price) AS price,
                             COUNT(DISTINCT v.variant_id) as variant_count
                      FROM Products p
                      LEFT JOIN Categories c ON p.category_id = c.category_id
                      LEFT JOIN ProductImages pi ON p.product_id = pi.product_id
                      LEFT JOIN ProductVariants v ON p.product_id = v.product_id
                      WHERE v.stock_quantity > 0
                      GROUP BY p.product_id, p.product_name, c.category_name, pi.image_url
                      ORDER BY p.created_at DESC, p.product_id DESC
                      LIMIT 4";
              $result = mysqli_query($conn, $sql);
              if (mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      $img = $row['image_url'] ?? 'uploads/no-image.jpg';
                      if (!file_exists($img)) $img = 'uploads/no-image.jpg';
                      echo '
                      <div class="product-card">
                          <img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($row['product_name']).'" />
                          <div class="product-info">
                              <span class="category">'.htmlspecialchars($row['category_name']).'</span>
                              <h3>'.htmlspecialchars($row['product_name']).'</h3>
                              <p class="price">'.($row['price'] ? number_format($row['price'], 0, ",", ".").'đ' : 'Liên hệ').'</p>
                              <a href="index.php?page=product_detail&id='.$row['product_id'].'" class="btn">Xem chi tiết</a>
                          </div>
                      </div>';
                  }
              } else {
                  echo '<p class="no-products">Chưa có sản phẩm mới.</p>';
              }
            ?>
            </div>
          </section>

          <section class="product-section">
            <h2>Sản phẩm bán chạy</h2>
            <div class="product-grid">
            <?php
              $sql2 = "SELECT p.product_id, p.product_name, c.category_name,
                              pi.image_url, MIN(v.price) AS price,
                              SUM(od.quantity) as total_sold
                       FROM Products p
                       LEFT JOIN Categories c ON p.category_id = c.category_id
                       LEFT JOIN ProductImages pi ON p.product_id = pi.product_id
                       LEFT JOIN ProductVariants v ON p.product_id = v.product_id
                       LEFT JOIN OrderDetails od ON v.variant_id = od.variant_id
                       LEFT JOIN Orders o ON od.order_id = o.order_id
                       WHERE v.stock_quantity > 0
                         AND o.status_id = (SELECT status_id FROM Order_Status WHERE status_name = 'Completed')
                       GROUP BY p.product_id, p.product_name, c.category_name, pi.image_url
                       ORDER BY total_sold DESC, p.product_id DESC
                       LIMIT 4";
              $result2 = mysqli_query($conn, $sql2);
              if (mysqli_num_rows($result2) > 0) {
                  while ($row = mysqli_fetch_assoc($result2)) {
                      $img = $row['image_url'] ?? 'uploads/no-image.jpg';
                      if (!file_exists($img)) $img = 'uploads/no-image.jpg';
                      echo '
                      <div class="product-card">
                          <img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($row['product_name']).'" />
                          <div class="product-info">
                              <span class="category">'.htmlspecialchars($row['category_name']).'</span>
                              <h3>'.htmlspecialchars($row['product_name']).'</h3>
                              <p class="price">'.($row['price'] ? number_format($row['price'], 0, ",", ".").'đ' : 'Liên hệ').'</p>
                              <a href="index.php?page=product_detail&id='.$row['product_id'].'" class="btn">Xem chi tiết</a>
                          </div>
                      </div>';
                  }
              } else {
                  echo '<p class="no-products">Chưa có sản phẩm bán chạy.</p>';
              }
            ?>
            </div>
          </section>
          <?php
      } else {
          // === NỘI DUNG CÁC TRANG KHÁC ===
          include $allowed_pages[$page];
      }
    ?>
  </main>

  <!-- ================= FOOTER ================= -->
  <footer>
    <div class="footer-container">
      <div class="footer-col">
        <h4>Hỗ trợ khách hàng</h4>
        <ul>
          <li><a href="#">Chính sách</a></li>
          <li><a href="#">Hướng dẫn mua hàng</a></li>
          <li><a href="#">Liên hệ</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Liên hệ</h4>
        <p>📧 Voguelane@gmail.com</p>
        <p>📞 0123 456 789</p>
        <p>📍 Hà Nội, Việt Nam</p>
      </div>

      <div class="footer-col">
        <h4>Kết nối</h4>
        <div class="socials">
          <a href="#">Facebook</a>
          <a href="#">Instagram</a>
          <a href="#">Zalo</a>
        </div>
      </div>
    </div>
    <p class="copyright">© 2025 Vogue Lane Clothing - Bản quyền thuộc về chúng tôi</p>
  </footer>

  <script src="js/app.js"></script>
</body>
</html>
