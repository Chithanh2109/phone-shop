<?php
// Thông tin kết nối database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ban_dien_thoai');

// Tạo kết nối bằng mysqli
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối database thất bại: " . mysqli_connect_error());
}

// Thiết lập charset là utf8mb4 để hỗ trợ tiếng Việt
mysqli_set_charset($conn, "utf8mb4");

// Hàm kiểm tra kết nối (tùy chọn, có thể giữ lại nếu muốn)
function checkConnection() {
    global $conn;
    if (mysqli_ping($conn)) {
        return true;
    }
    return false;
}
?> 