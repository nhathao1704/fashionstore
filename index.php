<?php
include_once "config/config.php"; // config will start session centrally

$page = $_GET['page'] ?? '';

$allowed_pages = [
    'product' => 'product/function/product.php',
    'product_detail' => 'product/function/product_detail.php',
    'cart' => 'product/function/cart.php',
    'login' => 'product/function/login.php',
    'register' => 'product/function/register.php',
    'pay' => 'product/function/pay.php',
    'logout' => 'product/function/logout.php',
    'purchase_order' => 'product/function/purchase_order.php'
];

$layout = 'main';
$page_title = 'FashionStore - Vogue Lane Clothing';

ob_start();
?>

<main>
<?php
if ($page === '' || !array_key_exists($page, $allowed_pages)) {
    // === NỘI DUNG TRANG CHỦ ===
    ?>
    <div class="promotion-image">
        <div class="image-khuyenmai">
            <img src="uploads/khuyenmai.jpg" alt="khuyenmai"/>
        </div>
    </div>

    <section class="product-section">
        <h2>Sản phẩm mới nhất</h2>
        <div class="product-grid">
        <?php
        $sql = "SELECT p.product_id, p.product_name, c.category_name,
                       pi.image_url, MIN(v.price) AS price,
                       COUNT(DISTINCT v.variant_id) as variant_count
                FROM Products p
                LEFT JOIN Categories c ON p.category_id = c.category_id
                LEFT JOIN ProductImages pi ON p.product_id = pi.product_id
                LEFT JOIN ProductVariants v ON p.product_id = v.product_id
                WHERE v.stock_quantity > 0
                GROUP BY p.product_id, p.product_name, c.category_name, pi.image_url
                ORDER BY p.created_at DESC, p.product_id DESC
                LIMIT 4";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $img = $row['image_url'] ?? 'uploads/no-image.jpg';
                if (!file_exists($img)) $img = 'uploads/no-image.jpg';
                echo '
                <div class="product-card">
                    <img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($row['product_name']).'" />
                    <div class="product-info">
                        <span class="category">'.htmlspecialchars($row['category_name']).'</span>
                        <h3>'.htmlspecialchars($row['product_name']).'</h3>
                        <p class="price">'.($row['price'] ? number_format($row['price'], 0, ",", ".").'đ' : 'Liên hệ').'</p>
                        <a href="index.php?page=product_detail&id='.$row['product_id'].'" class="btn">Xem chi tiết</a>
                    </div>
                </div>';
            }
        } else {
            echo '<p class="no-products">Chưa có sản phẩm mới.</p>';
        }
        ?>
        </div>
    </section>
    <?php
} else {
    // === CÁC TRANG KHÁC ===
    include $allowed_pages[$page];
}
?>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/includes/layouts/' . $layout . '.php';
?>
