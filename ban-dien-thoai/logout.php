<?php
require_once 'includes/functions.php'; // Bao gồm file functions
session_start(); // Bắt đầu session để có thể hủy

// Hủy tất cả các biến session
$_SESSION = array();

// Nếu muốn hủy session hoàn toàn, xóa cả cookie session.
// Lưu ý: Điều này sẽ phá hủy session, không chỉ dữ liệu session!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session
session_destroy();

// Hiển thị thông báo đăng xuất thành công
setMessage('success', 'Đăng xuất thành công!');

// Chuyển hướng về trang chủ hoặc trang đăng nhập
header('Location: index.php'); // Hoặc 'login.php'
exit();
?> 