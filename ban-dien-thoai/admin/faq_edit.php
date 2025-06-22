<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Sửa Câu hỏi Thường gặp';
$current_admin = getCurrentUser();
$form_message = '';

// Lấy ID câu hỏi từ URL
$faq_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faq = null;

// Lấy dữ liệu câu hỏi hiện có từ database
if ($faq_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $faq_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $faq = $result->fetch_assoc();
        $stmt->close();

        if (!$faq) {
            setMessage('warning', 'Không tìm thấy câu hỏi thường gặp với ID này.');
            redirect('faq_manage.php');
            exit;
        }
    } else {
        setMessage('danger', 'Lỗi database khi chuẩn bị câu lệnh: ' . $conn->error);
    }
} else {
    setMessage('danger', 'Không có ID câu hỏi thường gặp được chỉ định.');
    redirect('faq_manage.php');
    exit;
}

// Xử lý form cập nhật câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $faq) {
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($question) || empty($answer)) {
        $form_message = '<div class="admin-alert admin-alert-danger">Vui lòng nhập đầy đủ Câu hỏi và Trả lời.</div>';
    } else {
        $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('ssii', $question, $answer, $is_active, $faq_id);
            if ($stmt->execute()) {
                setMessage('success', 'Cập nhật câu hỏi thường gặp thành công!');
                redirect('faq_manage.php'); // Chuyển hướng để thấy thay đổi
            } else {
                 $form_message = '<div class="admin-alert admin-alert-danger">Có lỗi xảy ra khi cập nhật câu hỏi: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $form_message = '<div class="admin-alert admin-alert-danger">Lỗi database: Không thể chuẩn bị câu lệnh. ' . $conn->error . '</div>';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="icon" href="<?php echo getSetting('site_favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css"> <!-- Tạm dùng CSS chung -->
    <link rel="stylesheet" href="css/admin.css"> <!-- CSS riêng cho admin -->
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="../index.php" class="sidebar-link">🏠 Trang chủ</a></li>
                    <li><a href="index.php" class="sidebar-link">Bảng điều khiển</a></li>
                    <li><a href="products.php" class="sidebar-link">Quản lý Sản phẩm</a></li>
                    <li><a href="orders.php" class="sidebar-link">Quản lý Đơn hàng</a></li>
                    <li><a href="users.php" class="sidebar-link">Quản lý Người dùng</a></li>
                    <li><a href="reviews.php" class="sidebar-link active">Quản lý Đánh giá</a></li>
                    <li><a href="online_payments.php" class="sidebar-link">Quản lý Thanh toán</a></li>
                    <li><a href="faq_manage.php" class="sidebar-link">Quản lý Câu hỏi Thường gặp</a></li>
                    <li><a href="../logout.php" class="sidebar-link">Đăng xuất</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Admin Main Content -->
        <div class="admin-main-content">
            <!-- Admin Header Top -->
            <header class="admin-header-top">
                <div>
                    <h3><?php echo $page_title; ?></h3>
                </div>
                <div class="user-menu">
                     <span>Xin chào, <b><?php echo htmlspecialchars($current_admin['name'] ?? ''); ?></b></span>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="admin-content">
                <?php echo showMessage(); ?>
                <?php echo $form_message; // Display form submission messages ?>
                
                <h1><?php echo $page_title; ?></h1>
                
                <?php if ($faq): // Only show form if FAQ data was fetched ?>
                    <div class="admin-form-container">
                        <form method="POST" action="">
                            <div class="admin-form-group">
                                <label for="question">Câu hỏi:</label>
                                <input type="text" id="question" name="question" class="admin-form-control" value="<?php echo htmlspecialchars($faq['question'] ?? ''); ?>" required>
                            </div>
                            <div class="admin-form-group">
                                <label for="answer">Trả lời:</label>
                                <textarea id="answer" name="answer" class="admin-form-control" rows="6" required><?php echo htmlspecialchars($faq['answer'] ?? ''); ?></textarea>
                            </div>
                            <div class="admin-form-group">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($faq['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="is_active" style="display: inline-block; margin-left: 5px;">Hiển thị công khai</label>
                            </div>
                            <div class="admin-form-actions">
                                <button type="submit" class="admin-btn admin-btn-primary">Cập nhật Câu hỏi</button>
                                <a href="faq_manage.php" class="admin-btn admin-btn-secondary">Hủy</a>
                            </div>
                        </form>
                    </div>
                <?php elseif ($faq_id > 0): // Show message if ID was provided but FAQ not found ?>
                     <p>Không tìm thấy câu hỏi thường gặp với ID đã cho.</p>
                <?php else: // Show message if no ID was provided ?>
                     <p>Vui lòng chọn một câu hỏi thường gặp để sửa.</p>
                <?php endif; ?>

            </main>
        </div>
    </div>
     
    <?php // require_once 'includes/admin_footer.php'; ?>
    
</body>
</html> 