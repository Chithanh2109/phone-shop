<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = filter_input(INPUT_POST, 'review_id', FILTER_SANITIZE_NUMBER_INT);

    if ($review_id) {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $review_id);
            if ($stmt->execute()) {
                // Kiểm tra xem có dòng nào bị ảnh hưởng không
                if ($stmt->affected_rows > 0) {
                    setMessage('success', 'Xóa đánh giá thành công.');
                } else {
                    setMessage('warning', 'Không tìm thấy đánh giá để xóa.');
                }
            } else {
                 setMessage('danger', 'Lỗi khi xóa đánh giá: ' . $stmt->error);
            }
            $stmt->close();
        } else {
             setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
        }
    } else {
        setMessage('danger', 'Thiếu ID đánh giá để xóa.');
    }
} else {
    setMessage('danger', 'Yêu cầu không hợp lệ.');
}

redirect('reviews.php');
?> 