<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Thêm Câu hỏi Thường gặp';
$current_admin = getCurrentUser();
$form_message = '';

// Xử lý form thêm câu hỏi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($question) || empty($answer)) {
        $form_message = '<div class="admin-alert admin-alert-danger">Vui lòng nhập đầy đủ Câu hỏi và Trả lời.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO faqs (question, answer, is_active, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('ssi', $question, $answer, $is_active);
            if ($stmt->execute()) {
                setMessage('success', 'Thêm câu hỏi thường gặp thành công!');
                redirect('faq_manage.php'); // Chuyển hướng sau khi thành công
            } else {
                $form_message = '<div class="admin-alert admin-alert-danger">Có lỗi xảy ra khi thêm câu hỏi: ' . $stmt->error . '</div>';
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
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Tạm dùng CSS chung -->
    <link rel="stylesheet" href="css/admin.css"> <!-- CSS riêng cho admin -->
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <h2>Quản trị</h2>
            <nav>
                <ul>
                    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                    <li><a href="index.php" class="sidebar-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Bảng điều khiển</a></li>
                    <li><a href="products.php" class="sidebar-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">Quản lý Sản phẩm</a></li>
                    <li><a href="orders.php" class="sidebar-link <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">Quản lý Đơn hàng</a></li>
                    <li><a href="users.php" class="sidebar-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">Quản lý Người dùng</a></li>
                    <li><a href="reviews.php" class="sidebar-link <?php echo ($current_page == 'reviews.php') ? 'active' : ''; ?>">Quản lý Đánh giá</a></li>
                    <li><a href="online_payments.php" class="sidebar-link <?php echo ($current_page == 'online_payments.php') ? 'active' : ''; ?>">Quản lý Thanh toán</a></li>
                    <li><a href="faq_manage.php" class="sidebar-link <?php echo ($current_page == 'faq_manage.php') ? 'active' : ''; ?>">Quản lý Câu hỏi Thường gặp</a></li>
                    <li><a href="../logout.php" class="sidebar-link">Đăng xuất</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Admin Main Content -->
        <div class="admin-main-content">
            
            <!-- Main Content Area -->
            <main class="admin-content">
                <?php 
                if (!empty($form_message)) {
                    echo $form_message;
                }
                ?>
                
                <h1><?php echo $page_title; ?></h1>
                
                <div class="admin-form-container">
                    <form method="POST" action="">
                        <div class="admin-form-group">
                            <label for="question">Câu hỏi:</label>
                            <input type="text" id="question" name="question" class="admin-form-control" value="<?php echo htmlspecialchars($question ?? ''); ?>" required>
                        </div>
                        <div class="admin-form-group">
                            <label for="answer">Trả lời:</label>
                            <textarea id="answer" name="answer" class="admin-form-control" rows="6" required><?php echo htmlspecialchars($answer ?? ''); ?></textarea>
                        </div>
                        <div class="admin-form-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($is_active ?? 0) ? 'checked' : ''; ?>>
                            <label for="is_active" style="display: inline-block; margin-left: 5px;">Hiển thị công khai</label>
                        </div>
                        <div class="admin-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">Thêm Câu hỏi</button>
                            <a href="faq_manage.php" class="admin-btn admin-btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>
     
    <?php // require_once 'includes/admin_footer.php'; ?>
    
</body>
</html> 