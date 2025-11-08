<?php
require_once __DIR__ . '/../../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Thanh toán - FashionStore';
// Lấy dữ liệu thanh toán được chuyển từ `cart.php` (qua session)
$checkout = $_SESSION['checkout_order'] ?? null;

ob_start();
?>
<main>
  <section class="delivery">
    <div class="container">
      <div class="delivery-content">
          <div class="delivery-content-left">
          <p>Vui lòng chọn địa chỉ giao hàng</p>

          <div class="delivery-content-left-dangnhap row">
            <i class="fas fa-sign-in-alt"></i>
            <p>Đăng nhập (Nếu bạn đã có tài khoản )</p>
          </div>

          <form method="post" action="index.php?page=purchase_order">
            <div class="delivery-content-left-khachle row">
              <input checked name="customer_type" value="guest" type="radio">
              <p><span style="font-weight:bold;">Khách lẻ</span> (Nếu bạn không muốn lưu lại thông tin)</p>
            </div>

            <div class="delivery-content-left-dangky row">
              <input name="customer_type" value="register" type="radio">
              <p><span style="font-weight:bold;">Đăng ký</span> (Tạo tài khoản với thông tin bên dưới)</p>
            </div>

          <div class="delivery-content-left-input-top">
            <div class="delivery-content-left-input-top-item">
              <label>Họ tên <span style="color:red;">*</span></label>
              <input name="full_name" type="text" placeholder="Nhập họ và tên" required>
            </div>
            <div class="delivery-content-left-input-top-item">
              <label>Điện thoại <span style="color:red;">*</span></label>
              <input name="phone" type="text" placeholder="Số điện thoại" required>
            </div>
            <div class="delivery-content-left-input-top-item">
              <label>Tỉnh/Thành phố <span style="color:red;">*</span></label>
              <input name="city" type="text" placeholder="Ví dụ: Hà Nội" required>
            </div>
            <div class="delivery-content-left-input-top-item">
              <label>Quận/Huyện <span style="color:red;">*</span></label>
              <input name="district" type="text" placeholder="Ví dụ: Ba Đình" required>
            </div>
          </div>

          <div class="delivery-content-left-input-bottom">
            <label>Địa chỉ <span style="color:red;">*</span></label>
            <input name="address" type="text" placeholder="Số nhà, tên đường..." required>
          </div>

          <div class="delivery-content-left-button">
            <a href="index.php?page=cart" class="back-link"><span>&#171;</span> Quay lại giỏ hàng</a>
            <?php // include a hidden checkout token if available to help recover order ?>
            <?php if (!empty($_SESSION['checkout_token'])): ?>
              <input type="hidden" name="checkout_token" value="<?= htmlspecialchars($_SESSION['checkout_token']) ?>">
            <?php endif; ?>
            <button type="submit" class="btn-pay">THANH TOÁN VÀ GIAO HÀNG</button>
          </div>
          </form>
        </div>

        <div class="delivery-content-right">
          <div class="order-summary">
            <h3>Đơn hàng của bạn</h3>

            <?php if ($checkout && !empty($checkout['items'])): ?>
              <div class="order-items">
                <?php foreach ($checkout['items'] as $it): ?>
                  <div class="order-item">
                    <div class="thumb">
                      <?php $img = !empty($it['image']) ? $it['image'] : '/fashionstore/uploads/no-image.jpg'; ?>
                      <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($it['product_name']) ?>">
                    </div>
                    <div class="meta">
                      <div class="name"><?= htmlspecialchars($it['product_name']) ?></div>
                      <?php if (!empty($it['variant'])): ?>
                        <div class="variant">Size: <?= htmlspecialchars($it['variant']) ?></div>
                      <?php endif; ?>
                      <div class="qty">Số lượng: <?= (int)$it['quantity'] ?></div>
                    </div>
                    <div class="price">
                      <div class="sub"><?= number_format($it['price'], 0, ',', '.') ?>đ</div>
                      <div class="unit"><?= number_format($it['price'] * $it['quantity'], 0, ',', '.') ?>đ</div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="order-totals">
                <div class="row"><span>Tạm tính</span><span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span></div>
                <div class="row"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
                <div class="row total"><span>Thành tiền</span><span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span></div>
              </div>

              <form method="post" class="pay-form">
                <input type="hidden" name="action" value="confirm_payment">
                
              </form>

            <?php else: ?>
              <div class="no-checkout">Không tìm thấy thông tin thanh toán. Vui lòng quay lại giỏ hàng.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
$content = ob_get_clean();
// include the main layout (path relative to product/function -> go up two levels)
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
?>
