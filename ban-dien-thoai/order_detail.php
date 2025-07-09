<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

// Kiểm tra order_id
if (!isset($_GET['id'])) {
    redirect('index.php');
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    redirect('index.php');
}

// Lấy chi tiết sản phẩm trong đơn hàng
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng - Bán Điện Thoại</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="cart.php">Giỏ hàng <span id="cart-count">0</span></a></li>
                    <li><a href="profile.php">Tài khoản</a></li>
                    <li><a href="logout.php">Đăng xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="order-detail">
            <h2>Chi tiết đơn hàng #<?php echo $order['id']; ?></h2>
            
            <div class="order-info">
                <h3>Thông tin đơn hàng</h3>
                <p>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                <p>Trạng thái: 
                    <span class="order-status status-<?php echo htmlspecialchars($order['status']); ?>">
                        <?php
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
                        echo htmlspecialchars($order_status_vietnamese[$order['status']] ?? $order['status']);
                        ?>
                    </span>
                </p>
                <p>Tổng tiền: <?php echo formatPrice($order['total_price']); ?></p>
            </div>
            
            <div class="customer-info">
                <h3>Thông tin khách hàng</h3>
                <p>Tên: <?php echo htmlspecialchars($order['username']); ?></p>
                <p>Email: <?php echo htmlspecialchars($order['email']); ?></p>
            </div>
            
            <div class="order-items">
                <h3>Chi tiết sản phẩm</h3>
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Số lượng: <?php echo $item['quantity']; ?></p>
                            <p>Đơn giá: <?php echo formatPrice($item['price']); ?></p>
                            <p>Thành tiền: <?php echo formatPrice($item['price'] * $item['quantity']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-actions">
                <a href="profile.php" class="btn">Quay lại</a>
                <?php if ($order['status'] === 'pending'): ?>
                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn btn-danger">Hủy đơn hàng</button>
                <?php endif; ?>
            </div>

            <?php if ($order['status'] === 'delivered'): ?>
            <div class="customer-confirm-delivery" style="margin-top: 20px;">
                <h3>Xác nhận nhận hàng</h3>
                <p>Vui lòng xác nhận nếu bạn đã nhận được đơn hàng này:</p>
                <form action="confirm_delivery.php" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xác nhận đã nhận đơn hàng này?');">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" class="btn btn-success">Tôi đã nhận được hàng</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'completed'): // Hoặc trạng thái khác bạn dùng cho đơn hàng hoàn thành ?>
            <div class="customer-feedback" style="margin-top: 20px;">
                <h3>Gửi đánh giá/Phản hồi</h3>
                <p>Hãy chia sẻ trải nghiệm của bạn về đơn hàng này:</p>
                <form action="submit_review.php" method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    
                    <!-- Phần đánh giá sao (tùy chọn, có thể thêm giao diện chọn sao ở đây) -->
                    <div class="form-group">
                        <label for="rating">Đánh giá sao:</label>
                        <!-- Thêm input hoặc giao diện chọn sao ở đây. Ví dụ: -->
                        <select name="rating" id="rating" class="form-control">
                            <option value="5">5 sao</option>
                            <option value="4">4 sao</option>
                            <option value="3">3 sao</option>
                            <option value="2">2 sao</option>
                            <option value="1">1 sao</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comment">Phản hồi của bạn:</label>
                        <textarea name="comment" id="comment" class="form-control" rows="4" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Gửi đánh giá</button>
                </form>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Bán Điện Thoại. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
    function cancelOrder(orderId) {
        if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đã hủy đơn hàng thành công!');
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra!');
                }
            });
        }
    }
    </script>
</body>
</html> 