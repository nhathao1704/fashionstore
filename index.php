<?php
include_once "config/config.php"; 

$page = $_GET['page'] ?? '';

$allowed_pages = [
    'product' => 'user/function/product.php',
    'product_detail' => 'user/function/product_detail.php',
    'cart' => 'user/function/cart.php',
    'login' => 'user/function/login.php',
    'register' => 'user/function/register.php',
    'pay' => 'user/function/pay.php',
    'logout' => 'user/function/logout.php',
    'purchase_order' => 'user/function/purchase_order.php',
    'information' => 'user/function/information.php',
];

$layout = 'main';
$page_title = 'FashionStore - Vogue Lane Clothing';

ob_start();
?>

<main>
<?php
//   TRANG CHỦ

if ($page === '' || !array_key_exists($page, $allowed_pages)) {
?>
    <div class="promotion-image">
        <div class="image-khuyenmai">
            <img src="uploads/khuyenmai.jpg" alt="khuyenmai"/>
        </div>
    </div>

    <!--SẢN PHẨM MỚI NHẤT -->
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
                LIMIT 5";

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

                        <p class="price">'.
                            ($row['price'] ? number_format($row['price'], 0, ",", ".").'đ' : 'Liên hệ')
                        .'</p>

                        <a href="index.php?page=product_detail&id='.$row['product_id'].'" class="btn">
                            Xem chi tiết
                        </a>
                    </div>
                </div>';
            }
        } else {
            echo '<p class="no-products">Chưa có sản phẩm mới.</p>';
        }
        ?>
        </div>
    </section>

    <!-- SẢN PHẨM THỊNH HÀNH-->
   <section class="product-section">
        <h2>🔥 Sản phẩm thịnh hành</h2>

        <div class="product-grid">
        <?php
        $sql_hot = "
            SELECT 
                p.product_id,
                p.product_name,
                c.category_name,
                pi.image_url,
                SUM(od.quantity) AS total_sold,
                MIN(v.price) AS price
            FROM orderdetails od
            JOIN productvariants v ON od.variant_id = v.variant_id
            JOIN products p ON v.product_id = p.product_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN productimages pi ON p.product_id = pi.product_id
            GROUP BY p.product_id, p.product_name, c.category_name, pi.image_url
            ORDER BY total_sold DESC
            LIMIT 5
        ";

        $result_hot = mysqli_query($conn, $sql_hot);

        if ($result_hot && mysqli_num_rows($result_hot) > 0) {
            while ($row = mysqli_fetch_assoc($result_hot)) {

                $img = $row['image_url'] ?? 'uploads/no-image.jpg';
                if (!file_exists($img)) $img = 'uploads/no-image.jpg';

                echo '
                <div class="product-card">
                    <img src="'.htmlspecialchars($img).'" 
                        alt="'.htmlspecialchars($row['product_name']).'">

                    <div class="product-info">
                        <span class="category">'.htmlspecialchars($row['category_name']).'</span>

                        <h3>'.htmlspecialchars($row['product_name']).'</h3>

                        <p class="price">'.
                            number_format($row['price'], 0, ",", ".").'đ
                        </p>

                        <a href="index.php?page=product_detail&id='.$row['product_id'].'" class="btn">
                            Xem chi tiết
                        </a>
                    </div>
                </div>';
            }
        } else {
            echo "<p class='no-products'>Chưa có dữ liệu thịnh hành.</p>";
        }
        ?>
        </div>
    </section>
<?php
} else {
    include $allowed_pages[$page];
}
?>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/includes/layouts/' . $layout . '.php';
?>
