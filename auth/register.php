<?php
session_start();
include_once "../config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '' || $confirm === '') {
        echo "<script>alert('Vui lòng nhập đầy đủ thông tin!'); history.back();</script>";
        exit;
    }

    if ($password !== $confirm) {
        echo "<script>alert('Mật khẩu nhập lại không khớp!'); history.back();</script>";
        exit;
    }

    $email_safe = mysqli_real_escape_string($conn, $email);
    $check = mysqli_query($conn, "SELECT user_id FROM Users WHERE email = '$email_safe'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Email đã tồn tại!'); history.back();</script>";
        exit;
    }

    $password_md5 = md5($password);

    $sql = "INSERT INTO Users (role_id, full_name, email, password, created_at)
            VALUES (3, '$full_name', '$email_safe', '$password_md5', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Đăng ký thành công! Mời bạn đăng nhập.'); window.location.href='../login.html';</script>";
        exit;
    } else {
        echo "<script>alert('Lỗi hệ thống khi đăng ký.'); history.back();</script>";
        exit;
    }
}
?>