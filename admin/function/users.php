<?php

require_once __DIR__ . '/../../config/config.php';

if (empty($_SESSION['user']) || (int)$_SESSION['user']['role_id'] !== 1) {
    header('Location: login-admin.php?return=' . urlencode('index.php?page=users'));
    exit;
}

$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* 
   CREATE
*/
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $full  = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role  = (int)$_POST['role_id'];
    $pwd   = md5($_POST['password']);
    $addr  = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $status = mysqli_real_escape_string($conn, trim($_POST['employee_status']));
    
     mysqli_query($conn, "
        INSERT INTO users (role_id, full_name, email, password, address, phone_number, employee_status, created_at)
        VALUES ($role, '$full', '$email', '$pwd', '$addr', '$phone',
                " . ($status ? "'$status'" : "NULL") . ",
                NOW())
    ");

    header('Location: index.php?page=users');
    exit;
}

/* 
   UPDATE
 */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {

    $full  = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role  = (int)$_POST['role_id'];
    $addr  = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $status = mysqli_real_escape_string($conn, trim($_POST['employee_status']));

    mysqli_query($conn, "
        UPDATE users
        SET role_id = $role,
            full_name = '$full',
            email = '$email',
            address = '$addr',
            phone_number = '$phone',
            employee_status = " . ($status ? "'$status'" : "NULL") . "
        WHERE user_id = $id
    ");

    if (!empty($_POST['password'])) {
        $pwd = md5($_POST['password']);
        mysqli_query($conn, "UPDATE users SET password='$pwd' WHERE user_id=$id");
    }

    header('Location: index.php?page=users');
    exit;
}

/* 
   DELETE
 */
if ($action === 'delete' && $id > 0) {

      //  Xóa chi tiết đơn hàng (orderdetails)
    mysqli_query($conn, "
        DELETE od FROM orderdetails od
        INNER JOIN orders o ON od.order_id = o.order_id
        WHERE o.user_id = $id
    ");

    // Xóa đơn hàng
    mysqli_query($conn, "DELETE FROM orders WHERE user_id = $id");

    // Xóa cartitems (vì nó FK tới carts)
    mysqli_query($conn, "
        DELETE ci FROM cartitems ci
        INNER JOIN carts c ON ci.cart_id = c.cart_id
        WHERE c.user_id = $id
    ");

    // Xóa carts
    mysqli_query($conn, "DELETE FROM carts WHERE user_id = $id");

    //  Xóa feedback
    mysqli_query($conn, "DELETE FROM feedbacks WHERE user_id = $id");

    //  Cuối cùng xoá user
    mysqli_query($conn, "DELETE FROM users WHERE user_id = $id");
     
    header('Location: index.php?page=users');
    exit;

}

/*  EDIT MODE  */
$editing = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM users WHERE user_id=$id");
    $editing = $result ? mysqli_fetch_assoc($result) : null;
}

/* LIST USERS */
$rows = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");

?>

<h1 class="page-title">Quản lý Người dùng</h1>

<!-- ẨN danh sách nếu đang new/edit -->
<div id="userList" class="table-container" <?php if ($action !== 'list') echo 'style="display:none"'; ?>>

    <div style="margin-bottom:20px;">
        <a class="btn-edit"
           href="index.php?page=users&action=new"
           style="padding:10px 20px;background:#27ae60;color:#fff;border-radius:5px;text-decoration:none;">
            + Thêm người dùng
        </a>
    </div>

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
        <?php while ($r = mysqli_fetch_assoc($rows)): ?>
            <tr>
                <td>#<?= $r['user_id'] ?></td>
                <td><?= h($r['full_name']) ?></td>
                <td><?= h($r['email']) ?></td>

                <td><?= $r['role_id']==1?'Admin':($r['role_id']==2?'Staff':'Customer') ?></td>

                <td><?= h($r['address']) ?></td>
                <td><?= h($r['phone_number']) ?></td>
                <td><?= h($r['employee_status'] ?: 'NULL') ?></td>
                <td><?= $r['created_at'] ?></td>

                <td>
                    <a href="index.php?page=users&action=edit&id=<?= $r['user_id'] ?>"
                       class="btn-edit"
                       style="padding:6px 12px;background:#3498db;color:white;border-radius:4px;text-decoration:none;">
                        Sửa
                    </a>

                    <a href="index.php?page=users&action=delete&id=<?= $r['user_id'] ?>"
                       onclick="return confirm('Xóa user này?')"
                       class="btn-delete"
                       style="padding:6px 12px;background:#e74c3c;color:white;border-radius:4px;text-decoration:none;">
                        Xóa
                    </a>
                </td>

            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

<!--  POPUP NEW / EDIT-->
<?php if ($action === 'new' || $editing): ?>

<!-- Overlay -->
<div style="
    position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.45);z-index:900;">
</div>

<!-- Popup -->
<div style="
    position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
    background:white;padding:28px;border-radius:14px;
    width:500px;z-index:901;box-shadow:0 10px 30px rgba(0,0,0,0.25);">

    <h2 style="text-align:center;margin-bottom:20px;">
        <?= $editing ? "Cập nhật User" : "Tạo User mới" ?>
    </h2>

    <form method="POST"
          action="index.php?page=users&action=<?= $editing ? 'update&id='.$editing['user_id'] : 'create' ?>">

        <label>Họ tên</label>
        <input name="full_name" required
               value="<?= $editing ? h($editing['full_name']) : '' ?>"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <label>Email</label>
        <input name="email" type="email" required
               value="<?= $editing ? h($editing['email']) : '' ?>"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <label>Role</label>
        <select name="role_id"
                style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">
            <option value="1" <?= ($editing && $editing['role_id']==1)?'selected':'' ?>>Admin</option>
            <option value="2" <?= ($editing && $editing['role_id']==2)?'selected':'' ?>>Staff</option>
            <option value="3" <?= ($editing && $editing['role_id']==3)?'selected':'' ?>>Customer</option>
        </select>

        <label>Địa chỉ</label>
        <input name="address"
               value="<?= $editing ? h($editing['address']) : '' ?>"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <label>SĐT</label>
        <input name="phone_number"
               value="<?= $editing ? h($editing['phone_number']) : '' ?>"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <label>Trạng thái</label>
        <input name="employee_status"
               value="<?= $editing ? h($editing['employee_status']) : '' ?>"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <label>Mật khẩu <?= $editing ? "(để trống nếu không đổi)" : "" ?></label>
        <input type="password" name="password"
               style="width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-bottom:12px;">

        <button type="submit"
                style="width:100%;padding:12px;background:#27ae60;color:white;border:none;border-radius:8px;font-weight:bold;">
            <?= $editing ? "Cập nhật" : "Tạo mới" ?>
        </button>

        <a href="index.php?page=users"
           style="display:block;text-align:center;margin-top:14px;text-decoration:none;color:#666;">
           Hủy
        </a>

    </form>
</div>

<?php endif; ?>
