<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin thực hiện thao tác này

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    if ($product_id > 0) {
        $conn->begin_transaction();
        try {
            // Xóa ảnh sản phẩm liên quan
            $stmt_img = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt_img->bind_param('i', $product_id);
            $stmt_img->execute();
            $stmt_img->close();

            // Xóa các mục order_items liên quan (nếu có bảng order_items hoặc order_details)
            if ($conn->query("SHOW TABLES LIKE 'order_items'")->num_rows) {
                $stmt_oi = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
                $stmt_oi->bind_param('i', $product_id);
                $stmt_oi->execute();
                $stmt_oi->close();
            }
            if ($conn->query("SHOW TABLES LIKE 'order_details'")->num_rows) {
                $stmt_od = $conn->prepare("DELETE FROM order_details WHERE product_id = ?");
                $stmt_od->bind_param('i', $product_id);
                $stmt_od->execute();
                $stmt_od->close();
            }

            // Xóa sản phẩm chính
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param('i', $product_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    setMessage('success', 'Đã xóa sản phẩm và dữ liệu liên quan thành công.');
                } else {
                    setMessage('warning', 'Không tìm thấy sản phẩm để xóa.');
                }
            } else {
                throw new Exception('Lỗi khi xóa sản phẩm: ' . $stmt->error);
            }
            $stmt->close();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            setMessage('danger', 'Lỗi khi xóa sản phẩm: ' . $e->getMessage());
        }
    } else {
        setMessage('danger', 'ID sản phẩm không hợp lệ.');
    }
} else {
    setMessage('danger', 'Thiếu ID sản phẩm cần xóa.');
}

redirect('products.php');
?> 