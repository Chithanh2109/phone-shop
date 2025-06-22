<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin();

// Lấy id người dùng từ URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Không cho phép xóa chính mình hoặc xóa admin
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        setMessage('danger', 'Bạn không thể tự xóa chính mình!');
        redirect('users.php');
        exit; // Dừng thực thi ngay lập tức
    }
    
    // Thực hiện xóa và kiểm tra vai trò cùng lúc
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ? AND role != "admin"');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        // Kiểm tra xem có dòng nào bị ảnh hưởng không
        if ($stmt->affected_rows > 0) {
            setMessage('success', 'Đã xóa người dùng thành công!');
        } else {
            setMessage('warning', 'Không tìm thấy người dùng để xóa hoặc bạn đang cố xóa một quản trị viên khác.');
        }
        $stmt->close();
    } else {
         setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
    }

} else {
    setMessage('danger', 'ID người dùng không hợp lệ!');
}
redirect('users.php'); 