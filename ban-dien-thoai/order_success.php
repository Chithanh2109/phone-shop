<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

$order_id = $_GET['order_id'] ?? 0;
$order_info = null;

if ($order_id > 0) {
    // Lấy thông tin đơn hàng từ database
    // Cần đảm bảo người dùng hiện tại là chủ đơn hàng này để tránh xem trái phép
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_info = $result->fetch_assoc();
        $stmt->close();

        // Lấy chi tiết đơn hàng nếu đơn hàng tồn tại và thuộc về người dùng
        if ($order_info) {
            $stmt_details = $conn->prepare("SELECT od.*, p.name, p.image FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
            $stmt_details->bind_param("i", $order_id);
            $stmt_details->execute();
            $result_details = $stmt_details->get_result();
            $order_details = $result_details->fetch_all(MYSQLI_ASSOC);
            $stmt_details->close();
        }
    }
}

$page_title = 'Đặt hàng thành công';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="text-center">
        <h2 class="text-success mb-3">✅ Đặt hàng thành công!</h2>
        <p class="lead">Cảm ơn bạn đã đặt hàng của chúng tôi.</p>

        <?php if ($order_info): ?>
            <p>Mã đơn hàng của bạn: <strong>#<?php echo htmlspecialchars($order_info['id']); ?></strong></p>
            <p>Tổng tiền: <strong><?php echo formatPrice($order_info['total_price']); ?></strong></p>
            <p>Thông tin nhận hàng sẽ được gửi đến:</p>
            <p><strong><?php echo htmlspecialchars($order_info['shipping_name']); ?></strong> - <?php echo htmlspecialchars($order_info['shipping_phone']); ?></p>
            <p><?php echo htmlspecialchars($order_info['shipping_address']); ?></p>

            <?php if (!empty($order_details)): ?>
                <h4 class="mt-4">Chi tiết đơn hàng:</h4>
                <ul class="list-group mx-auto" style="max-width: 600px;">
                    <?php foreach ($order_details as $detail): 
                        // Lấy thông tin sản phẩm chi tiết từ database (bao gồm ảnh chính)
                        $stmt_product = $conn->prepare("SELECT name, image FROM products WHERE id = ? LIMIT 1");
                        $stmt_product->bind_param("i", $detail['product_id']);
                        $stmt_product->execute();
                        $result_product = $stmt_product->get_result();
                        $product = $result_product->fetch_assoc();
                        $stmt_product->close();
                        
                        // Lấy ảnh đại diện từ bảng product_images (ưu tiên)
                        $stmt_image = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
                        $stmt_image->bind_param("i", $detail['product_id']);
                        $stmt_image->execute();
                        $result_image = $stmt_image->get_result();
                        $main_image_data = $result_image->fetch_assoc();
                        $stmt_image->close();
                        
                        // Sử dụng ảnh từ product_images nếu có, ngược lại sử dụng ảnh chính từ products
                        $image_to_display = $main_image_data ? $main_image_data['image'] : ($product['image'] ?? '');
                        $image_url = getImageUrl($image_to_display);

                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                             <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; margin-right: 15px;">
                            <div class="flex-grow-1 text-start">
                                <?php echo htmlspecialchars($product['name']); ?>
                                <small class="text-muted d-block">Số lượng: <?php echo $detail['quantity']; ?></small>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?php echo formatPrice($detail['price'] * $detail['quantity']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-danger">Không tìm thấy thông tin đơn hàng hoặc đơn hàng không thuộc về bạn.</p>
        <?php endif; ?>

        <p class="mt-4">
            <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 