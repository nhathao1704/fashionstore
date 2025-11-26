<!-- ======================= TÌM KIẾM SẢN PHẨM ======================= -->
<div class="card">
    <?php
    // LẤY TỪ KHÓA NGƯỜI DÙNG NHẬP
    $keyword = $_GET['keyword'] ?? '';
    $keyword = trim($keyword);
    $keyword_sql = '%' . $keyword . '%';

    $sql = "SELECT 
                p.product_id, 
                p.product_name,
                 p.created_at,   
                MIN(v.price) AS min_price,
                COUNT(v.variant_id) AS total_variants
            FROM products p
            LEFT JOIN productvariants v 
                ON p.product_id = v.product_id
            WHERE p.product_name COLLATE utf8mb4_unicode_ci LIKE ?
               OR p.description COLLATE utf8mb4_unicode_ci LIKE ?
            GROUP BY p.product_id, p.product_name
            ORDER BY p.product_id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $keyword_sql, $keyword_sql);
    $stmt->execute();
    $products = $stmt->get_result();
    ?>

    <h2 class="section-title">Sản phẩm</h2>

    <?php if ($products->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Danh mục</th>
                <th>Tên sản phẩm</th>
                <th>Giá thấp nhất</th>
                <th>ngày tạo</th>
                <th>Hành động</th>
            </tr>
            </thead>

            <tbody>
            <?php while ($p = $products->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $p['product_id'] ?></td>

                    <!-- Lấy category -->
                    <td>
                        <?php
                        $sqlCate = "SELECT category_name 
                                    FROM categories 
                                    WHERE category_id = (
                                        SELECT category_id 
                                        FROM products 
                                        WHERE product_id = ?
                                    )";
                        $stm2 = $conn->prepare($sqlCate);
                        $stm2->bind_param("i", $p['product_id']);
                        $stm2->execute();
                        $cate = $stm2->get_result()->fetch_assoc();
                        echo $cate['category_name'] ?? '';
                        ?>
                    </td>

                    <td><?= htmlspecialchars($p['product_name']) ?></td>

                    <td>
                        <?= $p['min_price'] ? number_format($p['min_price']) . "đ" : "—" ?>
                    </td>

                    <td>
                        <?php 
                            $date = $p['created_at'] ?? '';
                            echo $date ? date('d/m/Y', strtotime($date)) : '—';
                        ?>
                    </td>

                  <td>
                        <a href="index.php?page=products&action=edit&id=<?= $p['product_id'] ?>"
                        style="padding:5px 10px;background:#3498db;color:white;border-radius:4px;
                                text-decoration:none;">
                        Sửa
                        </a>

                        <a href="index.php?page=products&action=delete&id=<?= $p['product_id'] ?>"
                        onclick="return confirm('Xóa sản phẩm?');"
                        style="padding:5px 10px;background:#e74c3c;color:white;border-radius:4px;
                                text-decoration:none;margin-left:6px;">
                        Xóa
                        </a>
                    </td>

                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Không có sản phẩm nào phù hợp.</p>
    <?php endif; ?>
</div>

<style>
.section-title {
    font-size: 22px;
    margin-bottom: 15px;
    font-weight: bold;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.admin-table thead tr {
    background: #2c3e50;
    color: #fff;
    text-align: left;
}

.admin-table th, 
.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
}

.admin-table tbody tr:hover {
    background: #f3f3f3;
}

.btn {
    padding: 6px 12px;
    border-radius: 5px;
    color: #fff;
    text-decoration: none;
}

.btn-primary {
    background: #3498db;
}

.btn-primary:hover {
    background: #2980b9;
}
</style>
