<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Quản lý Thanh toán Online';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// --- Logic xử lý tìm kiếm --- //
$search_term = trim($_GET['search'] ?? '');
$sql = "SELECT o.*, u.name as user_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id";

$params = [];

// Thêm điều kiện tìm kiếm theo ID nếu có và là số
if (!empty($search_term) && is_numeric($search_term)) {
    $sql .= " WHERE o.id = ?";
    $params[] = (int)$search_term;
}

// Sắp xếp kết quả
$sql .= " ORDER BY o.created_at DESC";

// --- Logic lấy dữ liệu đơn hàng --- //
$orders = [];
$stmt_orders = $conn->prepare($sql);

if ($stmt_orders) {
    // Nếu có tham số (tức là có tìm kiếm), bind chúng
    if (!empty($params)) {
        // Giả sử tìm kiếm chỉ theo ID (integer)
        $stmt_orders->bind_param('i', ...$params);
    }
    
    if ($stmt_orders->execute()) {
        $result = $stmt_orders->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        setMessage('danger', 'Lỗi khi thực thi truy vấn: ' . $stmt_orders->error);
    }
    $stmt_orders->close();
} else {
    setMessage('danger', 'Lỗi khi chuẩn bị câu lệnh: ' . $conn->error);
}
// --- Kết thúc logic lấy dữ liệu --- //

// Mapping trạng thái thanh toán sang tiếng Việt (nếu cần, hoặc sử dụng map đã có)
$payment_status_vietnamese = [
    'pending' => 'Đang chờ thanh toán',
    'paid' => 'Đã thanh toán',
    'failed' => 'Thanh toán thất bại'
];

// Mapping trạng thái đơn hàng sang tiếng Việt (nếu cần, hoặc sử dụng map đã có)
$order_status_vietnamese = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipped' => 'Đã gửi hàng',
    'delivered' => 'Đã giao hàng',
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
    <link rel="stylesheet" href="../css/style.css"> <!-- Tạm dùng CSS chung cho các style cơ bản -->
    <link rel="stylesheet" href="css/admin.css"> <!-- CSS riêng cho admin -->
    <!-- Có thể cần thêm link tới thư viện icon ở đây (ví dụ: Font Awesome) -->
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="../index.php" class="sidebar-link">Trang chủ</a></li>
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
                
                <!-- Khu vực tìm kiếm và bộ lọc (có thể cần điều chỉnh cho phù hợp với dữ liệu đơn hàng) -->
                <div class="filter-section" style="margin-bottom: 20px;">
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Tìm kiếm đơn hàng..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <!-- Thêm các bộ lọc khác nếu cần (ví dụ: theo trạng thái đơn hàng, phương thức thanh toán) -->
                        <button type="submit" class="admin-btn">Tìm kiếm</button>
                    </form>
                </div>

                <!-- Bảng hiển thị danh sách đơn hàng -->
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID Đơn hàng</th>
                                <th>Người dùng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Phương thức thanh toán</th>
                                <th>Trạng thái thanh toán</th>
                                <th>Trạng thái đơn hàng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name'] ?? ''); ?></td>
                                        <td><?php echo $order['created_at']; ?></td>
                                        <td><?php echo formatPrice($order['total_price']); ?></td>
                                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($payment_status_vietnamese[$order['payment_status']] ?? $order['payment_status']); ?></td>
                                        <td>
                                            <?php if ($order['payment_status'] !== 'paid'): ?>
                                                <form action="update_payment_status.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="new_status" value="paid">
                                                    <button type="submit" class="admin-btn admin-btn-success admin-btn-sm">Đã thanh toán</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($order['payment_status'] !== 'pending'): ?>
                                                 <form action="update_payment_status.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <input type="hidden" name="new_status" value="pending">
                                                    <button type="submit" class="admin-btn admin-btn-warning admin-btn-sm">Chưa thanh toán</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($order['payment_status'] === 'paid'): ?>
                                                 <span class="text-success">Đã thanh toán</span>
                                            <?php endif; ?>
                                             <?php if ($order['payment_status'] === 'pending'): ?>
                                                 <span class="text-warning">Chưa thanh toán</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">Chưa có đơn hàng nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Khu vực phân trang sẽ ở đây -->

            </main>
        </div>
    </div>
    
</body>
</html> 