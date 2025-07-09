<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập trang quản lý đánh giá

// Hàm dịch trạng thái đánh giá sang tiếng Việt
function translateReviewStatus($status) {
    switch ($status) {
        case 'pending':
            return 'Đang chờ duyệt';
        case 'approved':
            return 'Đã duyệt';
        case 'rejected': // Giả định có trạng thái rejected
             return 'Đã từ chối';
        default:
            return $status; // Giữ nguyên nếu không khớp
    }
}

$page_title = 'Quản lý Đánh giá';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// Lấy danh sách đánh giá từ database
$sql = "SELECT r.*, u.name as user_name, p.name as product_name 
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.id 
        JOIN products p ON r.product_id = p.id 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
$reviews = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="icon" href="<?php echo getSetting('site_favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Tạm dùng CSS chung cho các style cơ bản -->
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
                <?php echo showMessage(); ?>
                
                <h1><?php echo $page_title; ?></h1>
                
                <div class="admin-table-container">
                            <?php if (!empty($reviews)): ?>
                                <?php foreach ($reviews as $review): ?>
                            <div class="review-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                                <p><strong>ID:</strong> <?php echo $review['id']; ?></p>
                                <p><strong>Người dùng:</strong> <?php echo htmlspecialchars($review['user_name'] ?? 'Người dùng ẩn danh'); ?></p>
                                <p><strong>Sản phẩm:</strong> <?php echo htmlspecialchars($review['product_name']); ?></p>
                                <p><strong>Rating:</strong> <?php echo $review['rating']; ?>/5</p>
                                <p><strong>Bình luận:</strong> <?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                <p><strong>Trạng thái:</strong> <?php echo translateReviewStatus($review['status']); ?></p>
                                <p><strong>Ngày tạo:</strong> <?php echo $review['created_at']; ?></p>

                                <div class="admin-actions" style="margin-top: 10px;">
                                            <?php if ($review['status'] === 'pending'): ?>
                                        <form action="review_status.php" method="POST" style="display: inline-block; margin-right: 5px;">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="admin-btn admin-btn-warning">Duyệt</button>
                                        </form>
                                            <?php elseif ($review['status'] === 'approved'): ?>
                                         <form action="review_status.php" method="POST" style="display: inline-block; margin-right: 5px;">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" class="admin-btn admin-btn-secondary">Bỏ duyệt</button>
                                        </form>
                                            <?php endif; ?>
                                    <form action="review_delete.php" method="POST" style="display: inline-block;">
                                         <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                         <button type="submit" class="admin-btn admin-btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?');">Xóa</button>
                                    </form>
                                </div>
                            </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                        <p>Không có đánh giá nào.</p>
                            <?php endif; ?>
                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 