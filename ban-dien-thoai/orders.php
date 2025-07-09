<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Xử lý cập nhật trạng thái đơn hàng bởi khách hàng (PHẢI đặt trước khi xuất header)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    // Lấy trạng thái hiện tại
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    if ($order) {
        $current_status = $order['status'];
        $new_status = '';
        if ($action === 'delivered' && in_array($current_status, ['pending', 'shipped'])) {
            $new_status = 'delivered';
        } elseif ($action === 'cancelled' && in_array($current_status, ['pending', 'processing'])) {
            $new_status = 'cancelled';
        }
        if ($new_status) {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->bind_param('sii', $new_status, $order_id, $user_id);
            if ($stmt->execute()) {
                // Ghi lịch sử trạng thái
                $stmt_history = $conn->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, 'user', '')");
                $stmt_history->bind_param('iss', $order_id, $current_status, $new_status);
                $stmt_history->execute();
                $stmt_history->close();
                setMessage('success', 'Cập nhật trạng thái đơn hàng thành công!');
            } else {
                setMessage('danger', 'Lỗi khi cập nhật trạng thái đơn hàng.');
            }
            $stmt->close();
            redirect('orders.php');
            exit();
        } else {
            setMessage('danger', 'Không thể cập nhật trạng thái đơn hàng này.');
        }
    } else {
        setMessage('danger', 'Không tìm thấy đơn hàng hoặc bạn không có quyền.');
    }
}

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
    'shipping' => 'Đang vận chuyển',
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
                            <td>
                                <span class="order-status status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo htmlspecialchars($order_status_vietnamese[$order['status']] ?? $order['status']); ?>
                                </span>
                                <button type="button" class="btn btn-link btn-sm" onclick="toggleHistory(<?php echo $order['id']; ?>)">Xem lịch sử</button>
                                <div id="history-<?php echo $order['id']; ?>" class="order-history-table" style="display:none;margin-top:8px;overflow-x:auto;max-width:500px;">
                                    <?php
                                    $stmt_history = $conn->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY changed_at ASC");
                                    $stmt_history->bind_param('i', $order['id']);
                                    $stmt_history->execute();
                                    $history_result = $stmt_history->get_result();
                                    $order_history = $history_result->fetch_all(MYSQLI_ASSOC);
                                    $stmt_history->close();
                                    if (!empty($order_history)) {
                                        echo '<table style="font-size:13px;width:100%;background:#f9f9f9;border-radius:6px;table-layout:auto;word-break:break-word;">';
                                        echo '<thead><tr><th>Thời gian</th><th>Trạng thái cũ</th><th>Trạng thái mới</th><th>Người cập nhật</th><th style="min-width:120px;">Ghi chú</th></tr></thead><tbody>';
                                        foreach ($order_history as $h) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($h['changed_at']) . '</td>';
                                            echo '<td>' . htmlspecialchars($order_status_vietnamese[$h['old_status']] ?? $h['old_status']) . '</td>';
                                            echo '<td>' . htmlspecialchars($order_status_vietnamese[$h['new_status']] ?? $h['new_status']) . '</td>';
                                            echo '<td>' . htmlspecialchars($h['changed_by']) . '</td>';
                                            echo '<td style="white-space:pre-line;word-break:break-word;">' . htmlspecialchars($h['note']) . '</td>';
                                            echo '</tr>';
                                        }
                                        echo '</tbody></table>';
                                    } else {
                                        echo '<div style="color:#888;font-size:13px;">Chưa có lịch sử trạng thái.</div>';
                                    }
                                    ?>
                                </div>
                                <script>
                                function toggleHistory(orderId) {
                                    var el = document.getElementById('history-' + orderId);
                                    if (el.style.display === 'none') {
                                        el.style.display = 'block';
                                    } else {
                                        el.style.display = 'none';
                                    }
                                }
                                </script>
                                <?php if (in_array($order['status'], ['pending', 'shipped'])): ?>
                                    <form method="post" style="margin-top:6px;display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="action" value="delivered">
                                        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:2px;" onclick="return confirm('Xác nhận đã nhận được hàng?');">Đã nhận hàng</button>
                                    </form>
                                <?php elseif (in_array($order['status'], ['processing'])): ?>
                                    <form method="post" style="margin-top:6px;display:inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="action" value="cancelled">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="margin-top:2px;" onclick="return confirm('Bạn chắc chắn muốn hủy đơn này?');">Hủy đơn</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'cancelled'): ?>
                                    <form action="re_order.php" method="get" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" style="margin-top:4px;">Đặt lại</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 