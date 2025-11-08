<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../config.php';

// Kiểm tra quyền admin
if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: ../login.php');
    exit;
}

// === Xử lý hành động ===
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// === Tạo user mới ===
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $full  = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $usr   = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $role  = (int)($_POST['role_id'] ?? 3);
    $pwd   = md5($_POST['password'] ?? '');

    mysqli_query(
        $conn,
        "INSERT INTO users (role_id, full_name, username, email, password, created_at)
         VALUES ($role, '$full', '$usr', '$email', '$pwd', NOW())"
    );

    header('Location: users.php');
    exit;
}

// === Cập nhật user ===
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $full  = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $usr   = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $role  = (int)($_POST['role_id'] ?? 3);

    mysqli_query(
        $conn,
        "UPDATE users
         SET role_id=$role, full_name='$full', username='$usr', email='$email'
         WHERE user_id=$id"
    );

    if (!empty($_POST['password'])) {
        $pwd = md5($_POST['password']);
        mysqli_query($conn, "UPDATE users SET password='$pwd' WHERE user_id=$id");
    }

    header('Location: users.php');
    exit;
}

// === Xóa user ===
if ($action === 'delete' && $id > 0) {
    mysqli_query($conn, "DELETE FROM users WHERE user_id=$id");
    header('Location: users.php');
    exit;
}

// === Lấy dữ liệu user để chỉnh sửa ===
$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

// === Lấy danh sách tất cả users ===
$rows = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Admin - Users</title>
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="container" style="max-width:1100px; margin:30px auto;">
        <h1 class="mb-3">Quản lý Users</h1>

        <a class="btn btn-primary mb-3" href="users.php?action=new">
            <i class="fas fa-plus"></i> Thêm user
        </a>

        <?php if ($action === 'new' || $editing): ?>
            <form method="post" action="users.php?action=<?php echo $editing ? 'update&id=' . $editing['user_id'] : 'create'; ?>">
                <div class="form-group">
                    <label>Họ tên</label>
                    <input
                        name="full_name"
                        class="form-control"
                        required
                        value="<?php echo $editing ? h($editing['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input
                        name="username"
                        class="form-control"
                        required
                        value="<?php echo $editing ? h($editing['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        required
                        value="<?php echo $editing ? h($editing['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control">
                        <option value="1" <?php echo $editing && $editing['role_id'] == 1 ? 'selected' : ''; ?>>Admin</option>
                        <option value="3" <?php echo $editing && $editing['role_id'] == 3 ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Mật khẩu <?php echo $editing ? '(để trống nếu không đổi)' : ''; ?></label>
                    <input type="password" name="password" class="form-control">
                </div>

                <button class="btn btn-success">
                    <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                </button>
                <a class="btn btn-secondary" href="users.php">Hủy</a>
            </form>
            <hr>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): while ($r = mysqli_fetch_assoc($rows)): ?>
                    <tr>
                        <td><?php echo $r['user_id']; ?></td>
                        <td><?php echo h($r['full_name']); ?></td>
                        <td><?php echo h($r['username']); ?></td>
                        <td><?php echo h($r['email']); ?></td>
                        <td><?php echo $r['role_id'] == 1 ? 'Admin' : 'Customer'; ?></td>
                        <td><?php echo $r['created_at']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-info" href="users.php?action=edit&id=<?php echo $r['user_id']; ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a class="btn btn-sm btn-danger"
                               onclick="return confirm('Xóa user này?');"
                               href="users.php?action=delete&id=<?php echo $r['user_id']; ?>">
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
