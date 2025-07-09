<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập trang chi tiết đơn hàng

$page_title = 'Chi tiết Đơn hàng';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// Lấy ID đơn hàng từ URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    setMessage('danger', 'Không tìm thấy ID đơn hàng.');
    redirect('orders.php');
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    setMessage('danger', 'Đơn hàng không tồn tại.');
    redirect('orders.php');
}

// Lấy các mục trong đơn hàng
$stmt_items = $conn->prepare("SELECT oi.*, p.name as product_name FROM order_details oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt_items->bind_param('i', $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$order_items = $result_items->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> #<?php echo $order['id']; ?> - <?php echo getSetting('site_name'); ?></title>
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
                <?php echo showMessage(); ?>
                
                <h1>Chi tiết Đơn hàng #<?php echo $order['id']; ?></h1>
                
                <div class="order-details">
                    <p><strong>Người đặt:</strong> <?php echo htmlspecialchars($order['user_name']); ?> (<?php echo htmlspecialchars($order['user_email']); ?>)</p>
                    <p><strong>Ngày đặt:</strong> <?php echo $order['created_at']; ?></p>
                    <p><strong>Tổng tiền:</strong> <?php echo formatPrice($order['total_price']); ?></p>
                    <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                    <p><strong>Địa chỉ nhận hàng:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                     <?php if (!empty($order['notes'])): ?>
                         <p><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                     <?php endif; ?>
                </div>

                <h2 style="margin-top: 20px;">Sản phẩm trong đơn hàng:</h2>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Giá</th>
                                <th>Tổng cộng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($order_items)): ?>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo formatPrice($item['quantity'] * $item['price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Không có sản phẩm nào trong đơn hàng này.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="orders.php" class="admin-btn">Quay lại danh sách đơn hàng</a>
                    <a href="order_edit.php?id=<?php echo $order['id']; ?>" class="admin-btn">Sửa đơn hàng</a>
                    <a href="order_delete.php?id=<?php echo $order['id']; ?>" class="admin-btn admin-btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này không?');">Xóa đơn hàng</a>
                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 