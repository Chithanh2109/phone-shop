<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

require_once 'includes/header.php';

$page_title = 'Lịch sử đơn hàng';

// Lấy danh sách đơn hàng của người dùng hiện tại từ database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// trạng thái thanh toán
$payment_status_vietnamese = [
    'pending' => 'Đang chờ thanh toán',
    'paid' => 'Đã thanh toán',
    'failed' => 'Thanh toán thất bại'
];

// trạng thái đơn hàng 
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

<div class="container" style="margin: 40px auto; max-width: 1000px;">
    <h2>Lịch sử đơn hàng</h2>
    <div class="orders-history-container" style="background:#fff;padding:28px 24px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.07);">
        <?php if (empty($orders)): ?>
            <p style="text-align: center;">Bạn chưa có đơn hàng nào.</p>
        <?php else: ?>
            <!-- Hiển thị danh sách đơn hàng -->
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái thanh toán</th>
                        <th>Trạng thái đơn hàng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                            <td><?php echo formatPrice($order['total_price']); ?></td>
                            <td><?php echo htmlspecialchars($payment_status_vietnamese[$order['payment_status']] ?? $order['payment_status']); ?></td>
                            <td><?php echo htmlspecialchars($order_status_vietnamese[$order['status']] ?? $order['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 