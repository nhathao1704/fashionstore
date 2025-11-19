<?php
require_once __DIR__ . '/../../config/config.php';

// Lấy ID sản phẩm
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    echo '<p>Không tìm thấy sản phẩm.</p>';
    return;
}

// XỬ LÝ THÊM VÀO GIỎ HÀNG

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['variant_id']) && ($_POST['action'] ?? '') !== 'add_feedback') {

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

    $_SESSION['flash_success'] = "Đã thêm “{$product_name} - {$product_size}” vào giỏ hàng!";
    header("Location: /fashionstore/index.php?page=product_detail&id={$product_id}");
    exit;
}


// XỬ LÝ THÊM ĐÁNH GIÁ SẢN PHẨM

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_feedback') {

    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash_error'] = "Bạn phải đăng nhập để đánh giá!";
        header("Location: /fashionstore/index.php?page=product_detail&id={$product_id}");
        exit;
    }

    $uid = (int)$_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, trim($_POST['comment']));

    if ($rating < 1 || $rating > 5) $rating = 5;

    $sqlAddFb = "
        INSERT INTO feedbacks (user_id, product_id, rating, comment, feedback_date)
        VALUES ({$uid}, {$product_id}, {$rating}, '{$comment}', NOW())
    ";

    mysqli_query($conn, $sqlAddFb);

    $_SESSION['flash_success'] = "Cảm ơn bạn đã đánh giá sản phẩm!";
    header("Location: /fashionstore/index.php?page=product_detail&id={$product_id}");
    exit;
}



// LẤY THÔNG TIN SẢN PHẨM

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



// LẤY DANH SÁCH FEEDBACK

$feedbacks = [];
$sqlFb = "
    SELECT f.*, u.full_name
    FROM feedbacks f
    JOIN Users u ON u.user_id = f.user_id
    WHERE f.product_id = {$product_id}
    ORDER BY f.feedback_id DESC
";
$resFb = mysqli_query($conn, $sqlFb);
while ($fb = mysqli_fetch_assoc($resFb)) $feedbacks[] = $fb;

?>


<!-- FLASH MESSAGE-->
<style>
.alert { padding:12px; margin-bottom:18px; border-radius:8px; }
.alert.success { background:#d4f9d4; border-left:4px solid #0a6e0f; }
.alert.error   { background:#ffe1e1; border-left:4px solid #b10000; }

.feedback-box {
    margin-top: 35px;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.feedback-item { padding: 14px 0; border-bottom: 1px solid #eee; }
.feedback-item:last-child { border-bottom:none; }
.fb-header { display:flex; justify-content:space-between; color:#444; margin-bottom:4px; }
.feedback-form textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; }
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert success"><?= $_SESSION['flash_success'] ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert error"><?= $_SESSION['flash_error'] ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>


<!-- GIAO DIỆN CHI TIẾT -->
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

            <!-- FORM GIỎ HÀNG -->
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

                <button type="submit" class="btn-primary" id="addToCartBtn" disabled>
                    <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
                </button>
            </form>

            <!-- MÔ TẢ -->
            <div class="desc">
                <h3>Mô tả sản phẩm</h3>
                <div><?= nl2br(htmlspecialchars($product['description'])) ?></div>
            </div>


            <!-- ĐÁNH GIÁ -->
            <div class="feedback-box">
                <h3>Đánh giá sản phẩm</h3>

                <?php if (empty($feedbacks)): ?>
                    <p>Chưa có đánh giá nào.</p>
                <?php else: ?>
                    <?php foreach ($feedbacks as $fb): ?>
                        <div class="feedback-item">
                            <div class="fb-header">
                                <strong><?= htmlspecialchars($fb['full_name']) ?></strong>
                                <span><?= str_repeat("⭐", (int)$fb['rating']) ?></span>
                                <span><?= $fb['feedback_date'] ?></span>
                            </div>
                            <div><?= nl2br(htmlspecialchars($fb['comment'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>


            <!-- FORM VIẾT ĐÁNH GIÁ -->
            <?php if (!empty($_SESSION['user_id'])): ?>
                <div class="feedback-form" style="margin-top:20px">
                    <h3>Gửi đánh giá của bạn</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_feedback">

                        <label>Số sao:</label>
                        <select name="rating" required>
                            <option value="5">⭐ ⭐ ⭐ ⭐ ⭐</option>
                            <option value="4">⭐ ⭐ ⭐ ⭐</option>
                            <option value="3">⭐ ⭐ ⭐</option>
                            <option value="2">⭐ ⭐</option>
                            <option value="1">⭐</option>
                        </select>

                        <label>Bình luận:</label>
                        <textarea name="comment" rows="3" required></textarea>

                        <button class="btn-primary" type="submit">Gửi đánh giá</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="login-to-rate">
                    <a href="/fashionstore/index.php?page=login">Đăng nhập</a> để đánh giá sản phẩm.
                </p>

            <?php endif; ?>

        </div>
    </div>
</section>



<script>
(function(){
    const thumbs=document.querySelectorAll('.thumb');
    const main=document.getElementById('mainImage');
    const chips=document.querySelectorAll('.chip');
    const stockText=document.querySelector('.stock-qty');
    const displayPrice=document.getElementById('displayPrice');
    const qtyInput=document.querySelector('.qty-input');
    const addBtn=document.getElementById('addToCartBtn');
    const sizeInput=document.getElementById('variantSizeInput');

    thumbs.forEach(t=>{
        t.addEventListener('click',()=>{
            thumbs.forEach(x=>x.classList.remove('active'));
            t.classList.add('active');
            main.src=t.dataset.full;
        });
    });

    chips.forEach(ch=>{
        ch.addEventListener('click',()=>{
            chips.forEach(c=>c.classList.remove('active'));
            ch.classList.add('active');
            const radio=ch.querySelector('input[type=radio]');
            radio.checked=true;

            const stock=parseInt(ch.dataset.stock);
            const price=parseFloat(ch.dataset.price);
            const size=ch.dataset.size;

            stockText.textContent=stock;
            displayPrice.textContent=price.toLocaleString('vi-VN')+'đ';
            qtyInput.max=stock;
            addBtn.disabled=stock<=0;
            sizeInput.value=size;
        });
    });

    qtyInput.addEventListener('input',()=>{
        let v=parseInt(qtyInput.value)||1;
        let max=parseInt(qtyInput.max);
        qtyInput.value=Math.min(Math.max(v,1),max);
    });

    const first = Array.from(chips).find(c=>parseInt(c.dataset.stock)>0);
    if(first) first.click();
})();
</script>
