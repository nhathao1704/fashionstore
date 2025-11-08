<?php
if (session_status() === PHP_SESSION_NONE) {
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/fashionstore/',
		'domain' => '', // set your domain in production, e.g. 'example.com'
		'secure' => false, // set true when using HTTPS
		'httponly' => true,
		'samesite' => 'Lax'
	]);
	session_start();
}

$conn = mysqli_connect("localhost", "root", "", "fashion_store");
if (!$conn) {
	die("Kết nối thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>