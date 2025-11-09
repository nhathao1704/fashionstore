<?php
require_once __DIR__ . '/../../config/config.php';

// User information page
$layout = 'main';
$page_title = 'Thông tin cá nhân - FashionStore';

// Check if user is logged in
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

// Fetch user information from database
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
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name) || empty($email)) {
        $message = '<div class="message error">Họ tên và email là bắt buộc.</div>';
    } else {
        // Update user information
        $stmt = mysqli_prepare($conn, "UPDATE Users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $phone, $address, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="message">Cập nhật thông tin thành công!</div>';
            // Update session
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;
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
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user_info['phone'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="address">Địa chỉ:</label>
        <textarea id="address" name="address" rows="3"><?= htmlspecialchars($user_info['address'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Ngày tạo tài khoản:</label>
        <p class="readonly"><?= htmlspecialchars($user_info['created_at'] ?? '') ?></p>
      </div>

      <button type="submit" class="btn-primary">Cập nhật thông tin</button>
    </form>
  </div>
</main>

<style>
.info-container {
  max-width: 600px;
  margin: 0 auto;
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.info-container h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #2c3e50;
}

.info-form .form-group {
  margin-bottom: 20px;
}

.info-form label {
  display: block;
  margin-bottom: 5px;
  font-weight: 600;
  color: #333;
}

.info-form input,
.info-form textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.info-form textarea {
  resize: vertical;
}

.info-form .readonly {
  padding: 10px;
  background: #f8f8f8;
  border: 1px solid #ddd;
  border-radius: 6px;
  margin: 0;
  color: #666;
}

.btn-primary {
  background: #ff7a00;
  color: #fff;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  width: 100%;
}

.btn-primary:hover {
  background: #e66d00;
}
</style>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../includes/layouts/' . $layout . '.php';
