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
    $allowed_statuses = ['pending', 'processing', 'shipping', 'shipped', 'delivered', 'cancelled', 'returned']; // Các trạng thái ví dụ
    if (in_array($new_status, $allowed_statuses)) {
        $stmt_update = $conn->prepare("UPDATE orders SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt_update) {
            $stmt_update->bind_param('ssi', $new_status, $notes, $order_id);
            if ($stmt_update->execute()) {
                // Ghi lịch sử trạng thái
                $stmt_history = $conn->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, 'admin', ?)");
                $stmt_history->bind_param('isss', $order_id, $order['status'], $new_status, $notes);
                $stmt_history->execute();
                $stmt_history->close();
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
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <h2>Quản trị</h2>
            <nav>
                <ul>
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
                <h1>Sửa Đơn hàng #<?php echo $order['id']; ?></h1>
                <div class="order-details" style="margin-bottom:24px;">
                    <?php
                    // Lấy thông tin user
                    $stmt_user = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
                    $stmt_user->bind_param('i', $order['user_id']);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    $user = $result_user->fetch_assoc();
                    $stmt_user->close();
                    ?>
                    <p><strong>Người đặt:</strong> <?php echo htmlspecialchars($user['name'] ?? ''); ?> (<?php echo htmlspecialchars($user['email'] ?? ''); ?>)</p>
                    <p><strong>Ngày đặt:</strong> <?php echo $order['created_at']; ?></p>
                    <p><strong>Tổng tiền:</strong> <?php echo formatPrice($order['total_price']); ?></p>
                    <p><strong>Trạng thái thanh toán:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                    <p><strong>Địa chỉ nhận hàng:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    <?php if (!empty($order['notes'])): ?>
                        <p><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    <?php endif; ?>
                </div>
                <?php
                // Lấy các mục trong đơn hàng
                $stmt_items = $conn->prepare("SELECT oi.*, p.name as product_name FROM order_details oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmt_items->bind_param('i', $order['id']);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                $order_items = $result_items->fetch_all(MYSQLI_ASSOC);
                $stmt_items->close();
                ?>
                <h2 style="margin-top: 20px;">Sản phẩm trong đơn hàng:</h2>
                <div class="admin-table-container" style="margin-bottom:24px;">
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
                <div class="admin-form-container">
                    <form action="order_edit.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                        <div class="admin-form-group">
                            <label for="status">Trạng thái đơn hàng:</label>
                            <select id="status" name="status" class="admin-form-control" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                <option value="shipping" <?php echo $order['status'] === 'shipping' ? 'selected' : ''; ?>>Đang vận chuyển</option>
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
                            <a href="orders.php" class="admin-btn admin-btn-secondary">Quay lại danh sách</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
</body>
</html> 