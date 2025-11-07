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
    $user_id = (int)($_POST['user_id'] ?? 0);
    $total = (float)($_POST['total_amount'] ?? 0);
    $status = mysqli_real_escape_string($conn, trim($_POST['status'] ?? 'pending'));

    mysqli_query($conn, "INSERT INTO orders (user_id, total_amount, status, created_at)
                         VALUES ($user_id, $total, '$status', NOW())");
    header('Location: orders.php');
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $status = mysqli_real_escape_string($conn, trim($_POST['status'] ?? 'pending'));
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$id");
    header('Location: orders.php');
    exit;
}

if ($action === 'delete' && $id > 0) {
    mysqli_query($conn, "DELETE FROM orders WHERE id=$id");
    header('Location: orders.php');
    exit;
}

$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM orders WHERE id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

$rows = mysqli_query(
    $conn,
    "SELECT o.*, u.full_name 
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.user_id 
     ORDER BY o.created_at DESC"
);

$users = mysqli_query($conn, "SELECT user_id, full_name FROM users ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin - Orders</title>
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="container" style="max-width:1100px; margin:30px auto;">
        <h1 class="mb-3">Quản lý Đơn hàng</h1>

        <a class="btn btn-primary mb-3" href="orders.php?action=new">
            <i class="fas fa-plus"></i> Tạo đơn
        </a>

        <?php if ($action === 'new' || $editing): ?>
            <form method="post" action="orders.php?action=<?php echo $editing ? 'update&id=' . $editing['id'] : 'create'; ?>">
                <div class="form-group">
                    <label>Người dùng</label>
                    <select name="user_id" class="form-control" <?php echo $editing ? 'disabled' : ''; ?>>
                        <?php if ($users): while ($u = mysqli_fetch_assoc($users)): ?>
                            <option value="<?php echo $u['user_id']; ?>"
                                <?php echo ($editing && $editing['user_id'] == $u['user_id']) ? 'selected' : ''; ?>>
                                <?php echo $u['full_name']; ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tổng tiền</label>
                    <input type="number" step="0.01" name="total_amount"
                           class="form-control"
                           required
                           value="<?php echo $editing ? h($editing['total_amount']) : ''; ?>"
                           <?php echo $editing ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <?php $st = $editing ? $editing['status'] : 'pending'; ?>
                    <select name="status" class="form-control">
                        <option value="pending"   <?php echo $st === 'pending' ? 'selected' : ''; ?>>pending</option>
                        <option value="paid"      <?php echo $st === 'paid' ? 'selected' : ''; ?>>paid</option>
                        <option value="shipped"   <?php echo $st === 'shipped' ? 'selected' : ''; ?>>shipped</option>
                        <option value="cancelled" <?php echo $st === 'cancelled' ? 'selected' : ''; ?>>cancelled</option>
                    </select>
                </div>

                <button class="btn btn-success">
                    <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                </button>
                <a class="btn btn-secondary" href="orders.php">Hủy</a>
            </form>
            <hr>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Khách</th>
                    <th>Tổng</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): while ($r = mysqli_fetch_assoc($rows)): ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td><?php echo h($r['full_name']); ?></td>
                        <td><?php echo number_format($r['total_amount'], 2); ?></td>
                        <td><?php echo h($r['status']); ?></td>
                        <td><?php echo $r['created_at']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-info" href="orders.php?action=edit&id=<?php echo $r['id']; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a class="btn btn-sm btn-danger"
                               onclick="return confirm('Xóa đơn hàng này?');"
                               href="orders.php?action=delete&id=<?php echo $r['id']; ?>">
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
