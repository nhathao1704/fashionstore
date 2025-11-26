<?php
require_once __DIR__ . '/../../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Thanh toán - FashionStore';

// Lấy dữ liệu thanh toán từ session
$checkout = $_SESSION['checkout_order'] ?? null;

// Nếu chưa đăng nhập → quay về trang login
if (empty($_SESSION['user'])) {
    header('Location: /fashionstore/index.php?page=login&return=' . urlencode('/fashionstore/index.php?page=pay'));
    exit;
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user']['user_id'];

$stmt = mysqli_prepare($conn, "SELECT full_name, phone_number, district, city, address FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_info = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);


// LẤY DANH SÁCH KHUYẾN MÃI ĐANG HOẠT ĐỘNG

$sqlListPromo = "
    SELECT *
    FROM promotions
    WHERE is_active = 1
      AND start_date <= NOW()
      AND end_date >= NOW()
";
$listPromo = mysqli_query($conn, $sqlListPromo);

// ======================
// ÁP DỤNG KHUYẾN MÃI
// ======================
$applied_coupon_name = "";
$discount_amount = 0;

// Xử lý khi user bấm "ÁP DỤNG" mã trong popup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_promo') {

    $pid = intval($_POST['promotion_id']);

    $sqlP = "SELECT * FROM promotions WHERE promotion_id = ?";
    $st = mysqli_prepare($conn, $sqlP);
    mysqli_stmt_bind_param($st, "i", $pid);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $promo = mysqli_fetch_assoc($res);
    mysqli_stmt_close($st);

    if ($promo) {

        // Kiểm tra điều kiện tối thiểu
        if ($checkout['total_amount'] < $promo['min_order_value']) {

            $_SESSION['promo_error'] = "Đơn hàng chưa đủ điều kiện (" .
                number_format($promo['min_order_value'], 0, ',', '.') . "đ) để dùng mã này.";

            unset($_SESSION['applied_promo']);
            $discount_amount = 0;
            $applied_coupon_name = "";
        } else {

            // Tính giảm giá
            if ($promo['discount_type'] === 'percentage') {
                $discount_amount = $checkout['total_amount'] * ($promo['discount_value'] / 100);
            } else {
                $discount_amount = $promo['discount_value'];
            }

            if ($discount_amount > $checkout['total_amount']) {
                $discount_amount = $checkout['total_amount'];
            }

            // Lưu vào session
            $_SESSION['applied_promo'] = [
                'promotion_id' => $pid,
                'promotion_name' => $promo['promotion_name'],
                'discount_amount' => $discount_amount
            ];

            $applied_coupon_name = $promo['promotion_name'];
        }
    }
}

// Nếu đã có KM trước đó
if (isset($_SESSION['applied_promo']) && empty($applied_coupon_name)) {
    $discount_amount = $_SESSION['applied_promo']['discount_amount'];
    $applied_coupon_name = $_SESSION['applied_promo']['promotion_name'];
}

// Tổng sau giảm
$final_total = $checkout['total_amount'] - $discount_amount;

ob_start();
?>
<main>
  <section class="delivery">
    <div class="container">
      <div class="delivery-content">

        <!-- LEFT -->
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

              <?php if (!empty($_SESSION['checkout_token'])): ?>
                <input type="hidden" name="checkout_token" value="<?= htmlspecialchars($_SESSION['checkout_token']) ?>">
              <?php endif; ?>

              <!-- Lưu promotion -->
              <?php if (isset($_SESSION['applied_promo'])): ?>
                <input type="hidden" name="promotion_id" value="<?= $_SESSION['applied_promo']['promotion_id'] ?>">
                <input type="hidden" name="discount_amount" value="<?= $_SESSION['applied_promo']['discount_amount'] ?>">
              <?php endif; ?>

              <button type="submit" class="btn-pay">THANH TOÁN VÀ GIAO HÀNG</button>
            </div>
          </form>
        </div>

        <!-- RIGHT -->
        <div class="delivery-content-right">
          <div class="order-summary">
            <h3>Đơn hàng của bạn</h3>

            <!-- KHUNG CHỌN KHUYẾN MÃI -->
            <div class="promo-select-box" style="margin-bottom:15px;">
                <button type="button" class="btn-select-promo" onclick="openPromoPopup()" 
                        style="padding:8px 12px;background:#000;color:#fff;border:none;border-radius:5px;cursor:pointer;">
                    Chọn mã giảm giá
                </button>

                <?php if ($applied_coupon_name): ?>
                    <p style="color:green;margin-top:5px;">Đã áp dụng: <b><?= $applied_coupon_name ?></b></p>
                <?php endif; ?>

                <?php if (!empty($_SESSION['promo_error'])): ?>
                    <p style="color:red;margin-top:5px;">
                        <?= $_SESSION['promo_error']; ?>
                    </p>
                    <?php unset($_SESSION['promo_error']); ?>
                <?php endif; ?>
            </div>

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

              <!-- TỔNG TIỀN -->
              <div class="order-totals">
                <div class="row">
                  <span>Tạm tính</span>
                  <span><?= number_format($checkout['total_amount'], 0, ',', '.') ?>đ</span>
                </div>

                <?php if ($discount_amount > 0): ?>
                <div class="row">
                  <span>Khuyến mãi: <?= $applied_coupon_name ?></span>
                  <span>-<?= number_format($discount_amount, 0, ',', '.') ?>đ</span>
                </div>
                <?php endif; ?>

                <div class="row">
                  <span>Phí vận chuyển</span>
                  <span>Miễn phí</span>
                </div>

                <div class="row total">
                  <span>Thành tiền</span>
                  <span style="color:red;font-weight:bold;">
                    <?= number_format($final_total, 0, ',', '.') ?>đ
                  </span>
                </div>
              </div>

            <?php else: ?>
              <div class="no-checkout">Không tìm thấy thông tin thanh toán. Vui lòng quay lại giỏ hàng.</div>
            <?php endif; ?>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- POPUP KHUYẾN MÃI -->
  <div id="promo-popup" class="promo-popup" 
       style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);justify-content:center;align-items:center;z-index:9999;">
    <div class="promo-popup-content" 
         style="background:#fff;padding:20px;width:420px;border-radius:10px;position:relative;">
        
        <h3>Chọn mã giảm giá</h3>
        <button class="promo-close" onclick="closePromoPopup()" 
                style="position:absolute;right:10px;top:10px;cursor:pointer;font-size:18px;">✖</button>

        <ul style="list-style:none;padding:0;margin-top:15px;max-height:400px;overflow-y:auto;">
        <?php if (mysqli_num_rows($listPromo) > 0): ?>
            <?php while ($p = mysqli_fetch_assoc($listPromo)): ?>
                <?php
                $is_eligible = ($checkout['total_amount'] >= $p['min_order_value']);
                ?>
                <li style="margin-bottom:10px;">
                    <div style="border:1px solid #ddd;padding:12px;border-radius:6px;">
                        
                        <b style="font-size:16px;"><?= htmlspecialchars($p['promotion_name']) ?></b><br>

                        <div style="margin-top:5px;font-size:14px;line-height:22px;">
                            • <?= ($p['discount_type']=='percentage'
                                    ? 'Giảm '.$p['discount_value'].'%'
                                    : 'Giảm '.number_format($p['discount_value'],0,',','.').'đ') ?><br>

                            <?php if ($p['min_order_value'] > 0): ?>
                                • Áp dụng cho đơn từ 
                                  <b><?= number_format($p['min_order_value'],0,',','.') ?>đ</b><br>
                            <?php endif; ?>

                            <span style="color:<?= $is_eligible ? 'green' : 'red' ?>;">
                                <?= $is_eligible ? '✔ Đủ điều kiện' : '✘ Chưa đủ điều kiện' ?>
                            </span>
                        </div>

                        <?php if ($is_eligible): ?>
                            <form method="post" style="margin-top:10px;">
                                <input type="hidden" name="action" value="apply_promo">
                                <input type="hidden" name="promotion_id" value="<?= $p['promotion_id'] ?>">
                                <button class="btn-apply-promo"
                                        style="width:100%;padding:6px;border:none;background:#000;color:#fff;border-radius:5px;cursor:pointer;">
                                    Áp dụng
                                </button>
                            </form>
                        <?php else: ?>
                            <button disabled 
                                    style="width:100%;padding:6px;border:none;background:#ccc;color:#666;border-radius:5px;margin-top:10px;">
                                Không đủ điều kiện
                            </button>
                        <?php endif; ?>
                        
                    </div>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Không có mã giảm giá.</p>
        <?php endif; ?>
        </ul>

    </div>
  </div>

</main>

<script>
function openPromoPopup() {
    document.getElementById('promo-popup').style.display = 'flex';
}
function closePromoPopup() {
    document.getElementById('promo-popup').style.display = 'none';
}
</script>

