<?php
require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: index.php?page=login-admin&return=' . urlencode('/fashionstore/admin/index.php?page=products'));
    exit;
}

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$user_id = isset($_SESSION['user']['user_id']) ? (int)$_SESSION['user']['user_id'] : null;

if (!$user_id) {
    die("LỖI: Không xác định được người tạo sản phẩm. Bạn cần đăng nhập lại!");
}


/* CREATE PRODUCT  */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category_id  = (int)$_POST['category_id'];
    $description  = mysqli_real_escape_string($conn, trim($_POST['description']));

    mysqli_query($conn, "
        INSERT INTO products (category_id, product_name, description, created_at, created_by, updated_at, updated_by)
        VALUES ($category_id, '$product_name', '$description', NOW(), $user_id, NOW(), $user_id)
    ");

    $product_id = mysqli_insert_id($conn);

    /* upload ảnh */
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if (!$tmp) continue;

            $fileName = time() . "_" . basename($_FILES['images']['name'][$i]);
            $uploadPath = __DIR__ . "/../../uploads/" . $fileName;

            if (move_uploaded_file($tmp, $uploadPath)) {
                mysqli_query($conn, "
                    INSERT INTO productimages (product_id, image_url, alt_text)
                    VALUES ($product_id, 'uploads/$fileName', '$product_name')
                ");
            }
        }
    }

    header("Location: index.php?page=products");
    exit;
}

/* UPDATE PRODUCT  */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {

    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category_id  = (int)$_POST['category_id'];
    $description  = mysqli_real_escape_string($conn, trim($_POST['description']));

    mysqli_query($conn, "
        UPDATE products
        SET category_id=$category_id,
            product_name='$product_name',
            description='$description',
            updated_at=NOW(),
            updated_by=$user_id
        WHERE product_id=$id
    ");

    /* upload ảnh mới (không xóa ảnh cũ) */
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if (!$tmp) continue;

            $fileName = time() . "_" . basename($_FILES['images']['name'][$i]);
            $uploadPath = __DIR__ . "/../../uploads/" . $fileName;

            if (move_uploaded_file($tmp, $uploadPath)) {
                mysqli_query($conn, "
                    INSERT INTO productimages (product_id, image_url, alt_text)
                    VALUES ($id, 'uploads/$fileName', '$product_name')
                ");
            }
        }
    }

    header("Location: index.php?page=products");
    exit;
}

/* DELETE PRODUCT */
if ($action === 'delete' && $id > 0) {

    // 1. Xóa orderdetails (qua variant)
    mysqli_query($conn, "
        DELETE od FROM orderdetails od
        INNER JOIN productvariants pv ON od.variant_id = pv.variant_id
        WHERE pv.product_id = $id
    ");

    // 2. Xóa cartitems (qua variant)
    mysqli_query($conn, "
        DELETE ci FROM cartitems ci
        INNER JOIN carts c ON c.cart_id = ci.cart_id
        INNER JOIN productvariants pv ON ci.variant_id = pv.variant_id
        WHERE pv.product_id = $id
    ");

    // 3. Xóa productvariants
    mysqli_query($conn, "DELETE FROM productvariants WHERE product_id = $id");

    // 4. Xóa feedback của sản phẩm
    mysqli_query($conn, "DELETE FROM feedbacks WHERE product_id = $id");

    // 5. Xóa ảnh
    mysqli_query($conn, "DELETE FROM productimages WHERE product_id = $id");

    // 6. Xóa sản phẩm
    mysqli_query($conn, "DELETE FROM products WHERE product_id = $id");

    header("Location: index.php?page=products");
    exit;
}


/*EDIT PRODUCT*/
$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM products WHERE product_id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

/* LIST PRODUCTS */
$rows = mysqli_query($conn, "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC
");

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");

?>

<h1 class="page-title">Quản lý Sản phẩm</h1>

<div id="productList" class="table-container">

    <div style="margin-bottom: 20px;">
        <a href="index.php?page=products&action=new"
           style="padding:10px 20px;background:#27ae60;color:#fff;border-radius:5px;text-decoration:none;">
            + Thêm sản phẩm mới
        </a>
    </div>

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
                <td>#<?= $r['product_id'] ?></td>
                <td><?= h($r['category_name']) ?></td>
                <td><?= h($r['product_name']) ?></td>
                <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= h($r['description']) ?>
                </td>
                <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>

                <td>
                    <a href="index.php?page=products&action=edit&id=<?= $r['product_id'] ?>"
                       class="btn-edit" style="padding:6px 12px;background:#3498db;color:white;border-radius:4px;text-decoration:none;">
                        Sửa
                    </a>

                    <a href="index.php?page=products&action=delete&id=<?= $r['product_id'] ?>"
                       onclick="return confirm('Xóa sản phẩm này?')"
                       class="btn-delete" style="padding:6px 12px;background:#e74c3c;color:white;border-radius:4px;text-decoration:none;">
                        Xóa
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>

    </table>
</div>


<!-- POPUP FORM-->
<?php if ($action === 'new' || $editing): ?>
<div id="productOverlay" style="
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.4);z-index:900;"></div>

<div id="productModal" style="
    position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
    background:#fff;padding:28px;border-radius:14px;
    max-width:500px;width:90%;z-index:901;">

    <h2 style="text-align:center;margin-bottom:20px;">
        <?= $editing ? "Cập nhật sản phẩm" : "Thêm sản phẩm mới" ?>
    </h2>

    <form method="POST" enctype="multipart/form-data"
          action="index.php?page=products&action=<?= $editing ? 'update&id='.$editing['product_id'] : 'create' ?>">

        <label>Danh mục</label>
        <select name="category_id" required
                style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">
            <option value="">-- Chọn danh mục --</option>
            <?php mysqli_data_seek($categories, 0);
            while ($cat = mysqli_fetch_assoc($categories)): ?>
                <option value="<?= $cat['category_id'] ?>"
                    <?= ($editing && $editing['category_id']==$cat['category_id'])?"selected":"" ?>>
                    <?= h($cat['category_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Tên sản phẩm</label>
        <input type="text" name="product_name" required
               value="<?= $editing ? h($editing['product_name']) : '' ?>"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <label>Mô tả</label>
        <textarea name="description" rows="4"
                  style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;"><?= $editing ? h($editing['description']) : '' ?></textarea>

        <label>Ảnh sản phẩm</label>
        <input type="file" name="images[]" multiple
               style="padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:15px;">

        <button type="submit"
            style="width:100%;padding:12px;background:#27ae60;color:#fff;border:none;border-radius:8px;">
            <?= $editing ? 'Cập nhật' : 'Tạo mới' ?>
        </button>

        <a href="index.php?page=products"
           style="display:block;text-align:center;margin-top:12px;color:#666;text-decoration:none;">
           Hủy
        </a>

    </form>
</div>

<script>
// ẨN danh sách khi popup mở
document.getElementById("productList").style.display = "none";
</script>

<?php endif; ?>
