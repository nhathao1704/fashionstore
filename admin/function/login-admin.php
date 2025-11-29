<?php
require_once __DIR__ . '/../../config/config.php';

/*  MESSAGE  */
function set_flash($message, $type = 'info')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash()
{
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
/*   RETURN   */

function sanitize_return($return)
{
    $return = trim((string)$return);

    if ($return === '') return '';
    if (stripos($return, 'http://') === 0 || stripos($return, 'https://') === 0) return '';
    if (strpos($return, '//') === 0 || strpos($return, '..') !== false) return '';
    if ($return[0] !== '/') return '';
    if (strpos($return, '/fashionstore') !== 0) return '';

    return $return;
}

function redirect_login($return = '')
{
     $url = 'index.php?page=login-admin';
    if ($return !== '') $url .= '?return=' . urlencode($return);
    header('Location: ' . $url);
    exit;
}

/*  XỬ LÝ LOGIN  */
$return_to = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $return_to = sanitize_return($_POST['return'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /* KIỂM TRA INPUT  */
    if ($email === '' || $password === '') {
        set_flash('Vui lòng nhập đầy đủ email và mật khẩu.', 'warning');
        redirect_login($return_to);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('Email không hợp lệ.', 'warning');
        redirect_login($return_to);
    }

    /*TRUY VẤN BẢNG USERS*/
    $stmt = mysqli_prepare($conn, '
        SELECT user_id, full_name, email, password, role_id 
        FROM users 
        WHERE email = ? LIMIT 1
    ');

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    /* EMAIL KHÔNG TỒN TẠI */
    if (!$user) {
        set_flash('Email hoặc mật khẩu không đúng.', 'danger');
        redirect_login($return_to);
    }

    /*  MẬT KHẨU SAI*/
 if ($user['password'] !== md5($password)) {
    set_flash('Email hoặc mật khẩu không đúng.', 'danger');
    redirect_login($return_to);
}


    /* KHÔNG PHẢI ADMIN / STAFF */
    if ((int)$user['role_id'] === 3) { // Customer
        set_flash('Bạn không có quyền truy cập trang quản trị.', 'danger');
        redirect_login('');
    }

    /* ĐĂNG NHẬP THÀNH CÔNG  */
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'user_id'   => (int)$user['user_id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role_id'   => (int)$user['role_id'],
    ];
    $_SESSION['loggedin'] = true;

    set_flash('Đăng nhập thành công!', 'success');

    /*  XỬ LÝ REDIRECT */
    $redirect_to = 'index.php?page=dashboard';

    if ($return_to !== '') {
        $redirect_to = basename($return_to);
        if (!file_exists(__DIR__ . '/' . $redirect_to)) {
           $redirect_to = 'index.php?page=dashboard';
        }
    }

    header('Location: ' . $redirect_to);
    exit;
}

/*  NẾU ĐÃ LOGIN RỒI → VÀO DASHBOARD */
if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role_id'], [1, 2])) {
    header('Location: index.php?page=dashboard');
    exit;
}

$return_to = sanitize_return($_GET['return'] ?? '');
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin</title>
    <link rel="stylesheet" href="../../css/auth.css">
    <style>
        body { background: #f5f6fa; font-family: "Segoe UI", Tahoma; }
        .auth-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .auth-card { width: 100%; max-width: 420px; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 20px; color: #2c3e50; }
        .form-group { margin-bottom: 16px; }
        label { font-size: 14px; font-weight: 600; color: #34495e; display: block; margin-bottom: 6px; }
        input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #dcdde1; }
        input:focus { border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,.2); outline: none; }
        .btn-submit { width: 100%; padding: 12px; border: none; background: #3498db; color: #fff; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #2980b9; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 14px; font-size: 14px; }
        .alert-success { background: #d1f2eb; color: #145a32; border-left: 4px solid #1abc9c; }
        .alert-danger { background: #f9d6d5; color: #922b21; border-left: 4px solid #e74c3c; }
        .alert-warning { background: #fcf3cf; color: #7d6608; border-left: 4px solid #f1c40f; }
        .auth-links { margin-top: 18px; text-align: center; font-size: 14px; }
        .auth-links a { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <h1>Quản trị FashionStore</h1>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_to); ?>">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="admin@shop.vn" required>
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>

            <button class="btn-submit" type="submit">Đăng nhập</button>
        </form>

    </div>
</div>

</body>
</html>
