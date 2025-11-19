<?php
session_name("admin_session");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

/*  FLASH MESSAGE  */
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

/* XỬ LÝ SUBMIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    // VALIDATE 
    if ($full_name === '' || $email === '' || $password === '' || $confirm === '') {
        set_flash('Vui lòng nhập đầy đủ thông tin.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('Email không hợp lệ.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    if ($password !== $confirm) {
        set_flash('Mật khẩu nhập lại không khớp.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    if (strlen($password) < 6) {
        set_flash('Mật khẩu phải ít nhất 6 ký tự.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    //  KIỂM TRA EMAIL 
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        set_flash('Email đã tồn tại.', 'warning');
        header('Location: register-admin.php');
        exit;
    }
    mysqli_stmt_close($stmt);

    //  HASH PASSWORD DẠNG MD5 (đúng theo hệ thống cũ) 
    $password_md5 = md5($password);

    //  THÊM ADMIN role_id = 1 
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO users (role_id, full_name, email, password, created_at)
         VALUES (1, ?, ?, ?, NOW())"
    );

    mysqli_stmt_bind_param($stmt, 'sss', $full_name, $email, $password_md5);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        set_flash('Tạo tài khoản admin thành công! Vui lòng đăng nhập.', 'success');
        header('Location: login-admin.php');
        exit;
    }

    set_flash('Lỗi hệ thống! Không thể tạo tài khoản.', 'danger');
    header('Location: register-admin.php');
    exit;
}

$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Admin</title>
    <link rel="stylesheet" href="../../css/auth.css">
    <style>
        body {
            background: #f5f6fa;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
            padding: 32px;
        }
        .auth-card h1 {
            margin-bottom: 24px;
            text-align: center;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: #34495e;
            font-weight: 600;
        }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdde1;
            border-radius: 6px;
            font-size: 15px;
        }
        .btn-submit {
            width: 100%;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            background: #27ae60;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-submit:hover { background: #1e8449; }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Tạo tài khoản Admin</h1>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register-admin.php" novalidate>
                <div class="form-group">
                    <label for="full_name">Họ tên</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Nhập họ tên" required>
                </div>

                <div class="form-group">
                    <label for="email">Email quản trị</label>
                    <input type="email" id="email" name="email" placeholder="admin@shop.vn" required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Nhập lại mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>

                <button class="btn-submit" type="submit">Đăng ký Admin</button>
            </form>

            <div class="auth-links" style="margin-top: 18px; text-align:center;">
                <span>Đã có tài khoản?</span>
                <a href="login-admin.php">Đăng nhập admin</a>
            </div>
        </div>
    </div>
</body>
</html>
