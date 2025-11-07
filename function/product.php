<?php include_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sản phẩm - FashionStore</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <header>
    <div class="logo">Vogue Lane Clothing</div>
    <nav class="navbar">
      <ul>
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="product.php" class="active">Sản phẩm</a></li>
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
              <a href="#">Quần Jeans</a>
              <a href="#">Quần Kaki</a>
              <a href="#">Quần Short</a>
            </div>
            <div class="mega-column">
              <h4>Phụ kiện</h4>
              <a href="#">Thắt Lưng</a>
              <a href="#">Dây Chuyền</a>
              <a href="#">Vòng Tay</a>
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
        <a href="login.php"><i class="fa-solid fa-user"></i></a>
        <a href="cart.php" class="cart-icon">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="cart-count">0</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="product-section">
      <h2>Tất cả sản phẩm</h2>
      <div class="product-grid">
<?php
  include "config/config.php";

  $sql = "SELECT p.product_id, p.product_name, pi.image_url, MIN(v.price) AS price
          FROM Products p
          LEFT JOIN ProductImages pi ON p.product_id = pi.product_id
          LEFT JOIN ProductVariants v ON p.product_id = v.product_id
          GROUP BY p.product_id, p.product_name, pi.image_url
          ORDER BY p.product_id DESC";

  $result = mysqli_query($conn, $sql);

  while ($row = mysqli_fetch_assoc($result)) {
      echo '
      <div class="product-card">
          <img src="'.$row['image_url'].'" alt="'.$row['product_name'].'" />
          <h3>'.$row['product_name'].'</h3>
          <p class="price">'.number_format($row['price'], 0, ",", ".").'đ</p>
          <div class="actions">
            <a href="function/product_detail.php?id='.$row['product_id'].'" class="btn">Xem chi tiết</a>
            <button class="add-to-cart"><i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ</button>
          </div>
      </div>';
  }
?>
</div>
      </div>
    </section>
  </main>



  <script src="js/app.js"></script>
</body>
</html>
