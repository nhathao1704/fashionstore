<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Thiết lập thông tin trang
$layout = 'main';
$page_title = 'Thanh toán - FashionStore';

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

          <div class="delivery-content-left-khachle row">
            <input checked name="loaikhach" type="radio">
            <p><span style="font-weight:bold;">Khách lẻ</span> (Nếu bạn không muốn lưu lại thông tin)</p>
          </div>

          <div class="delivery-content-left-dangky row">
            <input name="loaikhach" type="radio">
            <p><span style="font-weight:bold;">Đăng ký</span> (Tạo tài khoản với thông tin bên dưới)</p>
          </div>

          <div class="delivery-content-left-input-top">
            <div class="delivery-content-left-input-top-item">
              <label>Họ tên <span style="color:red;">*</span></label>
              <input type="text" placeholder="Nhập họ và tên">
            </div>
            <div class="delivery-content-left-input-top-item">
              <label>Điện thoại <span style="color:red;">*</span></label>
              <input type="text" placeholder="Số điện thoại">
            </div>
            <div class="delivery-content-left-input-top-item">
              <label>Tỉnh/Thành phố <span style="color:red;">*</span></label>
              <input type="text" placeholder="Ví dụ: Hà Nội">
            </div>
            <div class="delivery-content-left-input-top-item">
              <label>Quận/Huyện <span style="color:red;">*</span></label>
              <input type="text" placeholder="Ví dụ: Ba Đình">
            </div>
          </div>

          <div class="delivery-content-left-input-bottom">
            <label>Địa chỉ <span style="color:red;">*</span></label>
            <input type="text" placeholder="Số nhà, tên đường...">
          </div>

          <div class="delivery-content-left-button">
            <a href="index.php?page=cart" class="back-link"><span>&#171;</span> Quay lại giỏ hàng</a>
            <button class="btn-pay">THANH TOÁN VÀ GIAO HÀNG</button>
          </div>
        </div>

        <div class="delivery-content-right">
          <table>
            <tr>
              <th>Tên sản phẩm</th>
              <th>Giảm giá</th>
              <th>Số lượng</th>
              <th>Thành tiền</th>
            </tr>
            <tr>
              <td>áo dài</td>
              <td>-20%</td>
              <td>1</td>
              <td><p>120.000 <sup>đ</sup></p></td>
            </tr>
           
            <tr>
              <td colspan="3" style="font-weight:bold;">Tổng</td>
              <td style="font-weight:bold;">360.000 <sup>đ</sup></td>
            </tr>
            <tr>
              <td colspan="3" style="font-weight:bold;">Thuế VAT</td>
              <td style="font-weight:bold;">36.000 <sup>đ</sup></td>
            </tr>
            <tr>
              <td colspan="3" style="font-weight:bold;">Tổng tiền hàng</td>
              <td style="font-weight:bold; color:red;">396.000 <sup>đ</sup></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/../includes/layouts/' . $layout . '.php';
?>
