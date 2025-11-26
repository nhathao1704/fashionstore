<?php
require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
     header('Location: index.php?page=login-admin&return=' . urlencode('/fashionstore/admin/index.php?page=products'));
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$user_id = (int)($_SESSION['user']['user_id'] ?? 0);

/* ===============================
   CREATE PRODUCT
================================*/
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category_id = (int)$_POST['category_id'];
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    mysqli_query($conn, "
        INSERT INTO products (category_id, product_name, description, created_at, created_by, updated_at, updated_by)
        VALUES ($category_id, '$product_name', '$description', NOW(), $user_id, NOW(), $user_id)
    ");

    $product_id = mysqli_insert_id($conn);

    // Upload images
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

    header('Location: index.php?page=products');
    exit;
}

/* ===============================
   UPDATE PRODUCT
================================*/
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {

    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category_id = (int)$_POST['category_id'];
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));

    mysqli_query($conn, "
        UPDATE products
        SET category_id=$category_id,
            product_name='$product_name',
            description='$description',
            updated_at=NOW(),
            updated_by=$user_id
        WHERE product_id=$id
    ");

    // Upload thêm hình mới (không xoá hình cũ)
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

     header('Location: index.php?page=products');
    exit;
}

/* ===============================
   DELETE
================================*/
if ($action === 'delete' && $id > 0) {

    mysqli_query($conn, "DELETE FROM productimages WHERE product_id=$id");
    mysqli_query($conn, "DELETE FROM products WHERE product_id=$id");

     header('Location: index.php?page=products');
    exit;
}

/* ===============================
   EDIT MODE GET DATA
================================*/
$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM products WHERE product_id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

/* ===============================
   GET LIST PRODUCTS
================================*/
$rows = mysqli_query($conn, "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC
");

$categories = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name");

?>
<h1 class="page-title">Quản lý Sản phẩm</h1>

<div class="table-container">

    <div style="margin-bottom:20px;">
        <a class="btn-edit"
           href="index.php?page=products&action=new"
           style="padding:10px 20px;background:#27ae60;color:#fff;border-radius:5px;text-decoration:none;">
            + Thêm sản phẩm mới
        </a>
    </div>

    <!-- FORM CREATE / EDIT -->
    <?php if ($action === 'new' || $editing): ?>
        <div class="table-container" style="margin-bottom:30px;">
            <h2><?php echo $editing ? 'Cập nhật sản phẩm' : 'Tạo sản phẩm mới'; ?></h2>

            <form method="post" enctype="multipart/form-data"
                  action="index.php?page=products&action=<?= $editing ? 'update&id=' . $editing['product_id'] : 'create' ?>">

                <label>Danh mục</label>
                <select name="category_id" required
                        style="width:100%;padding:10px;margin:6px 0;border:1px solid #ccc;border-radius:6px;">
                    <option value="">-- Chọn danh mục --</option>
                    <?php
                    mysqli_data_seek($categories, 0);
                    while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['category_id']; ?>"
                            <?php echo ($editing && $editing['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo h($cat['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Tên sản phẩm</label>
                <input type="text" name="product_name" required
                       style="width:100%;padding:10px;margin:6px 0;border:1px solid #ccc;border-radius:6px;"
                       value="<?php echo $editing ? h($editing['product_name']) : ''; ?>">

                <label>Mô tả</label>
                <textarea name="description" rows="4"
                          style="width:100%;padding:10px;margin:6px 0;border:1px solid #ccc;border-radius:6px;"><?php
                    echo $editing ? h($editing['description']) : ''; ?></textarea>

                <label>Ảnh sản phẩm</label>
                <input type="file" name="images[]" multiple
                       style="padding:10px;margin:6px 0;border:1px solid #ccc;border-radius:6px;">

                <button type="submit"
                        style="padding:12px 20px;background:#27ae60;color:#fff;border:none;border-radius:6px;">
                    <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                </button>

                <a href="index.php?page=products"
                   style="padding:12px 20px;background:#7f8c8d;color:#fff;border-radius:6px;text-decoration:none;margin-left:10px;">
                    Hủy
                </a>

            </form>
        </div>
    <?php endif; ?>

    <!-- TABLE LIST -->
    <table class="admin-table">
        <thead>
        <tr>
            <th>Mã SP</th>
            <th>Danh mục</th>
            <th>Tên</th>
            <th>Mô tả</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
        </tr>
        </thead>

        <tbody>
        <?php while ($r = mysqli_fetch_assoc($rows)): ?>
            <tr>
                <td>#<?php echo $r['product_id']; ?></td>
                <td><?php echo h($r['category_name']); ?></td>
                <td><?php echo h($r['product_name']); ?></td>
                <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?php echo h($r['description']); ?>
                </td>
                <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>

                <td>
                    <a class="btn-edit"
                      href="index.php?page=products&action=edit&id=<?= $r['product_id'] ?>"
                       style="padding:6px 12px;background:#3498db;color:#fff;border-radius:4px;text-decoration:none;">
                        Sửa
                    </a>

                    <a class="btn-delete"
                       href="index.php?page=products&action=delete&id=<?= $r['product_id'] ?>"
                       onclick="return confirm('Xóa sản phẩm này?')"
                       style="padding:6px 12px;background:#e74c3c;color:#fff;border-radius:4px;text-decoration:none;">
                        Xóa
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>

    </table>
</div>
</body>
</html>
