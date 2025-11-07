<?php include_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thanh toán - FashionStore</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
    <div class="logo">Vogue Lane Clothing</div>
    <nav class="navbar">
      <ul>
        <li><a href="index.php" class="active">Trang chủ</a></li>
        <li><a href="product.php">Sản phẩm</a></li>
        <li class="dropdown">
          <a href="#" class="toggle-btn">☰ Danh mục</a>
          <div class="mega-menu">
            <div class="mega-column">
              <h4>Sản Phẩm áo</h4>
              <a href="#">Áo dài</a>
              <a href="#">ÁO thun</a>
              <a href="#">ÁO khoác</a>
            </div>

            <div class="mega-column">
              <h4>Sản phẩm quần</h4>
              <a href="#">quần jean</a>
              <a href="#">quần short</a>
            </div>

            <div class="mega-column">
              <h4>phụ kiên</h4>
              <a href="#">Dây nịt</a>
              <a href="#">dây chuyền</a>
            </div>

            <div class="mega-column">
              <h4>Ưu Đãi Đặc Biệt</h4>
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
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
      </div>
      
    </div>
    </header>
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
          <a href="cart.php" class="back-link"><span>&#171;</span> Quay lại giỏ hàng</a>
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

    <script src="js/app.js"></script>
 
</body>
</html>
