<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);

    // Kiểm tra tính hợp lệ cơ bản
    if ($order_id && in_array($new_status, ['pending', 'paid', 'failed'])) {
        // Cập nhật trạng thái thanh toán trong database
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $new_status, $order_id);
            if ($stmt->execute()) {
                 setMessage('success', 'Cập nhật trạng thái thanh toán đơn hàng #' . $order_id . ' thành công.');
            } else {
                 setMessage('danger', 'Lỗi khi cập nhật trạng thái thanh toán: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
        }
    } else {
        setMessage('danger', 'Tham số không hợp lệ để cập nhật trạng thái thanh toán.');
    }
} else {
    setMessage('danger', 'Yêu cầu không hợp lệ.');
}

// Chuyển hướng về trang quản lý thanh toán online
redirect('online_payments.php');
?> 