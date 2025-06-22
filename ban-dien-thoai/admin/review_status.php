<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = filter_input(INPUT_POST, 'review_id', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    if ($review_id && in_array($status, ['approved', 'pending', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $status, $review_id);
            if ($stmt->execute()) {
                setMessage('success', 'Cập nhật trạng thái đánh giá thành công.');
            } else {
                setMessage('danger', 'Lỗi khi cập nhật trạng thái đánh giá: ' . $stmt->error);
            }
            $stmt->close();
        } else {
             setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
        }
    } else {
        setMessage('danger', 'Tham số không hợp lệ.');
    }
} else {
    setMessage('danger', 'Yêu cầu không hợp lệ.');
}

redirect('reviews.php');
?> 