<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin thực hiện thao tác này

// Kiểm tra xem có nhận được ID sản phẩm không
if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    if ($product_id > 0) {
        $stmt = $conn->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $product_id);
            
            if ($stmt->execute()) {
                // Kiểm tra xem có dòng nào bị ảnh hưởng không
                if ($stmt->affected_rows > 0) {
                    setMessage('success', 'Sản phẩm đã được đánh dấu là ngừng hoạt động.');
                } else {
                    setMessage('warning', 'Không tìm thấy sản phẩm để cập nhật hoặc sản phẩm đã ngừng hoạt động.');
                }
            } else {
                 setMessage('danger', 'Lỗi khi cập nhật trạng thái sản phẩm: ' . $stmt->error);
            }
            $stmt->close();
        } else {
             setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
        }
    } else {
        setMessage('danger', 'ID sản phẩm không hợp lệ.');
    }
} else {
    setMessage('danger', 'Thiếu ID sản phẩm cần xóa.');
}

// Chuyển hướng về trang quản lý sản phẩm
redirect('products.php');
?> 