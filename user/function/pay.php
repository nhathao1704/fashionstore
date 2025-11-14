<?php
require_once __DIR__ . '/../../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Thanh toán - FashionStore';
// Lấy dữ liệu thanh toán được chuyển từ `cart.php` (qua session)
$checkout = $_SESSION['checkout_order'] ?? null;
// Nếu chưa đăng nhập → quay về trang login
if (empty($_SESSION['user'])) {
    header('Location: /fashionstore/index.php?page=login&return=' . urlencode('/fashionstore/index.php?page=pay'));
    exit;
}

// Nếu đã đăng nhập, lấy thông tin người dùng

$user_info = null;
$user_id = $_SESSION['user']['user_id'];

$stmt = mysqli_prepare($conn, "SELECT full_name, phone_number,district, city, address FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_info = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

ob_start();
?>
<main>
  <section class="delivery">
    <div class="container">
      <div class="delivery-content">
          <div class="delivery-content-left">
           <form method="post" action="/fashionstore/index.php?page=purchase_order" class="pay-form">
            <p>Vui lòng chọn địa chỉ giao hàng</p>

            <div class="delivery-content-left-input-top">
              <div class="delivery-content-left-input-top-item">
                <label>Họ tên <span style="color:red;">*</span></label>
                <input type="text" name="full_name" required 
                       value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>">
              </div>

              <div class="delivery-content-left-input-top-item">
                <label>Điện thoại <span style="color:red;">*</span></label>
                <input type="text" name="phone" required 
                       value="<?= htmlspecialchars($user_info['phone_number'] ?? '') ?>">
              </div>

              <div class="delivery-content-left-input-top-item">
                <label>Tỉnh/Thành phố <span style="color:red;">*</span></label>
                <input type="text" name="city" required 
                       value="<?= htmlspecialchars($user_info['city'] ?? '') ?>">
              </div>

              <div class="delivery-content-left-input-top-item">
                <label>Quận/Huyện <span style="color:red;">*</span></label>
                <input type="text" name="district" required 
                       value="<?= htmlspecialchars($user_info['district'] ?? '') ?>">
              </div>
            </div>

            <div class="delivery-content-left-input-bottom">
              <label>Địa chỉ <span style="color:red;">*</span></label>
              <input type="text" name="address" required 
                     value="<?= htmlspecialchars($user_info['address'] ?? '') ?>">
            </div>
            <div class="delivery-content-left-button">
              <a href="index.php?page=cart" class="back-link"><span>&#171;</span> Quay lại giỏ hàng</a>
              <?php // include a hidden checkout token if available to help recover order ?>
              <?php if (!empty($_SESSION['checkout_token'])): ?>
                <input type="hidden" name="checkout_token" value="<?= htmlspecialchars($_SESSION['checkout_token']) ?>">
              <?php endif; ?>
              <button type="submit" class="btn-pay">THANH TOÁN VÀ GIAO HÀNG</button>
            </div>
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

