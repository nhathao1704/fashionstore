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


  $sql = "SELECT p.product_id, p.product_name, pi.image_url, MIN(v.price) AS price, MIN(v.variant_id) AS variant_id
    FROM Products p
    LEFT JOIN ProductImages pi ON p.product_id = pi.product_id
    LEFT JOIN ProductVariants v ON p.product_id = v.product_id
    GROUP BY p.product_id, p.product_name, pi.image_url
    ORDER BY p.product_id DESC";

  $result = mysqli_query($conn, $sql);

  while ($row = mysqli_fetch_assoc($result)) {
      $img = htmlspecialchars($row['image_url'] ?: 'uploads/no-image.jpg');
      $pname = htmlspecialchars($row['product_name']);
      $price = $row['price'] ? number_format($row['price'], 0, ",", ".") . 'đ' : 'Liên hệ';
      $variantId = isset($row['variant_id']) ? (int)$row['variant_id'] : 0;
      echo "
      <div class=\"product-card\">
          <img src=\"{$img}\" alt=\"{$pname}\" />
          <h3>{$pname}</h3>
          <p class=\"price\">{$price}</p>
          <div class=\"actions\">
            <a href=\"/fashionstore/index.php?page=product_detail&id={$row['product_id']}\" class=\"btn\">Xem chi tiết</a>
            <button class=\"add-to-cart\" data-product-id=\"{$row['product_id']}\" data-variant-id=\"{$variantId}\"> <i class=\"fa-solid fa-cart-plus\"></i> Thêm vào giỏ</button>
          </div>
      </div>";
  }
?>
</div>
      </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
      function showMessage(msg){
        try { alert(msg); } catch(e) { console.log(msg); }
      }

      document.querySelectorAll('.add-to-cart').forEach(function(btn){
        btn.addEventListener('click', async function(e){
          e.preventDefault();
          const productId = this.dataset.productId;
          const variantId = parseInt(this.dataset.variantId || 0);
          if (!variantId || variantId <= 0) {
            // nếu không có variant mặc định, chuyển tới trang chi tiết để chọn
            window.location.href = '/fashionstore/index.php?page=product_detail&id=' + encodeURIComponent(productId);
            return;
          }
          this.disabled = true;
          try {
            const form = new FormData();
            form.append('product_id', productId);
            form.append('variant_id', variantId);
            form.append('quantity', 1);

            const resp = await fetch('/fashionstore/api/add_to_cart.php', {
              method: 'POST',
              body: form,
              credentials: 'same-origin'
            });

            const data = await resp.json();
            if (!resp.ok) {
              if (resp.status === 401) {
                 alert('Bạn chưa đăng nhập. Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.');
                // chưa login -> redirect tới trang login, kèm return
                window.location.href = '/fashionstore/index.php?page=login&return=' + encodeURIComponent('/fashionstore/index.php?page=product');
                return;
              }
              showMessage(data.error || 'Không thể thêm vào giỏ');
              return;
            }

            // Cập nhật số lượng giỏ hàng ở header
            if (data && data.data && typeof data.data.cart_count !== 'undefined') {
              const el = document.querySelector('.cart-count');
              if (el) el.textContent = data.data.cart_count;
            }
            showMessage(data.data.message || 'Đã thêm vào giỏ');
          } catch (err) {
            console.error(err);
            showMessage('Lỗi mạng, vui lòng thử lại');
          } finally {
            this.disabled = false;
          }
        });
      });
    });
    </script>

