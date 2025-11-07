<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
  header('Location: product.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
  if (empty($_SESSION['loggedin']) || empty($_SESSION['user_id'])) {
    header('Location: /fashionstore/index.php?page=login&return=' . urlencode('/fashionstore/index.php?page=product_detail&id=' . $product_id));
    exit;
  }

  $user_id   = (int)$_SESSION['user_id'];
  $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
  $qty        = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

  $sqlVar = "SELECT variant_id, product_id, price, stock_quantity 
             FROM ProductVariants 
             WHERE variant_id = ? AND product_id = ?";
  $stmtVar = mysqli_prepare($conn, $sqlVar);
  mysqli_stmt_bind_param($stmtVar, 'ii', $variant_id, $product_id);
  mysqli_stmt_execute($stmtVar);
  $rsVar = mysqli_stmt_get_result($stmtVar);
  $variant = mysqli_fetch_assoc($rsVar);

  if (!$variant) {
    $err = "Biến thể không hợp lệ.";
  } else {
    if ($qty > (int)$variant['stock_quantity']) {
      $err = "Số lượng vượt quá tồn kho.";
    } else {
      mysqli_begin_transaction($conn);
      try {
        $sqlCart = "SELECT cart_id FROM Carts WHERE user_id = ? LIMIT 1";
        $stmtCart = mysqli_prepare($conn, $sqlCart);
        mysqli_stmt_bind_param($stmtCart, 'i', $user_id);
        mysqli_stmt_execute($stmtCart);
        $rsCart = mysqli_stmt_get_result($stmtCart);

        if ($rowCart = mysqli_fetch_assoc($rsCart)) {
          $cart_id = (int)$rowCart['cart_id'];
          mysqli_query($conn, "UPDATE Carts SET updated_at = NOW() WHERE cart_id = {$cart_id}");
        } else {
          $sqlInsCart = "INSERT INTO Carts (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())";
          $stmtInsCart = mysqli_prepare($conn, $sqlInsCart);
          mysqli_stmt_bind_param($stmtInsCart, 'i', $user_id);
          mysqli_stmt_execute($stmtInsCart);
          $cart_id = (int)mysqli_insert_id($conn);
        }

        $sqlHas = "SELECT cart_item_id, quantity FROM CartItems WHERE cart_id = ? AND variant_id = ? LIMIT 1";
        $stmtHas = mysqli_prepare($conn, $sqlHas);
        mysqli_stmt_bind_param($stmtHas, 'ii', $cart_id, $variant_id);
        mysqli_stmt_execute($stmtHas);
        $rsHas = mysqli_stmt_get_result($stmtHas);

        if ($rowItem = mysqli_fetch_assoc($rsHas)) {
          $newQty = (int)$rowItem['quantity'] + $qty;
          $upd = mysqli_prepare($conn, "UPDATE CartItems SET quantity = ?, price_at_added = ?, added_at = NOW() WHERE cart_item_id = ?");
          $price = (float)$variant['price'];
          mysqli_stmt_bind_param($upd, 'idi', $newQty, $price, $rowItem['cart_item_id']);
          mysqli_stmt_execute($upd);
        } else {
          $ins = mysqli_prepare($conn, "INSERT INTO CartItems (cart_id, variant_id, quantity, price_at_added, added_at) VALUES (?, ?, ?, ?, NOW())");
          $price = (float)$variant['price'];
          mysqli_stmt_bind_param($ins, 'iiid', $cart_id, $variant_id, $qty, $price);
          mysqli_stmt_execute($ins);
        }

        mysqli_commit($conn);
        header('Location: cart.php?added=1');
        exit;
      } catch (Exception $ex) {
        mysqli_rollback($conn);
        $err = "Có lỗi khi thêm sản phẩm vào giỏ. Vui lòng thử lại!";
      }
    }
  }
}

$sqlP = "SELECT p.product_id, p.product_name, p.description, 
                COALESCE(MIN(v.price),0) AS min_price
         FROM Products p
         LEFT JOIN ProductVariants v ON p.product_id = v.product_id
         WHERE p.product_id = ?
         GROUP BY p.product_id";
$stmtP = mysqli_prepare($conn, $sqlP);
mysqli_stmt_bind_param($stmtP, 'i', $product_id);
mysqli_stmt_execute($stmtP);
$rsP = mysqli_stmt_get_result($stmtP);
$product = mysqli_fetch_assoc($rsP);
if (!$product) {
  header('Location: product.php');
  exit;
}

// === LẤY ẢNH SẢN PHẨM ===
$imgs = [];
$rsImg = mysqli_query($conn, "SELECT image_url, alt_text FROM ProductImages WHERE product_id = {$product_id}");
while ($row = mysqli_fetch_assoc($rsImg)) $imgs[] = $row;
if (empty($imgs)) {
  $imgs[] = ['image_url' => 'uploads/no-image.png', 'alt_text' => $product['product_name']];
}

// Sửa đường dẫn ảnh cho đúng tuyệt đối
foreach ($imgs as &$img) {
  $path = $img['image_url'];
  if (strpos($path, 'http') !== 0 && strpos($path, '/FashionStore3/') !== 0 && strpos($path, '/fashionstore/') !== 0) {
    $path = '/fashionstore/' . ltrim($path, '/');
  }
    $img['image_url'] = $path;
}
unset($img);

// === LẤY BIẾN THỂ ===
$variants = [];
$rsVarAll = mysqli_query($conn, "SELECT variant_id, size, price, stock_quantity 
                                 FROM ProductVariants 
                                 WHERE product_id = {$product_id}
                                 ORDER BY size ASC");
while ($row = mysqli_fetch_assoc($rsVarAll)) $variants[] = $row;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['product_name']) ?> - Chi tiết</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>

<header>
  <div class="logo"><a href="/fashionstore/index.php" style="text-decoration:none;color:white;">Vogue Lane Clothing</a></div>
  <nav class="navbar">
    <ul>
  <li><a href="/fashionstore/index.php">Trang chủ</a></li>
  <li><a href="/fashionstore/index.php?page=product">Sản phẩm</a></li>
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
        <span class="user-name">Xin chào, <?= htmlspecialchars($_SESSION['user']['full_name']); ?></span>
        <a href="/fashionstore/index.php?page=logout" class="logout-btn" style="color:#c33;text-decoration:none;">Đăng xuất</a>
      <?php else: ?>
        <a href="/fashionstore/index.php?page=login"><i class="fa-solid fa-user"></i></a>
      <?php endif; ?>
      <a href="/fashionstore/index.php?page=cart" class="cart-icon">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="cart-count">0</span>
      </a>
    </div>
  </div>
</header>

<main class="detail-wrap">
  <?php if (!empty($err)): ?>
    <div style="background:#ffecec;color:#c0392b;padding:10px 12px;border-radius:8px;margin-bottom:10px">
      <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <div class="detail-grid">
    <div class="detail-photos">
      <div class="thumbs">
        <?php foreach ($imgs as $i => $img): ?>
          <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="<?= htmlspecialchars($img['alt_text']) ?>"
               class="<?= $i===0?'active':'' ?>" data-full="<?= htmlspecialchars($img['image_url']) ?>">
        <?php endforeach; ?>
      </div>
      <div class="main-photo">
        <img id="mainImage" src="<?= htmlspecialchars($imgs[0]['image_url']) ?>" alt="<?= htmlspecialchars($imgs[0]['alt_text']) ?>">
      </div>
    </div>

    <div class="detail-info">
      <h1><?= htmlspecialchars($product['product_name']) ?></h1>
      <div class="price-box">
        <div class="price-main" id="displayPrice">
          <?= number_format($product['min_price'], 0, ",", ".") ?>đ
        </div>
        <ul style="margin:8px 0 0 18px; color:#444">
          <li>Thiết kế hiện đại, chất liệu thoáng mát.</li>
        </ul>
      </div>

      <form method="post">
        <input type="hidden" name="action" value="add_to_cart">

        <div class="opts">
          <div><b>Kích thước:</b></div>
          <div class="chips">
            <?php if (empty($variants)): ?>
              <span>Hiện chưa có biến thể.</span>
            <?php else: ?>
              <?php foreach ($variants as $k => $v): ?>
                <label class="chip">
                  <input type="radio" name="variant_id" value="<?= (int)$v['variant_id'] ?>" <?= $k===0?'checked':'' ?> hidden>
                  <span><?= htmlspecialchars($v['size'] ?: 'Free') ?></span>
                  <span class="vprice" data-price="<?= (float)$v['price'] ?>" hidden></span>
                </label>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="qty">
            <label>
              <b>Số lượng:</b>
              <input type="number" name="quantity" value="1" min="1" required
                     data-stock="0" class="qty-input">
            </label>
            <span class="stock">Còn <span class="stock-qty">0</span> sản phẩm</span>
          </div>
        </div>

        <div class="cta">
          <button type="submit" class="btn-primary" id="addToCartBtn" disabled>
            <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
          </button>
          <a href="/fashionstore/index.php?page=cart" class="btn-outline">Xem giỏ hàng</a>
        </div>
      </form>

      <input type="hidden" id="productId" value="<?= (int)$product_id ?>">

      <div class="desc">
        <h3>Mô tả sản phẩm</h3>
        <div><?= nl2br(htmlspecialchars($product['description'] ?: 'Đang cập nhật...')) ?></div>
      </div>
    </div>
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

  <script src="/fashionstore/js/app.js"></script>

<script>
document.querySelectorAll('.thumbs img').forEach(function(img){
  img.addEventListener('click', function(){
    document.getElementById('mainImage').src = this.dataset.full;
    document.querySelectorAll('.thumbs img').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
  });
});

const chips = document.querySelectorAll('.chip input[name="variant_id"]');
function fmt(n){ return n.toLocaleString('vi-VN') + 'đ'; }
function setPriceFromChecked(){
  const checked = document.querySelector('.chip input[name="variant_id"]:checked');
  if(!checked) return;
  const priceEl = checked.parentElement.querySelector('.vprice');
  const price = parseFloat(priceEl.dataset.price || "0");
  document.getElementById('displayPrice').textContent = fmt(price);
}
chips.forEach(r => r.addEventListener('change', setPriceFromChecked));
setPriceFromChecked();

const qtyInput = document.querySelector('.qty-input');
const stockSpan = document.querySelector('.stock-qty');
const addBtn = document.getElementById('addToCartBtn');
const variants = <?= json_encode($variants) ?>;

function updateStock() {
    const checkedVariant = document.querySelector('.chip input[name="variant_id"]:checked');
    if (!checkedVariant) return;
    const variant = variants.find(v => v.variant_id === parseInt(checkedVariant.value));
    if (!variant) return;
    const stock = parseInt(variant.stock_quantity);
    qtyInput.max = stock;
    qtyInput.dataset.stock = stock;
    stockSpan.textContent = stock;
    validateQuantity();
}

function validateQuantity() {
    const qty = parseInt(qtyInput.value);
    const stock = parseInt(qtyInput.dataset.stock);
    const isValid = qty > 0 && qty <= stock;
    addBtn.disabled = !isValid;
    if (qty < 1) {
        qtyInput.setCustomValidity('Số lượng phải lớn hơn 0');
    } else if (qty > stock) {
        qtyInput.setCustomValidity('Số lượng vượt quá tồn kho');
    } else {
        qtyInput.setCustomValidity('');
    }
}
qtyInput.oninput = validateQuantity;
chips.forEach(r => r.addEventListener('change', updateStock));
updateStock();
</script>
</body>
</html>
