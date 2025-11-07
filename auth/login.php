<?php
session_start();
include_once "../config/config.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if (empty($email) || empty($password)) {
        header("Location: ../layout/login.html");
        exit();
    }

    $sql = "SELECT user_id, full_name, email, password, role_id
            FROM Users
            WHERE email = :email LIMIT 1";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        
        error_log('DB error on login: ' . $e->getMessage());
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>\n";
        echo "<script>Swal.fire({icon:'error', title:'Lỗi máy chủ', text:'Vui lòng thử lại sau.'}).then(()=>{window.location='../login.html'});</script>";
        exit();
    }

    if ($user) {
        $dbEmail = isset($user['email']) ? strtolower(trim($user['email'])) : '';
        $dbPass  = isset($user['password']) ? trim($user['password']) : '';
        $inputEmail = strtolower($email);
        $inputPass  = $password;

        $passwordOk = false;
        if (!empty($dbPass) && password_needs_rehash($dbPass, PASSWORD_DEFAULT) === false && password_verify($inputPass, $dbPass)) {
            $passwordOk = true;
        } elseif ($inputPass === $dbPass) {
            
            $passwordOk = true;
        }

        if ($inputEmail === $dbEmail && $passwordOk) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['image_url'] = $user['image_url'] ?? 'uploads/default.png';


          
            echo "
            <html>
            <head>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Đăng nhập thành công!',
                    text: 'Chào mừng bạn, " . addslashes($user['full_name']) . "!',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href = '../index.php';
                });
            </script>
            </body>
            </html>";
            exit();
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                  <script>
                      Swal.fire({
                          icon: 'error',
                          title: 'Sai mật khẩu!',
                          text: 'Vui lòng thử lại.',
                          confirmButtonText: 'OK'
                      }).then(() => {
                          window.location = '../layout/login.html';
                      });
                  </script>";
            exit();
        }
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
              <script>
                  Swal.fire({
                      icon: 'warning',
                      title: 'Không tìm thấy tài khoản!',
                      text: 'Vui lòng kiểm tra lại email của bạn.',
                      confirmButtonText: 'OK'
                  }).then(() => {
                      window.location = '../layout/login.html';
                  });
              </script>";
        exit();
    }
} else {
    header("Location: ../layout/login.html");
    exit();
}
?>
