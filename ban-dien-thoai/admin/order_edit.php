<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập trang sửa đơn hàng

$page_title = 'Sửa Đơn hàng';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// Lấy ID đơn hàng từ URL hoặc POST
$order_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($order_id === 0) {
    setMessage('danger', 'Không tìm thấy ID đơn hàng.');
    redirect('orders.php');
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    setMessage('danger', 'Đơn hàng không tồn tại.');
    redirect('orders.php');
}

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = sanitizeInput($_POST['status']);
    $notes = sanitizeInput($_POST['notes']);

    // Kiểm tra trạng thái mới có hợp lệ không (cần đồng bộ với các trạng thái trong database)
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned']; // Các trạng thái ví dụ
    if (in_array($new_status, $allowed_statuses)) {
        $stmt_update = $conn->prepare("UPDATE orders SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param('ssi', $new_status, $notes, $order_id);
            if ($stmt_update->execute()) {
                setMessage('success', 'Cập nhật đơn hàng thành công.');
                redirect('order_detail.php?id=' . $order_id); // Chuyển hướng về trang chi tiết sau khi cập nhật
            } else {
                setMessage('danger', 'Lỗi khi cập nhật đơn hàng: ' . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
        }
    } else {
        setMessage('danger', 'Trạng thái không hợp lệ.');
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> #<?php echo $order['id']; ?> - <?php echo getSetting('site_name'); ?></title>
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
                    <li><a href="../index.php" class="sidebar-link">Trang chủ</a></li>
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
            <!-- Admin Header Top -->
            <header class="admin-header-top">
                <div>
                    <h3><?php echo $page_title; ?> #<?php echo $order['id']; ?></h3>
                </div>
                <div class="user-menu">
                     <span>Xin chào, <b><?php echo htmlspecialchars($current_admin['name'] ?? ''); ?></b></span>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="admin-content">
                <?php echo showMessage(); ?>
                
                <h1>Sửa Đơn hàng #<?php echo $order['id']; ?></h1>
                
                <div class="admin-form-container">
                    <form action="order_edit.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">

                        <div class="admin-form-group">
                            <label for="status">Trạng thái đơn hàng:</label>
                            <select id="status" name="status" class="admin-form-control" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Đã nhận hàng</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                <option value="returned" <?php echo $order['status'] === 'returned' ? 'selected' : ''; ?>>Đã trả hàng</option>
                            </select>
                        </div>

                         <div class="admin-form-group">
                            <label for="notes">Ghi chú (Admin):</label>
                            <textarea id="notes" name="notes" class="admin-form-control" rows="4"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="admin-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">Lưu thay đổi</button>
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="admin-btn admin-btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>
    
    <!-- Có thể bao gồm một footer riêng cho admin ở đây -->
    <?php // require_once \'includes/admin_footer.php\'; ?>
    
</body>
</html> 