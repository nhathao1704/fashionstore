<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if ($full_name === '' || $email === '' || $password === '') {
        $error = "Vui lòng điền đầy đủ họ tên, email và mật khẩu.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif ($password !== $confirm) {
        $error = "Mật khẩu và xác nhận mật khẩu không khớp.";
    } elseif (strlen($password) < 8) {
        $error = "Mật khẩu phải có ít nhất 8 ký tự.";
    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $check_sql = "SELECT user_id FROM Users WHERE email = '{$email_safe}' LIMIT 1";
        $check_res = mysqli_query($conn, $check_sql);
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $error = "Email đã được sử dụng, vui lòng chọn email khác.";
        } else {
            $full_safe = mysqli_real_escape_string($conn, $full_name);
            $pass_md5 = md5($password);
            $role_id = 3;

            $insert_sql = "INSERT INTO Users (role_id, full_name, email, password) VALUES ({$role_id}, '{$full_safe}', '{$email_safe}', '{$pass_md5}')";
            if (mysqli_query($conn, $insert_sql)) {
                $new_id = mysqli_insert_id($conn);
                $_SESSION['user'] = [
                    'user_id' => (int)$new_id,
                    'full_name' => $full_name,
                    'email' => $email,
                    'role_id' => (int)$role_id
                ];
                header('Location: index.php'); exit;
            } else {
                $error = "Lỗi khi tạo tài khoản: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - FashionStore</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
    <div class="auth-container">
        <?php if (!empty($error)): ?>
            <div class="error-message" style="margin:12px;padding:10px;border:1px solid #f00;border-radius:8px;background:#fff6f6;color:#900;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="auth-box">
            <h1>Đăng Ký</h1>
            <form id="registerForm" action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Họ và tên</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Nhập họ và tên" required value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Nhập email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                </div>

                <button type="submit" class="btn-auth">Đăng Ký</button>

                <div class="auth-links" style="margin-top:8px;">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </div>
            </form>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>
<?php 
session_start();
include_once "config.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = "Vui lòng nhập đầy đủ thông tin.";
    }
    elseif ($password !== $confirm) {
        $error = "Mật khẩu nhập lại không khớp.";
    }
    else {
        $email_safe = mysqli_real_escape_string($conn, $email);
        $full_safe  = mysqli_real_escape_string($conn, $full_name);

        $check = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '{$email_safe}' LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $error = "Email đã tồn tại.";
        }
        else {
            $pass_md5 = md5($password);
            $role_id = 3;
            $sql = "INSERT INTO users (role_id, full_name, email, password, created_at)
                    VALUES ({$role_id}, '{$full_safe}', '{$email_safe}', '{$pass_md5}', NOW())";

            if (mysqli_query($conn, $sql)) {
                header('Location: login.php'); exit;
            } else {
                $error = "Có lỗi khi tạo tài khoản.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - FashionStore</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<?php if (!empty($error)) { echo "<div class='error-message' style='margin:12px;padding:10px;border:1px solid #f00;border-radius:8px;'>".htmlspecialchars($error)."</div>"; } ?>
<body>
    
    <div class="auth-container">
        <div id="successMessage" class="success-message"></div>
        
        <div class="auth-box">
            <h1>Đăng Ký Tài Khoản</h1>
            <form id="signupForm" action="register.php" method="POST">
                <div class="form-group">
                    <label for="fullname">Họ và Tên</label>
                    <input type="text" id="fullname" placeholder="Nhập họ và tên" required name="full_name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" placeholder="Nhập email" required name="email">
                </div>
                
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" placeholder="Nhập tên đăng nhập" required name="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" placeholder="Nhập mật khẩu" required name="password">
                        
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Xác nhận mật khẩu</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" placeholder="Nhập lại mật khẩu" required name="confirm_password">
                    </div>
                </div>
                
                <button type="submit" class="btn-auth">Đăng Ký</button>
            </form>
            
            <div class="switch-form">
                Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
    <script src="js/app.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>