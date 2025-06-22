<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Xóa Câu hỏi'; // Tiêu đề trang (ít quan trọng vì sẽ redirect nhanh)
$current_admin = getCurrentUser();

// Lấy ID câu hỏi từ URL
$faq_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($faq_id > 0) {
    // Tiến hành xóa câu hỏi thường gặp
    $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $faq_id);
        if ($stmt->execute()) {
            // Kiểm tra số dòng bị ảnh hưởng để biết xóa thành công hay không
            if ($stmt->affected_rows > 0) {
                setMessage('success', 'Xóa câu hỏi thường gặp thành công!');
            } else {
                setMessage('warning', 'Không tìm thấy câu hỏi thường gặp để xóa.');
            }
        } else {
            setMessage('danger', 'Có lỗi xảy ra khi xóa câu hỏi: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        setMessage('danger', 'Lỗi database: Không thể chuẩn bị câu lệnh. ' . $conn->error);
    }
} else {
    // Nếu không có ID câu hỏi
    setMessage('danger', 'Không có ID câu hỏi thường gặp được chỉ định để xóa.');
}

// Chuyển hướng về trang quản lý câu hỏi thường gặp sau khi xử lý
redirect('faq_manage.php');
?> 