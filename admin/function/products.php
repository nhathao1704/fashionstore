<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    mysqli_query($conn, "
        INSERT INTO products (name, price, stock, created_at)
        VALUES ('$name', $price, $stock, NOW())
    ");

    header('Location: products.php');
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    mysqli_query($conn, "
        UPDATE products
        SET name='$name', price=$price, stock=$stock
        WHERE id=$id
    ");

    header('Location: products.php');
    exit;
}

if ($action === 'delete' && $id > 0) {
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    header('Location: products.php');
    exit;
}

$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM products WHERE id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}
$rows = mysqli_query($conn, "SELECT * FROM products ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin - Products</title>
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="container" style="max-width:1100px; margin:30px auto;">
        <h1 class="mb-3">Quản lý Sản phẩm</h1>

        <a class="btn btn-primary mb-3" href="products.php?action=new">
            <i class="fas fa-plus"></i> Thêm sản phẩm
        </a>

        <?php if ($action === 'new' || $editing): ?>
            <form method="post" action="products.php?action=<?php echo $editing ? 'update&id=' . $editing['id'] : 'create'; ?>">
                <div class="form-group">
                    <label>Tên sản phẩm</label>
                    <input
                        name="name"
                        class="form-control"
                        required
                        value="<?php echo $editing ? h($editing['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Giá</label>
                    <input
                        type="number"
                        step="0.01"
                        name="price"
                        class="form-control"
                        required
                        value="<?php echo $editing ? h($editing['price']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Tồn kho</label>
                    <input
                        type="number"
                        name="stock"
                        class="form-control"
                        required
                        value="<?php echo $editing ? h($editing['stock']) : ''; ?>">
                </div>

                <button class="btn btn-success">
                    <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                </button>
                <a class="btn btn-secondary" href="products.php">Hủy</a>
            </form>
            <hr>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Giá</th>
                    <th>Tồn kho</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): while ($r = mysqli_fetch_assoc($rows)): ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td><?php echo h($r['name']); ?></td>
                        <td><?php echo number_format($r['price'], 2); ?></td>
                        <td><?php echo $r['stock']; ?></td>
                        <td><?php echo $r['created_at']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-info" href="products.php?action=edit&id=<?php echo $r['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a class="btn btn-sm btn-danger"
                               onclick="return confirm('Xóa sản phẩm này?');"
                               href="products.php?action=delete&id=<?php echo $r['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
