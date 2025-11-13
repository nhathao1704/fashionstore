<?php
require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: login-admin.php?return=' . urlencode('users.php'));
    exit;
}

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* === CREATE === */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $full  = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role  = (int)$_POST['role_id'];
    $pwd   = md5($_POST['password']);
    $addr  = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $status = mysqli_real_escape_string($conn, trim($_POST['employee_status']));

    mysqli_query(
        $conn,
        "INSERT INTO users (role_id, full_name, email, password, address, phone_number, employee_status, created_at)
         VALUES ($role, '$full', '$email', '$pwd', '$addr', '$phone', " . ($status ? "'$status'" : "NULL") . ", NOW())"
    );
    header('Location: users.php'); exit;
}

/* === UPDATE === */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $full  = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role  = (int)$_POST['role_id'];
    $addr  = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $status = mysqli_real_escape_string($conn, trim($_POST['employee_status']));

    mysqli_query(
        $conn,
        "UPDATE users SET 
            role_id = $role,
            full_name = '$full',
            email = '$email',
            address = '$addr',
            phone_number = '$phone',
            employee_status = " . ($status ? "'$status'" : "NULL") . "
         WHERE user_id = $id"
    );

    if (!empty($_POST['password'])) {
        $pwd = md5($_POST['password']);
        mysqli_query($conn, "UPDATE users SET password='$pwd' WHERE user_id=$id");
    }

    header('Location: users.php'); exit;
}

/* === DELETE === */
if ($action === 'delete' && $id > 0) {
    mysqli_query($conn, "DELETE FROM users WHERE user_id=$id");
    header('Location: users.php'); exit;
}

/* === EDITING === */
$editing = null;
if ($action === 'edit' && $id > 0) {
    $r = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$id");
    $editing = $r ? mysqli_fetch_assoc($r) : null;
}

/* === LIST USERS === */
$rows = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<?php include "../layout/head.php"; ?>
<?php include "../layout/sidebar.php"; ?>
        <h1 class="page-title">Quản lý Người dùng</h1>

        <div class="table-container">

            <!-- BUTTON ADD -->
            <div style="margin-bottom:20px;">
                <a class="btn-edit" href="users.php?action=new" 
                   style="display:inline-block;padding:10px 20px;background:#27ae60;color:#fff;text-decoration:none;border-radius:5px;">
                    <i class="fas fa-plus"></i> Thêm người dùng
                </a>
            </div>

            <!-- FORM CREATE / UPDATE -->
            <?php if ($action === 'new' || $editing): ?>
                <div class="table-container" style="margin-bottom:30px;">
                    <h2><?php echo $editing ? 'Cập nhật User' : 'Tạo User mới'; ?></h2>

                    <form method="post" action="users.php?action=<?php echo $editing ? 'update&id=' . $editing['user_id'] : 'create'; ?>">

                        <div class="form-group">
                            <label>Họ tên</label>
                            <input name="full_name" class="form-control" required
                                   value="<?php echo $editing ? h($editing['full_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo $editing ? h($editing['email']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Role</label>
                            <select name="role_id" class="form-control">
                                <option value="1" <?php echo ($editing && $editing['role_id']==1)?'selected':''; ?>>Admin</option>
                                <option value="2" <?php echo ($editing && $editing['role_id']==2)?'selected':''; ?>>Staff</option>
                                <option value="3" <?php echo ($editing && $editing['role_id']==3)?'selected':''; ?>>Customer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <input name="address" class="form-control"
                                   value="<?php echo $editing ? h($editing['address']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input name="phone_number" class="form-control"
                                   value="<?php echo $editing ? h($editing['phone_number']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Trạng thái nhân viên (Active / NULL)</label>
                            <input name="employee_status" class="form-control"
                                   value="<?php echo $editing ? h($editing['employee_status']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Mật khẩu <?php echo $editing ? '(để trống nếu không đổi)' : ''; ?></label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <button class="btn-edit" style="padding:10px 20px;background:#27ae60;color:#fff;border:none;border-radius:5px;">
                            <?php echo $editing ? 'Cập nhật' : 'Tạo mới'; ?>
                        </button>

                        <a class="btn-delete" href="users.php"
                           style="padding:10px 20px;background:#95a5a6;color:#fff;text-decoration:none;border-radius:5px;margin-left:10px;">
                            Hủy
                        </a>

                    </form>
                </div>
            <?php endif; ?>

            <!-- USER TABLE -->
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Địa chỉ</th>
                        <th>SĐT</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): mysqli_data_seek($rows,0); while ($r = mysqli_fetch_assoc($rows)): ?>
                        <tr>
                            <td>#<?php echo $r['user_id']; ?></td>
                            <td><?php echo h($r['full_name']); ?></td>
                            <td><?php echo h($r['email']); ?></td>
                            <td>
                                <?php 
                                echo $r['role_id']==1?'Admin':($r['role_id']==2?'Staff':'Customer'); 
                                ?>
                            </td>
                            <td><?php echo h($r['address']); ?></td>
                            <td><?php echo h($r['phone_number']); ?></td>
                            <td><?php echo h($r['employee_status'] ?? 'NULL'); ?></td>
                            <td><?php echo $r['created_at']; ?></td>

                            <td>
                                <a href="users.php?action=edit&id=<?php echo $r['user_id']; ?>" 
                                   class="btn-edit" style="padding:6px 12px;background:#3498db;color:#fff;border-radius:4px;text-decoration:none;">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>

                                <a href="users.php?action=delete&id=<?php echo $r['user_id']; ?>"
                                   onclick="return confirm('Xóa user này?')"
                                   class="btn-delete"
                                   style="padding:6px 12px;background:#e74c3c;color:#fff;border-radius:4px;text-decoration:none;">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="9" style="text-align:center;">Chưa có người dùng nào</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
<?php include "../layout/footer.php"; ?>

</body>
</html>
