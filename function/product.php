<?php
require_once __DIR__ . '/../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Sản phẩm - FashionStore';

// Bắt đầu output buffering
ob_start();
?>
    <section class="product-section">
      <h2>Tất cả sản phẩm</h2>
      <div class="product-grid">
<?php


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

