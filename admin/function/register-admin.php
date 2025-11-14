<?php
session_name("admin_session");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../../config/config.php';

function set_flash($message, $type = 'info')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($email === '' || $password === '' || $confirm === '') {
        set_flash('Vui lòng nhập đầy đủ thông tin.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('Định dạng email không hợp lệ.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    if ($password !== $confirm) {
        set_flash('Mật khẩu nhập lại không khớp.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    if (strlen($password) < 6) {
        set_flash('Mật khẩu cần ít nhất 6 ký tự.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    $stmt = mysqli_prepare($conn, 'SELECT admin_id FROM admin WHERE email = ? LIMIT 1');
    if (!$stmt) {
        set_flash('Không thể kiểm tra email.', 'danger');
        header('Location: register-admin.php');
        exit;
    }

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        set_flash('Email đã tồn tại.', 'warning');
        header('Location: register-admin.php');
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'INSERT INTO admin (email, password) VALUES (?, ?)');

    if (!$stmt) {
        set_flash('Không thể tạo tài khoản.', 'danger');
        header('Location: register-admin.php');
        exit;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $email, $hash);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        set_flash('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
        header('Location: login-admin.php');
        exit;
    }

    set_flash('Lỗi hệ thống khi đăng ký.', 'danger');
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
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdde1;
            border-radius: 6px;
            font-size: 15px;
            color: #2c3e50;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
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
            transition: background 0.25s ease;
        }
        .btn-submit:hover {
            background: #1e8449;
        }
        .alert {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
        }
        .alert-success {
            background: #d1f2eb;
            color: #145a32;
            border: 1px solid #1abc9c;
        }
        .alert-danger {
            background: #f9d6d5;
            color: #922b21;
            border: 1px solid #e74c3c;
        }
        .alert-warning {
            background: #fcf3cf;
            color: #7d6608;
            border: 1px solid #f1c40f;
        }
        .auth-links {
            margin-top: 18px;
            font-size: 14px;
            text-align: center;
        }
        .auth-links a {
            color: #3498db;
            text-decoration: none;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
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
                    <label for="email">Email quản trị</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="admin@example.com"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ít nhất 6 ký tự"
                        required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Nhập lại mật khẩu</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Nhập lại mật khẩu"
                        required>
                </div>

                <button class="btn-submit" type="submit">Đăng ký</button>
            </form>

            <div class="auth-links">
                <span>Đã có tài khoản?</span>
                <a href="login-admin.php">Đăng nhập admin</a>
            </div>
        </div>
    </div>
</body>
</html>