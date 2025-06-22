<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

// Kiểm tra nếu yêu cầu là GET và có order_id
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $order_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    if ($order_id) {
        $conn->begin_transaction();
        try {
            // Bước 1: Xóa các chi tiết đơn hàng liên quan
            $stmt_delete_details = $conn->prepare("DELETE FROM order_details WHERE order_id = ?");
            $stmt_delete_details->bind_param('i', $order_id);
            if (!$stmt_delete_details->execute()) {
                throw new Exception("Lỗi khi xóa chi tiết đơn hàng: " . $stmt_delete_details->error);
            }
            $stmt_delete_details->close();
            
            // Bước 2: Xóa đơn hàng chính
            $stmt_delete_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $stmt_delete_order->bind_param('i', $order_id);
             if (!$stmt_delete_order->execute()) {
                throw new Exception("Lỗi khi xóa đơn hàng chính: " . $stmt_delete_order->error);
            }

            // Kiểm tra xem có dòng nào bị ảnh hưởng không
            if ($stmt_delete_order->affected_rows > 0) {
                setMessage('success', 'Xóa đơn hàng #' . $order_id . ' và các chi tiết liên quan thành công.');
            } else {
                setMessage('warning', 'Không tìm thấy đơn hàng #' . $order_id . ' để xóa.');
            }
            $stmt_delete_order->close();
            
            $conn->commit();

        } catch (Exception $e) {
            $conn->rollback();
            setMessage('danger', 'Lỗi khi xóa đơn hàng: ' . $e->getMessage());
        }
    } else {
        setMessage('danger', 'Thiếu ID đơn hàng để xóa.');
    }
} else {
    setMessage('danger', 'Yêu cầu không hợp lệ.');
}

// Chuyển hướng về trang quản lý đơn hàng
redirect('orders.php');
?> 