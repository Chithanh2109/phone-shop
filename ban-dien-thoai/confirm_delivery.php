<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    if ($order_id) {
        // Tắt autocommit để bắt đầu transaction
        $conn->autocommit(FALSE);

        // Kiểm tra đơn hàng có thuộc về người dùng hiện tại và có trạng thái là 'delivered' không
        $stmt_check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'delivered' LIMIT 1 FOR UPDATE");
        $stmt_check->bind_param("ii", $order_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Cập nhật trạng thái đơn hàng thành 'completed'
            $stmt_update = $conn->prepare("UPDATE orders SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt_update->bind_param("i", $order_id);
            
            if ($stmt_update->execute()) {
                $conn->commit(); // Hoàn tất transaction
                setMessage('success', 'Xác nhận đơn hàng thành công!');
            } else {
                $conn->rollback(); // Hoàn tác nếu có lỗi
                setMessage('danger', 'Lỗi khi cập nhật trạng thái đơn hàng.');
            }
            $stmt_update->close();
        } else {
            $conn->rollback(); // Hoàn tác vì không tìm thấy đơn hàng
            setMessage('danger', 'Không tìm thấy đơn hàng hợp lệ để xác nhận.');
        }
        $stmt_check->close();
        // Bật lại autocommit
        $conn->autocommit(TRUE);
    } else {
        setMessage('danger', 'Thiếu ID đơn hàng để xác nhận.');
    }
} else {
    setMessage('danger', 'Yêu cầu không hợp lệ.');
}

// Chuyển hướng về trang lịch sử đơn hàng
redirect('orders.php');
?> 