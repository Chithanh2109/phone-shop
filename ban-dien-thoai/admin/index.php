<?php
// Cấu hình cho tiếng Việt
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

// Hàm dịch trạng thái đơn hàng sang tiếng Việt
function translateOrderStatus($status) {
    switch ($status) {
        case 'pending':
            return 'Đang chờ xử lý';
        case 'completed':
            return 'Đã hoàn thành';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return $status; // Giữ nguyên nếu không khớp
    }
}

// trạng thái thanh toán
$payment_status_vietnamese = [
    'pending' => 'Chưa thanh toán',
    'paid' => 'Đã thanh toán',
    'failed' => 'Thanh toán thất bại',
    'refunded' => 'Đã hoàn tiền'
];

$page_title = 'Trang Quản Trị';
// Có thể bao gồm một header riêng cho admin ở đây
// require_once 'includes/admin_header.php'; \n

// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// --- Lấy các số liệu thống kê --- //

// Tổng số sản phẩm
$result_products = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$total_products = $result_products->fetch_assoc()['total_products'] ?? 0;

// Tổng số đơn hàng
$result_orders = $conn->query("SELECT COUNT(*) AS total_orders FROM orders");
$total_orders = $result_orders->fetch_assoc()['total_orders'] ?? 0;

// Tổng số người dùng (trừ admin)
$result_users = $conn->query("SELECT COUNT(*) AS total_users FROM users WHERE role != 'admin'");
$total_users = $result_users->fetch_assoc()['total_users'] ?? 0;

// --- Lấy danh sách đơn hàng gần đây (ví dụ 10 đơn hàng mới nhất) ---
$stmt_recent_orders = $conn->prepare("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
$stmt_recent_orders->execute();
$result_recent_orders = $stmt_recent_orders->get_result();
$recent_orders = $result_recent_orders->fetch_all(MYSQLI_ASSOC);
$stmt_recent_orders->close();

// --- Lấy top sản phẩm bán chạy (ví dụ 5 sản phẩm có số lượng bán ra nhiều nhất) ---
$stmt_top_products = $conn->prepare("SELECT p.id, p.name, p.price, p.sale_price, SUM(od.quantity) as total_sold FROM order_details od JOIN products p ON od.product_id = p.id GROUP BY p.id, p.name, p.price, p.sale_price ORDER BY total_sold DESC LIMIT 5");
$stmt_top_products->execute();
$result_top_products = $stmt_top_products->get_result();
$top_products = $result_top_products->fetch_all(MYSQLI_ASSOC);
$stmt_top_products->close();

// --- Tính tổng doanh thu (từ các đơn hàng có trạng thái thanh toán 'paid') ---
$result_revenue = $conn->query("SELECT SUM(total_price) AS total_revenue FROM orders WHERE payment_status = 'paid'");
$total_revenue = $result_revenue->fetch_assoc()['total_revenue'] ?? 0;

// --- Kết thúc lấy số liệu --- //

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
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
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
            <!-- Main Content Area -->
            <main class="admin-content">
                <?php echo showMessage(); ?>
                <h1>Tổng quan</h1>
                
                <!-- Khu vực thống kê nhanh -->
                <div class="admin-stats">
                    <div class="stat-card">
                        <h3>Tổng sản phẩm</h3>
                        <p><?php echo $total_products; ?></p>
                    </div>
                     <div class="stat-card">
                        <h3>Tổng đơn hàng</h3>
                        <p><?php echo $total_orders; ?></p>
                    </div>
                     <div class="stat-card">
                        <h3>Tổng người dùng</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                    <?php /*
                     <div class="stat-card">
                        <h3>Tổng doanh thu</h3>
                        <p><?php echo formatPrice($total_revenue); ?></p>
                    </div>
                    */ ?>
                    <!-- Thêm các thống kê khác nếu cần -->
                    <div class="stat-card">
                        <h3>Tổng doanh thu (Đã hoàn thành)</h3>
                        <p><?php echo formatPrice($total_revenue); ?></p>
                    </div>
                </div>
                 
                <!-- Có thể thêm các section khác cho dashboard, ví dụ: -->
                <h2>Đơn hàng gần đây</h2>
                <div class="admin-table-container" style="margin-bottom: 30px;">
                     <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Xem chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatPrice($order['total_price']); ?></td>
                                        <td><?php echo htmlspecialchars($payment_status_vietnamese[$order['payment_status']] ?? $order['payment_status']); ?></td>
                                        <td><?php echo $order['created_at']; ?></td>
                                        <td class="admin-actions">
                                             <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="admin-btn">Xem</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Không có đơn hàng gần đây.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h2>Sản phẩm bán chạy nhất</h2>
                 <div class="admin-table-container">
                     <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên sản phẩm</th>
                                <th>Giá</th>
                                <th>Giá khuyến mãi</th>
                                <th>Tổng số lượng bán</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo formatPrice($product['price']); ?></td>
                                        <td><?php echo $product['sale_price'] ? formatPrice($product['sale_price']) : 'N/A'; ?></td>
                                        <td><?php echo $product['total_sold']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Không có dữ liệu sản phẩm bán chạy.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>
     
    <!-- Có thể bao gồm một footer riêng cho admin ở đây -->
    <?php // require_once 'includes/admin_footer.php'; ?>
    
</body>
</html>