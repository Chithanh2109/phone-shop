<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập trang quản lý đơn hàng

$page_title = 'Quản lý Đơn hàng';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// Lấy danh sách đơn hàng từ database
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id > 0) {
  $sql = "SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $order_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $orders = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $total_orders = count($orders);
  $total_pages = 1;
} else {
  $result = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
  $orders = $result->fetch_all(MYSQLI_ASSOC);
}

// trạng thái thanh toán sang tiếng Việt
$payment_status_vietnamese = [
    'pending' => 'Đang chờ thanh toán',
    'paid' => 'Đã thanh toán',
    'failed' => 'Thanh toán thất bại'
];

$order_status_vietnamese = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang vận chuyển',
    'shipped' => 'Đã gửi hàng',
    'delivered' => 'Đã nhận hàng',
    'cancelled' => 'Đã hủy',
    'returned' => 'Đã trả hàng',
    'completed' => 'Hoàn thành',
];

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
                
                <div class="admin-search-bar" style="margin-bottom: 18px;">
                    <form method="get" action="orders.php" style="display:flex;gap:10px;align-items:center;">
                        <input type="number" name="order_id" min="1" placeholder="Nhập mã đơn hàng..." class="admin-form-control" style="max-width:180px;">
                        <button type="submit" class="admin-btn admin-btn-primary">Tìm kiếm</button>
                        <a href="orders.php" class="admin-btn admin-btn-secondary">Xóa lọc</a>
                    </form>
                </div>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Người dùng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td><?php echo $order['created_at']; ?></td>
                                        <td data-label="Tổng tiền"><?php echo formatPrice($order['total_price']); ?></td>
                                        <td data-label="Trạng thái">
                                            <span class="order-status status-<?php echo htmlspecialchars(trim(strtolower($order['status']))); ?>">
                                                <?php echo htmlspecialchars($order_status_vietnamese[trim(strtolower($order['status']))] ?? $order['status']); ?>
                                            </span>
                                        </td>
                                        <td class="admin-actions">
                                            <a href="order_edit.php?id=<?php echo $order['id']; ?>">Sửa</a>
                                            <a href="order_delete.php?id=<?php echo $order['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này không?');">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Không có đơn hàng nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 