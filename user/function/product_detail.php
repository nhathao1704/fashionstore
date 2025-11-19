<?php
require_once __DIR__ . '/../../config/config.php';


// Lấy ID sản phẩm

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    echo '<p>Không tìm thấy sản phẩm.</p>';
    return;
}



// XỬ LÝ THÊM VÀO GIỎ HÀNG (PHP THUẦN)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['variant_id'])) {

    if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
        $returnUrl = urlencode("/fashionstore/index.php?page=product_detail&id={$product_id}");
        header("Location: /fashionstore/index.php?page=login&return={$returnUrl}");
        exit;
    }

    $user_id = !empty($_SESSION['user'])
        ? (int)$_SESSION['user']['user_id']
        : (int)$_SESSION['user_id'];

    $variant_id = (int)$_POST['variant_id'];
    $qty = max(1, (int)$_POST['quantity']);

    $product_name = $_POST['product_name'] ?? 'Sản phẩm';
    $product_size = $_POST['variant_size'] ?? '';

    // Lấy dữ liệu biến thể
    $v = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT price, stock_quantity FROM ProductVariants WHERE variant_id={$variant_id} LIMIT 1"
    ));

    if (!$v) {
        $_SESSION['flash_error'] = "Biến thể không tồn tại!";
        header("Location: /fashionstore/index.php?page=product_detail&id={$product_id}");
        exit;
    }

    if ($qty > $v['stock_quantity']) {
        $_SESSION['flash_error'] = "Số lượng vượt quá tồn kho!";
        header("Location: /fashionstore/index.php?page=product_detail&id={$product_id}");
        exit;
    }

    // Tạo giỏ nếu chưa có
    $cart = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT cart_id FROM Carts WHERE user_id={$user_id} LIMIT 1"
    ));

    if (!$cart) {
        mysqli_query($conn, "INSERT INTO Carts(user_id, created_at) VALUES ({$user_id}, NOW())");
        $cart_id = mysqli_insert_id($conn);
    } else {
        $cart_id = $cart['cart_id'];
    }

    // Check item
    $item = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT cart_item_id, quantity FROM CartItems 
         WHERE cart_id={$cart_id} AND variant_id={$variant_id} LIMIT 1"
    ));

    if ($item) {
        $new_qty = min($item['quantity'] + $qty, $v['stock_quantity']);
        mysqli_query($conn,
            "UPDATE CartItems SET quantity={$new_qty} WHERE cart_item_id={$item['cart_item_id']}"
        );
    } else {
        mysqli_query($conn,
            "INSERT INTO CartItems(cart_id, variant_id, quantity, price_at_added, added_at)
             VALUES ({$cart_id}, {$variant_id}, {$qty}, {$v['price']}, NOW())"
        );
    }

    // FLASH MESSAGE (có tên sp + size)
    $_SESSION['flash_success'] = "Đã thêm “{$product_name} - {$product_size}” vào giỏ hàng!";
    header("Location: /fashionstore/index.php?page=product_detail&id={$product_id}");
    exit;
}



//
//  LẤY THÔNG TIN SẢN PHẨM

$sql = "SELECT p.product_id, p.product_name, p.description,
               (SELECT MIN(price) FROM ProductVariants WHERE product_id=p.product_id) AS min_price
        FROM Products p
        WHERE p.product_id={$product_id}
        LIMIT 1";
$res = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($res);
if (!$product) {
    echo "<p>Không tìm thấy sản phẩm.</p>";
    return;
}


// Hình ảnh
$imgs = [];
$resImg = mysqli_query($conn,
    "SELECT image_url, COALESCE(alt_text,'') AS alt_text 
     FROM ProductImages WHERE product_id={$product_id}"
);
while ($r = mysqli_fetch_assoc($resImg)) $imgs[] = $r;
if (empty($imgs)) $imgs[] = ['image_url'=>'uploads/no-image.jpg','alt_text'=>$product['product_name']];


// Biến thể
$variants = [];
$resVar = mysqli_query($conn,
    "SELECT variant_id, size, price, stock_quantity 
     FROM ProductVariants WHERE product_id={$product_id}"
);
while ($v = mysqli_fetch_assoc($resVar)) $variants[] = $v;

?>


<!-- FLASH MESSAGE-->
<style>
.alert {
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 15px;
    animation: fadeIn 0.35s ease;
}
.alert.success { background:#d4f9d4; color:#0a6e0f; border-left:4px solid #0a6e0f; }
.alert.error   { background:#ffe1e1; color:#b10000; border-left:4px solid #b10000; }
@keyframes fadeIn { from{opacity:0;transform:translateY(-6px);} to{opacity:1;transform:translateY(0);} }
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert success"><?= $_SESSION['flash_success']; ?></div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert error"><?= $_SESSION['flash_error']; ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>



<!-- GIAO DIỆN CHI TIẾT SẢN PHẨM -->
<section class="detail-wrap">
    <div class="detail-grid">

        <!-- HÌNH ẢNH -->
        <div class="detail-photos">
            <div class="thumbs">
                <?php foreach ($imgs as $i => $img): ?>
                    <img src="<?= htmlspecialchars($img['image_url']) ?>"
                         class="thumb <?= $i===0?'active':'' ?>"
                         data-full="<?= htmlspecialchars($img['image_url']) ?>">
                <?php endforeach; ?>
            </div>

            <div class="main-photo">
                <img id="mainImage" src="<?= htmlspecialchars($imgs[0]['image_url']) ?>" alt="">
            </div>
        </div>



        <!-- THÔNG TIN -->
        <div class="detail-info">
            <h1><?= htmlspecialchars($product['product_name']) ?></h1>

            <div class="price-main" id="displayPrice">
                <?= number_format($product['min_price'],0,",",".") ?>đ
            </div>


            <!-- FORM THÊM GIỎ -->
            <form method="post">
                <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>">

                <div class="opts">
                    <b>Kích thước:</b>
                    <div class="chips">

                        <?php foreach ($variants as $v): ?>
                            <label class="chip"
                                data-stock="<?= $v['stock_quantity'] ?>"
                                data-price="<?= $v['price'] ?>"
                                data-size="<?= htmlspecialchars($v['size']) ?>">
                                <input type="radio" name="variant_id" value="<?= $v['variant_id'] ?>" hidden>
                                <span><?= htmlspecialchars($v['size']) ?></span>
                            </label>
                        <?php endforeach; ?>

                    </div>

                    <div class="qty">
                        <label><b>Số lượng:</b>
                            <input type="number" name="quantity" value="1" min="1" class="qty-input">
                        </label>
                        <span class="stock">Còn <span class="stock-qty">0</span></span>
                    </div>
                </div>

                <input type="hidden" name="variant_size" id="variantSizeInput">

                <button type="submit" id="addToCartBtn" class="btn-primary" disabled>
                    <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
                </button>
                <a href="/fashionstore/index.php?page=cart" class="btn-outline" style="text-decoration: none">Xem giỏ hàng</a>
            </form>


            <!-- MÔ TẢ -->
            <div class="desc">
                <h3>Mô tả sản phẩm</h3>
                <div><?= nl2br(htmlspecialchars($product['description'])) ?></div>
            </div>

        </div>

    </div>
</section>



<!-- 
SCRIPT UI
-->
<script>
(function(){
    const thumbs = document.querySelectorAll('.thumb');
    const main = document.getElementById('mainImage');
    const chips = document.querySelectorAll('.chip');
    const stockText = document.querySelector('.stock-qty');
    const displayPrice = document.getElementById('displayPrice');
    const qtyInput = document.querySelector('.qty-input');
    const addBtn = document.getElementById('addToCartBtn');
    const sizeInput = document.getElementById('variantSizeInput');

    // đổi ảnh
    thumbs.forEach(t=>{
        t.addEventListener('click',()=>{
            thumbs.forEach(x=>x.classList.remove('active'));
            t.classList.add('active');
            main.src = t.dataset.full;
        });
    });

    // chọn size
    chips.forEach(ch=>{
        ch.addEventListener('click',()=>{
            chips.forEach(c=>c.classList.remove('active'));
            ch.classList.add('active');

            const radio = ch.querySelector('input[type=radio]');
            radio.checked = true;

            const stock = parseInt(ch.dataset.stock);
            const price = parseFloat(ch.dataset.price);
            const size  = ch.dataset.size;

            stockText.textContent = stock;
            displayPrice.textContent = price.toLocaleString('vi-VN') + 'đ';
            qtyInput.max = stock;
            addBtn.disabled = stock <= 0;

            sizeInput.value = size;
        });
    });

    // không vượt tồn kho
    qtyInput.addEventListener('input',()=>{
        let v = parseInt(qtyInput.value)||1;
        let max = parseInt(qtyInput.max);
        qtyInput.value = Math.min(Math.max(v,1), max);
    });

    // tự chọn biến thể đầu tiên còn hàng
    (function(){
        const first = Array.from(chips).find(c=>parseInt(c.dataset.stock)>0);
        if (first) first.click();
    })();
})();
</script>
