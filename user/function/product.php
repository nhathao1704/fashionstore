<?php
require_once __DIR__ . '/../../config/config.php';

$layout = 'main';
$page_title = 'Sản phẩm - FashionStore';


// ====================== XỬ LÝ THÊM VÀO GIỎ HÀNG ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['add_cart_product'])) {

    if (empty($_SESSION['user']) && empty($_SESSION['user_id'])) {
        $returnUrl = urlencode("/fashionstore/index.php?page=product");
        header("Location: /fashionstore/index.php?page=login&return={$returnUrl}");
        exit;
    }

    $user_id = !empty($_SESSION['user'])
        ? (int)$_SESSION['user']['user_id']
        : (int)$_SESSION['user_id'];

    $product_id   = (int)$_POST['add_cart_product'];
    $variant_id   = (int)$_POST['variant_id'];
    $quantity     = 1;
    $product_name = $_POST['product_name'] ?? 'Sản phẩm';

    $var = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT price, stock_quantity FROM ProductVariants WHERE variant_id={$variant_id} LIMIT 1")
    );

    if (!$var) {
        $_SESSION['flash_error'] = "Biến thể sản phẩm không hợp lệ!";
        header("Location: /fashionstore/index.php?page=product");
        exit;
    }

    if ($var['stock_quantity'] <= 0) {
        $_SESSION['flash_error'] = "Sản phẩm đã hết hàng!";
        header("Location: /fashionstore/index.php?page=product");
        exit;
    }

    // Tạo giỏ
    $cart = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT cart_id FROM Carts WHERE user_id={$user_id} LIMIT 1"
    ));

    if (!$cart) {
        mysqli_query($conn, "INSERT INTO Carts(user_id, created_at) VALUES ({$user_id}, NOW())");
        $cart_id = mysqli_insert_id($conn);
    } else {
        $cart_id = $cart['cart_id'];
    }

    $item = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT cart_item_id, quantity FROM CartItems 
         WHERE cart_id={$cart_id} AND variant_id={$variant_id} LIMIT 1"
    ));

    if ($item) {
        $new_qty = min($item['quantity'] + 1, $var['stock_quantity']);
        mysqli_query($conn,
            "UPDATE CartItems SET quantity={$new_qty} WHERE cart_item_id={$item['cart_item_id']}"
        );
    } else {
        mysqli_query($conn,
            "INSERT INTO CartItems(cart_id, variant_id, quantity, price_at_added, added_at)
             VALUES ({$cart_id}, {$variant_id}, 1, {$var['price']}, NOW())"
        );
    }

    $_SESSION['flash_success'] = "Đã thêm “{$product_name}” vào giỏ hàng!";
    header("Location: /fashionstore/index.php?page=product");
    exit;
}



// ====================== LẤY TÊN DANH MỤC ========================
$categoryName = '';
if (!empty($_GET['cat'])) {
    $catId = (int)$_GET['cat'];
    $rsCat = mysqli_query($conn, "SELECT category_name FROM categories WHERE category_id = $catId LIMIT 1");
    $catRow = mysqli_fetch_assoc($rsCat);
    if ($catRow) $categoryName = $catRow['category_name'];
}



// ====================== FILTER SQL ========================
$where = "1";

if (!empty($_GET['cat'])) {
    $cat = (int)$_GET['cat'];
    $where .= " AND p.category_id = $cat";
}

if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND p.product_name LIKE '%$search%'";
}



// ====================== LẤY SẢN PHẨM + ĐẾM SỐ SIZE ========================
$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        (SELECT image_url FROM ProductImages WHERE product_id = p.product_id LIMIT 1) AS image_url,
        (SELECT MIN(price) FROM ProductVariants WHERE product_id = p.product_id) AS price,
        (SELECT MIN(variant_id) FROM ProductVariants WHERE product_id = p.product_id) AS variant_id,
        (SELECT COUNT(*) FROM ProductVariants WHERE product_id = p.product_id) AS variant_count
    FROM Products p
    WHERE $where
    ORDER BY p.product_id DESC
";

$result = mysqli_query($conn, $sql);



// ====================== FLASH MESSAGE ========================
?>
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



<!-- ====================== HIỂN THỊ SẢN PHẨM ======================== -->

<section class="product-section">

    <h2>
        <?php 
        if (!empty($categoryName)) echo "Danh mục: " . htmlspecialchars($categoryName);
        elseif (!empty($_GET['search'])) echo "Kết quả tìm kiếm: " . htmlspecialchars($_GET['search']);
        else echo "Tất cả sản phẩm";
        ?>
    </h2>

    <div class="product-grid">

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

            <?php
                $img          = htmlspecialchars($row['image_url'] ?: 'uploads/no-image.jpg');
                $pname        = htmlspecialchars($row['product_name']);
                $price        = $row['price'] ? number_format($row['price'], 0, ",", ".") . 'đ' : 'Liên hệ';
                $variantId    = (int)$row['variant_id'];
                $variantCount = (int)$row['variant_count'];
            ?>

            <div class="product-card">

                <img src="<?= $img ?>" alt="<?= $pname ?>">

                <h3><?= $pname ?></h3>
                <p class="price"><?= $price ?></p>

                <div class="actions">

                    <a href="/fashionstore/index.php?page=product_detail&id=<?= $row['product_id'] ?>" 
                       class="btn">Xem chi tiết</a>

                    <?php if ($variantCount >= 2): ?>
                        <!-- ⭐ CÓ 2 SIZE TRỞ LÊN → HIỆN POPUP RỒI CHUYỂN TRANG -->
                        <button type="button" class="add-to-cart"
                            onclick="alert('Bạn cần chọn size trước khi thêm vào giỏ hàng!'); 
                                     window.location.href='/fashionstore/index.php?page=product_detail&id=<?= $row['product_id'] ?>'">
                            <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                        </button>

                    <?php else: ?>
                        <!-- ⭐ CHỈ 1 SIZE → THÊM GIỎ BÌNH THƯỜNG -->
                        <form method="post">
                            <input type="hidden" name="add_cart_product" value="<?= $row['product_id'] ?>">
                            <input type="hidden" name="variant_id" value="<?= $variantId ?>">
                            <input type="hidden" name="product_name" value="<?= $pname ?>">
                            <button type="submit" class="add-to-cart">
                                <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    <?php endif; ?>

                </div>

            </div>

        <?php endwhile; ?>

    </div>
</section>
