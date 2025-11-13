<?php
require_once __DIR__ . '/../../config/config.php';

// User information page
$layout = 'main';
$page_title = 'Thông tin cá nhân - FashionStore';

// kiem tra dang nhap
if (empty($_SESSION['user'])) {
    header('Location: /fashionstore/index.php?page=login');
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['user_id'] ?? null;

if (!$user_id) {
    echo "<p>Không tìm thấy thông tin người dùng. <a href='index.php?page=login'>Đăng nhập lại</a></p>";
    exit;
}

// truy van thong tin nguoi dung
$user_info = null;
if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM Users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_info = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$user_info) {
    echo "<p>Không tìm thấy thông tin người dùng. <a href='index.php?page=login'>Đăng nhập lại</a></p>";
    exit;
}

// Handle form submission for updating information
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');


    if (empty($full_name) || empty($email)) {
        $message = '<div class="message error">Họ tên và email là bắt buộc.</div>';
    } else {
        // Update user information
        $stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, email = ?, phone_number = ?, district = ?, city = ?, address = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ssssssi", $full_name, $email, $phone_number, $district, $city, $address, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="message">Cập nhật thông tin thành công!</div>';
            // Update session
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone_number;
            $_SESSION['user']['address'] = $address;
            $_SESSION['user']['city'] = $city;
            $_SESSION['user']['district'] = $district;
            $user_info = array_merge($user_info, $_SESSION['user']);
        } else {
            $message = '<div class="message error">Có lỗi xảy ra khi cập nhật thông tin.</div>';
        }
        mysqli_stmt_close($stmt);
    }
}

ob_start();
?>
<main style="padding:120px 20px;">
  <div class="info-container">
    <h1>Thông tin cá nhân</h1>

    <?= $message ?>

    <form method="POST" class="info-form">
      <div class="form-group">
        <label for="full_name">Họ và tên:</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_info['email'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="phone">Số điện thoại:</label>
        <input type="tel" id="phone_number" name="phone_number" value="<?= htmlspecialchars($user_info['phone_number'] ?? '') ?>">
      </div>
      <div class="form-group">
          <label for="city">Tỉnh/Thành phố:</label>
          <input id="city" name="city" value="<?= htmlspecialchars($user_info['city'] ?? '') ?>">
      </div>
      <div class="form-group">
          <label for="district">Quận/Huyện:</label>
          <input id="district" name="district" value="<?= htmlspecialchars($user_info['district'] ?? '') ?>">
      </div>
      <div class="form-group">
          <label for="address">Địa chỉ:</label>
          <input id="address" name="address" value="<?= htmlspecialchars($user_info['address'] ?? '') ?>">
      </div>


      <button type="submit" class="btn-primary1">Cập nhật thông tin</button>
    </form>
  </div>
</main>


<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
