<?php
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
  header('Location: login.php?return=' . urlencode('cart.php'));
  exit;
}

if (!empty($_SESSION['user'])) {
  $user_id = (int)$_SESSION['user']['user_id'];
} else {
  $user_id = (int)$_SESSION['user_id'];
}

var_dump($user_id);

$cart = mysqli_fetch_assoc(mysqli_query($conn, 
  "SELECT cart_id FROM Carts WHERE user_id = {$user_id} LIMIT 1"
));

$items = [];
$total_amount = 0;
$total_items = 0;

if ($cart) {
  $sql = "
    SELECT ci.cart_item_id, ci.quantity, ci.price_at_added,
         p.product_id, p.product_name,
         v.size, v.stock_quantity,
         COALESCE(pi.image_url, 'uploads/no-image.jpg') as image_url
    FROM CartItems ci
    JOIN ProductVariants v ON ci.variant_id = v.variant_id
    JOIN Products p ON v.product_id = p.product_id
    LEFT JOIN (
      SELECT product_id, MIN(image_url) as image_url 
      FROM ProductImages 
      GROUP BY product_id
    ) pi ON p.product_id = pi.product_id
    WHERE ci.cart_id = {$cart['cart_id']}
    ORDER BY ci.added_at DESC
  ";
    
  $result = mysqli_query($conn, $sql);
  while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
    $total_amount += $row['price_at_added'] * $row['quantity'];
    $total_items += $row['quantity'];
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng - FashionStore</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/cart.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <header>
    <div class="logo">Vogue Lane Clothing</div>
    <nav class="navbar">
      <ul>
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="product.php">Sản phẩm</a></li>
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
        <?php if (!empty($_SESSION['user'])): ?>
          <span class="user-name" title="<?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>" 
              style="margin-right:10px;font-weight:600;color:#fff;">
            Xin chào, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>
          </span>
          <a href="logout.php" class="logout-btn" style="margin-right:8px;color:#c33;text-decoration:none;">
            Đăng xuất
          </a>
        <?php else: ?>
          <a href="login.php"><i class="fa-solid fa-user"></i></a>
        <?php endif; ?>
        <a href="cart.php" class="cart-icon">
          <i class="fa-solid fa-cart-shopping"></i>
          <span class="cart-count"><?= $total_items ?></span>
        </a>
      </div>
    </div>
  </header>

  <main class="cart-page">
    <div class="cart-container">
      <h1>Giỏ hàng của bạn</h1>
            
      <?php if (empty($items)): ?>
      <div class="cart-empty">
        <i class="fas fa-shopping-cart"></i>
        <p>Giỏ hàng trống</p>
        <a href="product.php" class="btn-primary">Tiếp tục mua sắm</a>
      </div>
      <?php else: ?>
            
      <div class="cart-content">
        <div class="cart-items">
          <?php foreach ($items as $item): ?>
          <div class="cart-item" data-id="<?= $item['cart_item_id'] ?>">
            <div class="item-image">
              <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                 alt="<?= htmlspecialchars($item['product_name']) ?>">
            </div>
                        
            <div class="item-details">
              <h3>
                <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                  <?= htmlspecialchars($item['product_name']) ?>
                </a>
              </h3>
              <p class="size">Size: <?= htmlspecialchars($item['size']) ?></p>
              <p class="price"><?= number_format($item['price_at_added'], 0, ",", ".") ?>đ</p>
            </div>
                        
            <div class="item-quantity">
              <div class="quantity-control">
                <button type="button" class="qty-btn minus">-</button>
                <input type="number" value="<?= $item['quantity'] ?>" 
                     min="1" max="<?= $item['stock_quantity'] ?>"
                     class="qty-input"
                     data-id="<?= $item['cart_item_id'] ?>"
                     data-price="<?= $item['price_at_added'] ?>">
                <button type="button" class="qty-btn plus">+</button>
              </div>
              <button class="remove-item">
                <i class="fas fa-trash"></i> Xóa
              </button>
            </div>
                        
            <div class="item-total">
              <?= number_format($item['price_at_added'] * $item['quantity'], 0, ",", ".") ?>đ
            </div>
          </div>
          <?php endforeach; ?>
        </div>
                
        <div class="cart-summary">
          <h3>Tổng giỏ hàng</h3>
          <div class="summary-row">
            <span>Tổng sản phẩm:</span>
            <span class="total-items"><?= $total_items ?></span>
          </div>
          <div class="summary-row">
            <span>Tạm tính:</span>
            <span class="total-amount"><?= number_format($total_amount, 0, ",", ".") ?>đ</span>
          </div>
          <div class="summary-row">
            <span>Phí vận chuyển:</span>
            <span>Miễn phí</span>
          </div>
          <div class="summary-row total">
            <span>Thành tiền:</span>
            <span class="final-total"><?= number_format($total_amount, 0, ",", ".") ?>đ</span>
          </div>
                    
          <div class="cart-actions">
            <a href="product.php" class="btn-outline">
              <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
            </a>
            <a href="checkout.php" class="btn-primary">
              Thanh toán <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>

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
    <p class="copyright">© <?= date('Y') ?> Vogue Lane Clothing - Bản quyền thuộc về chúng tôi</p>
  </footer>

  <script src="js/cart.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.cart-items');
    if (!container) return;
    function updateTotals() {
      let totalItems = 0;
      let totalAmount = 0;
            
      document.querySelectorAll('.cart-item').forEach(item => {
        const qty = parseInt(item.querySelector('.qty-input').value);
        const price = parseFloat(item.querySelector('.qty-input').dataset.price);
        totalItems += qty;
        totalAmount += qty * price;
        item.querySelector('.item-total').textContent = 
          new Intl.NumberFormat('vi-VN').format(qty * price) + 'đ';
      });
      document.querySelector('.total-items').textContent = totalItems;
      document.querySelector('.total-amount').textContent = 
        new Intl.NumberFormat('vi-VN').format(totalAmount) + 'đ';
      document.querySelector('.final-total').textContent = 
        new Intl.NumberFormat('vi-VN').format(totalAmount) + 'đ';
      updateCartCount(totalItems);
    }
    container.addEventListener('click', function(e) {
      if (!e.target.classList.contains('qty-btn')) return;
            
      const input = e.target.parentNode.querySelector('.qty-input');
      const currentVal = parseInt(input.value);
      const max = parseInt(input.max);
            
      if (e.target.classList.contains('minus')) {
        if (currentVal > 1) input.value = currentVal - 1;
      } else {
        if (currentVal < max) input.value = currentVal + 1;
      }
            
      updateQuantity(input);
    });

    container.addEventListener('change', function(e) {
      if (!e.target.classList.contains('qty-input')) return;
      updateQuantity(e.target);
    });

    container.addEventListener('click', async function(e) {
      const removeBtn = e.target.closest('.remove-item');
      if (!removeBtn) return;
            
      if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
            
      const item = removeBtn.closest('.cart-item');
      const itemId = item.dataset.id;
            
      try {
        const response = await fetch('api/remove_from_cart.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ cart_item_id: itemId })
        });
                
        const result = await response.json();
        if (!response.ok) throw new Error(result.error);
                
        item.remove();
        updateTotals();
                
        if (document.querySelectorAll('.cart-item').length === 0) {
          location.reload(); 
        }
                
        showMessage('Đã xóa sản phẩm khỏi giỏ hàng');
      } catch (error) {
        showMessage(error.message, true);
      }
    });

    // Cập nhật số lượng lên server
    async function updateQuantity(input) {
      const newQty = parseInt(input.value);
      const itemId = input.dataset.id;
            
      try {
        const response = await fetch('api/update_cart.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            cart_item_id: itemId,
            quantity: newQty
          })
        });
                
        const result = await response.json();
        if (!response.ok) throw new Error(result.error);
                
        updateTotals();
      } catch (error) {
        showMessage(error.message, true);
        input.value = input.defaultValue;
        updateTotals();
      }
    }
  });
  </script>
</body>
</html>

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
  <section class="cart">
    <div class="container">
        <div class="cart-content">
            <div class="cart-content-left">
                <table>
                    <tr>
                        <th>Sản phẩm </th>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Xóa</th>
                    </tr>
                    <tr>
                        <td><img src="" alt=""></td>
                        <td><p>Tên sản phẩm</p></td>
                        <td><img src="" alt=""></td>
                        <td><input type="number" value="1" min="1"></td>
                        <td><p>...<sub>đ</sub></p></td>
                        <td><span>X</span></td>
                    </tr>
                </table>
            </div>
            <div class="cart-content-right">
                <table>
                    <tr>
                        <th colspan="2">Tổng tiền giỏ hàng</th>
                    </tr>
                    <tr>
                        <td>TỔNG SẢM PHẨM</td>
                        <td>2</td>
                    </tr>
                    <tr>
                        <td>TỔNG TIỀN HÀNG</td>
                        <td><p>...<sub>đ</sub></p></td>
                    </tr>
                    <tr>
                        <td>TẠM TÍNH</td>
                        <td><p style="color: black;font-weight: bold;">...<sub>đ</sub></p></td>
                    </tr>
                </table>
                <div class="cart-content-right-text">
                    <p>Bạn sẽ được miễn phí ship khi đơn hàng của bạn có tổng giá trị trên 2.000.000đ</p>
                </div>
                <div class="cart-content-right-button">
                    <button>TIẾP TỤC MUA SẢN PHẨM</button>
                    <a href="pay.php" style="color:white; text-decoration: none;;"><button>THANH TOÁN</button></a>
                </div>
                
            </div>
        </div>
    </div>
</section>
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


</body>
</html>