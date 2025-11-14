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

function sanitize_return($return)
{
    $return = trim((string)$return);
    if ($return === '') {
        return '';
    }
    if (stripos($return, 'http://') === 0 || stripos($return, 'https://') === 0) {
        return '';
    }
    if (strpos($return, '//') === 0 || strpos($return, '..') !== false) {
        return '';
    }
    if ($return[0] !== '/') {
        return '';
    }
    if (strpos($return, '/fashionstore') !== 0) {
        return '';
    }
    return $return;
}

function redirect_login($return = '')
{
    $url = 'login-admin.php';
    if ($return !== '') {
        $url .= '?return=' . urlencode($return);
    }
    header('Location: ' . $url);
    exit;
}

$return_to = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_to = sanitize_return($_POST['return'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        set_flash('Vui lòng nhập đầy đủ email và mật khẩu.', 'warning');
        redirect_login($return_to);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('Định dạng email không hợp lệ.', 'warning');
        redirect_login($return_to);
    }

    $stmt = mysqli_prepare($conn, 'SELECT admin_id, email, password FROM admin WHERE email = ? LIMIT 1');
    if (!$stmt) {
        set_flash('Không thể chuẩn bị truy vấn đăng nhập.', 'danger');
        redirect_login($return_to);
    }

    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$admin || empty($admin['password']) || !password_verify($password, $admin['password'])) {
        set_flash('Email hoặc mật khẩu không đúng.', 'danger');
        redirect_login($return_to);
    }

    session_regenerate_id(true);
    
    // Set session data
    $_SESSION['user'] = [
        'user_id'   => (int)$admin['admin_id'],
        'full_name' => 'Administrator',
        'email'     => $admin['email'],
        'role_id'   => 1,
    ];
    $_SESSION['loggedin'] = true;
    
    // Đảm bảo session được ghi
    if (function_exists('session_write_close')) {
        // Không cần vì PHP tự động ghi khi script kết thúc
    }

    set_flash('Đăng nhập thành công!', 'success');

    // Chuyển đổi đường dẫn tuyệt đối thành tương đối nếu cần
    $redirect_to = 'dashboard.php';
    if ($return_to !== '') {
        if (strpos($return_to, '/fashionstore/admin/function/') === 0) {
            $redirect_to = basename($return_to);
        } elseif (strpos($return_to, '/') === 0) {
            $redirect_to = basename($return_to);
        } else {
            $redirect_to = $return_to;
        }
        
        // Kiểm tra file tồn tại
        if (!file_exists(__DIR__ . '/' . $redirect_to)) {
            $redirect_to = 'dashboard.php';
        }
    }

    header('Location: ' . $redirect_to);
    exit;
}

// Lấy return từ GET nếu chưa có từ POST
if ($return_to === '') {
    $return_to = sanitize_return($_GET['return'] ?? '');
}

// Kiểm tra nếu đã đăng nhập thì redirect
if (!empty($_SESSION['user']) && (int)($_SESSION['user']['role_id'] ?? 0) === 1) {
    // Chuyển đổi đường dẫn tuyệt đối thành tương đối nếu cần
    if ($return_to !== '' && strpos($return_to, '/fashionstore/admin/function/') === 0) {
        $return_to = basename($return_to);
    } elseif ($return_to !== '' && strpos($return_to, '/') === 0) {
        $return_to = basename($return_to);
    }
    
    if ($return_to !== '' && file_exists(__DIR__ . '/' . $return_to)) {
        header('Location: ' . $return_to);
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

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
            background: #3498db;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.25s ease;
        }
        .btn-submit:hover {
            background: #2980b9;
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
            <h1>Quản trị FashionStore</h1>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login-admin.php" novalidate>
                <input type="hidden" name="return" value="<?php echo htmlspecialchars($return_to); ?>">
                <div class="form-group">
                    <label for="email">Email</label>
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
                        placeholder="Nhập mật khẩu"
                        required>
                </div>

                <button class="btn-submit" type="submit">Đăng nhập</button>
            </form>

            <div class="auth-links">
                <span>Chưa có tài khoản?</span>
                <a href="register-admin.php">Đăng ký admin mới</a>
            </div>
        </div>
    </div>
</body>
</html>