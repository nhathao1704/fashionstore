<?php
session_name("admin_session");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: login-admin.php?return=' . urlencode('products.php'));
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$user_id = (int)($_SESSION['user']['user_id'] ?? 0);

/* ===============================
    CREATE PRODUCT
================================ */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name'] ?? ''));
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    if ($product_name && $category_id > 0) {
        mysqli_query($conn, "
            INSERT INTO products (category_id, product_name, description, created_at, created_by, updated_at, updated_by)
            VALUES ($category_id, '$product_name', '$description', NOW(), $user_id, NOW(), $user_id)
        ");

        // lấy ID sản phẩm vừa tạo
        $product_id = mysqli_insert_id($conn);

        // xử lý upload ảnh
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($tmp == "") continue;

                $fileName = time() . "_" . basename($_FILES['images']['name'][$i]);
                $uploadPath = "../../uploads/" . $fileName;

                if (move_uploaded_file($tmp, $uploadPath)) {
                    $url = "uploads/" . $fileName;
                    $alt = mysqli_real_escape_string($conn, $product_name);

                    mysqli_query($conn, "
                        INSERT INTO productimages (product_id, image_url, alt_text)
                        VALUES ($product_id, '$url', '$alt')
                    ");
                }
            }
        }
    }

    header('Location: products.php');
    exit;
}

/* ===============================
    UPDATE PRODUCT
================================ */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name'] ?? ''));
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    if ($product_name && $category_id > 0) {
        mysqli_query($conn, "
            UPDATE products
            SET category_id=$category_id, product_name='$product_name', description='$description',
                updated_at=NOW(), updated_by=$user_id
            WHERE product_id=$id
        ");
    }

    // Nếu có upload ảnh mới → thêm ảnh mới, không xóa ảnh cũ
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if ($tmp == "") continue;

            $fileName = time() . "_" . basename($_FILES['images']['name'][$i]);
            $uploadPath = "../../uploads/" . $fileName;

            if (move_uploaded_file($tmp, $uploadPath)) {
                $url = "uploads/" . $fileName;
                $alt = mysqli_real_escape_string($conn, $product_name);

                mysqli_query($conn, "
                    INSERT INTO productimages (product_id, image_url, alt_text)
                    VALUES ($id, '$url', '$alt')
                ");
            }
        }
    }

    header('Location: products.php');
    exit;
}

/* ===============================
    DELETE PRODUCT
================================ */
if ($action === 'delete' && $id > 0) {
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$id");
    mysqli_query($conn, "DELETE FROM productimages WHERE product_id=$id");
    header('Location: products.php');
    exit;
}

/* GET PRODUCT FOR EDIT */
$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM products WHERE product_id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

/* ===============================
    GET LIST OF PRODUCTS
================================ */
$rows = mysqli_query($conn, "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC
");

/* ===============================
    CATEGORIES
================================ */
$categories = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name");

?>
<?php include "../layout/head.php"; ?>
<?php include "../layout/sidebar.php"; ?>

<h1 class="page-title">Quản lý Sản phẩm</h1>

<div class="table-container">
    <div style="margin-bottom: 20px;">
        <a class="btn-edit" href="products.php?action=new" style="padding: 10px 20px; background: #27ae60; color: #fff; border-radius: 5px;text-decoration: none;">
            <i class="fas fa-plus"></i> Thêm sản phẩm mới
        </a>
    </div>

    <?php if ($action === 'new' || $editing): ?>
        <div class="table-container" style="margin-bottom: 30px;">
            <h2><?php echo $editing ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm mới'; ?></h2>

            <form method="post" enctype="multipart/form-data"
                  action="products.php?action=<?php echo $editing ? 'update&id=' . $editing['product_id'] : 'create'; ?>">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Danh mục</label>
                    <select name="category_id" class="form-control" style="padding: 8px; border: 1px solid #ddd;" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php if ($categories):
                            mysqli_data_seek($categories, 0);
                            while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($editing && $editing['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo h($cat['category_name']); ?>
                                </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Tên sản phẩm</label>
                    <input name="product_name" class="form-control" required
                           value="<?php echo $editing ? h($editing['product_name']) : ''; ?>"
                           style="padding: 8px; border: 1px solid #ddd;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Mô tả</label>
                    <textarea name="description" class="form-control" rows="4"
                              style="padding: 8px; border: 1px solid #ddd;"><?php echo $editing ? h($editing['description']) : ''; ?></textarea>
                </div>

                <!-- UPLOAD ẢNH -->
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Ảnh sản phẩm</label>
                    <input type="file" name="images[]" multiple
                           style="padding: 8px; border: 1px solid #ddd;">
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-edit" style="padding: 10px 20px; background: #27ae60; color: #fff; border-radius: 5px;">
                        <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                    </button>
                    <a class="btn-delete" href="products.php" style="padding: 10px 20px; background: #95a5a6; color: #fff; border-radius: 5px; margin-left: 10px;">
                        Hủy
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã SP</th>
                <th>Danh mục</th>
                <th>Tên sản phẩm</th>
                <th>Mô tả</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($rows):
            mysqli_data_seek($rows, 0);
            while ($r = mysqli_fetch_assoc($rows)): ?>
                <tr>
                    <td>#<?php echo $r['product_id']; ?></td>
                    <td><?php echo h($r['category_name'] ?? 'N/A'); ?></td>
                    <td><?php echo h($r['product_name']); ?></td>
                    <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo h($r['description']); ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>

                    <td>
                        <a class="btn-edit"
                           href="products.php?action=edit&id=<?php echo $r['product_id']; ?>"
                           style="padding: 5px 10px; background: #3498db; color: #fff; border-radius: 3px; margin-right: 5px;">
                            <i class="fas fa-edit"></i> Sửa
                        </a>

                        <a class="btn-delete"
                           onclick="return confirm('Xóa sản phẩm #<?php echo $r['product_id']; ?>?');"
                           href="products.php?action=delete&id=<?php echo $r['product_id']; ?>"
                           style="padding: 5px 10px; background: #e74c3c; color: #fff; border-radius: 3px;">
                            <i class="fas fa-trash"></i> Xóa
                        </a>
                    </td>
                </tr>
        <?php endwhile; else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Chưa có sản phẩm nào</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<?php include "../layout/footer.php"; ?>
</body>
</html>
