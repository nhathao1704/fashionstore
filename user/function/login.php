<?php
require_once __DIR__ . '/../../config/config.php';

// Thiết lập thông tin trang
$layout = 'auth';
$page_title = 'Đăng Nhập - FashionStore';
$extra_css = ['css/auth.css'];

// Lấy return URL nếu có (GET/POST)
$return_url = '';
if (!empty($_REQUEST['return'])) {
    $return_url = trim($_REQUEST['return']);
}

function is_safe_return($r) {
    if (!$r) return false;
    // Không cho phép chứa protocol/host để tránh open redirect
    if (stripos($r, 'http://') !== false || stripos($r, 'https://') !== false) return false;
    if (strpos($r, '//') !== false) return false;
    // Cho phép đường dẫn nội bộ: bắt đầu bằng /fashionstore hoặc bắt đầu bằng index.php
    if (strpos($r, '/fashionstore') === 0 || strpos($r, '/index.php') === 0 || strpos($r, 'index.php') === 0) return true;
    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = "Vui lòng nhập email và mật khẩu.";
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $pass_md5   = md5($password);
        $sql = "SELECT user_id, full_name, email, role_id FROM users WHERE email = '{$email_safe}' AND password = '{$pass_md5}' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            // Regenerate session id to prevent session fixation
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            // Lưu session theo dạng mảng (hiện tại code dùng) và đồng thời set các khóa phổ thông
            $_SESSION['user'] = ['user_id'=>(int)$user['user_id'], 'full_name'=>$user['full_name'], 'email'=>$user['email'], 'role_id'=>(int)$user['role_id']];
            // Thiết lập thêm để các đoạn mã khác (ví dụ auth/login.php) cũng nhận diện được
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['loggedin'] = true;
            // Redirect về return_url nếu nó an toàn, ngược lại về trang chủ
            if (!empty($return_url) && is_safe_return($return_url)) {
                header('Location: ' . $return_url);
            } else {
                header('Location: /fashionstore/index.php');
            }
            exit;
        } else {
            $error = "Email hoặc mật khẩu không đúng.";
        }
    }
}
?>
<?php
// Bắt đầu output buffering
ob_start();
?>

<div class="auth-container">
    <div id="successMessage" class="success-message"></div>
    <?php if (!empty(
            $error
        )): ?>
            <div class="error-message" style="margin:12px;padding:10px;border:1px solid #f00;border-radius:8px;background:#fff6f6;color:#900;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - FashionStore</title>
    <link rel="stylesheet" href="/fashionstore/css/style.css">
    <link rel="stylesheet" href="/fashionstore/css/auth.css">
</head>
<body>    
        <div class="auth-box">
            <h1>Đăng Nhập</h1>
                            <form id="loginForm" action="/fashionstore/index.php?page=login" method="POST">
                                <?php if (!empty($return_url) && is_safe_return($return_url)): ?>
                                        <input type="hidden" name="return" value="<?= htmlspecialchars($return_url) ?>">
                                <?php endif; ?>
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="email" placeholder="Nhập tên đăng nhập" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Nhập mật khẩu" required>
                        
                    </div>
                </div>
                
                <button type="submit" class="btn-auth">Đăng Nhập</button>
                
                <div class="auth-links">
                    <a href="#" onclick="forgotPassword()">Quên mật khẩu?</a>
                </div>
            </form>
            
                <div class="switch-form">
                Chưa có tài khoản? <a href="/fashionstore/index.php?page=register">Đăng ký ngay</a>
            </div>
        </div>
    </div>
</body>
</html>