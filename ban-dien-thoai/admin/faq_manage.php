<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Quản lý Câu hỏi Thường gặp';
$current_admin = getCurrentUser();

// --- Xử lý logic quản lý câu hỏi tại đây (thêm, sửa, xóa) ---
// (Sẽ triển khai sau)

// --- Lấy danh sách câu hỏi từ database ---
$faqs = []; // Khởi tạo mảng rỗng
$result = $conn->query("SELECT * FROM faqs ORDER BY created_at DESC");
if ($result) {
    $faqs = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    // Tùy chọn: Log lỗi hoặc hiển thị thông báo
    // setMessage('danger', 'Lỗi khi truy vấn câu hỏi: ' . $conn->error);
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
                <h1><?php echo $page_title; ?></h1>
                
                <div class="admin-table-container">
                    <!-- Nút Thêm mới -->
                    <a href="faq_add.php" class="admin-btn admin-btn-primary" style="margin-bottom: 20px;">Thêm Câu hỏi Mới</a>

                    <!-- Bảng hiển thị danh sách câu hỏi -->
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Câu hỏi</th>
                                <th>Trả lời</th>
                                <th>Hiển thị</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faqs)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Chưa có câu hỏi nào.</td>
                                </tr>
                            <?php else: ?>
                                <!-- Lặp qua danh sách câu hỏi và hiển thị -->
                                <?php foreach ($faqs as $faq): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($faq['id']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($faq['question'], 0, 100)) . '...'; ?></td> <!-- Hiển thị 100 ký tự đầu -->
                                        <td><?php echo htmlspecialchars(substr($faq['answer'], 0, 100)) . '...'; ?></td> <!-- Hiển thị 100 ký tự đầu -->
                                        <td><?php echo ($faq['is_active'] ?? 0) ? 'Có' : 'Không'; ?></td>
                                        <td><?php echo htmlspecialchars($faq['created_at']); ?></td>
                                        <td class="admin-actions">
                                            <a href="faq_edit.php?id=<?php echo $faq['id']; ?>" class="admin-btn admin-btn-secondary">Sửa</a>
                                            <a href="faq_delete.php?id=<?php echo $faq['id']; ?>" class="admin-btn admin-btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa câu hỏi này?');">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>   <?php // require_once 'includes/admin_footer.php'; ?>
    
</body>
</html> 