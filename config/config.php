<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$conn = mysqli_connect("localhost", "root", "", "fashion_store");
if (!$conn) {
	die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>