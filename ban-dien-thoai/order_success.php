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

<div class="order-success-bg">
  <div class="order-success-card">
    <div class="order-success-title">🎉 Đặt hàng thành công!</div>
    <?php if ($order_info): ?>
      <div class="order-success-info-row"><b>Mã đơn hàng:</b> <span>#<?php echo htmlspecialchars($order_info['id']); ?></span></div>
      <div class="order-success-info-row"><b>Ngày đặt:</b> <span><?php echo date('d/m/Y H:i', strtotime($order_info['created_at'])); ?></span></div>
      <div class="order-success-info-row"><b>Khách hàng:</b> <span><?php echo htmlspecialchars($order_info['shipping_name']); ?></span></div>
      <div class="order-success-info-row"><b>Điện thoại:</b> <span><?php echo htmlspecialchars($order_info['shipping_phone']); ?></span></div>
      <div class="order-success-info-row"><b>Địa chỉ:</b> <span><?php echo htmlspecialchars($order_info['shipping_address']); ?></span></div>
      <div class="order-success-list-title">Sản phẩm đã mua</div>
      <div class="order-success-product-list">
        <?php foreach ($order_details as $detail): 
          $stmt_product = $conn->prepare("SELECT name, image FROM products WHERE id = ? LIMIT 1");
          $stmt_product->bind_param("i", $detail['product_id']);
          $stmt_product->execute();
          $result_product = $stmt_product->get_result();
          $product = $result_product->fetch_assoc();
          $stmt_product->close();
          $stmt_image = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
          $stmt_image->bind_param("i", $detail['product_id']);
          $stmt_image->execute();
          $result_image = $stmt_image->get_result();
          $main_image_data = $result_image->fetch_assoc();
          $stmt_image->close();
          $image_to_display = $main_image_data ? $main_image_data['image'] : ($product['image'] ?? '');
          $image_url = getImageUrl($image_to_display);
        ?>
        <div class="order-success-product-item">
          <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="order-success-img">
          <div class="order-success-prod-info">
            <div class="order-success-prod-name"><?php echo htmlspecialchars($product['name']); ?></div>
            <div class="order-success-prod-row"><span>Số lượng:</span> <b><?php echo $detail['quantity']; ?></b></div>
            <div class="order-success-prod-row"><span>Đơn giá:</span> <b><?php echo formatPrice($detail['price']); ?></b></div>
            <div class="order-success-prod-row"><span>Thành tiền:</span> <b style="color:#e74c3c;font-weight:600;"><?php echo formatPrice($detail['price'] * $detail['quantity']); ?></b></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="order-success-total-row"><b>Tổng cộng:</b> <span><?php echo formatPrice($order_info['total_price']); ?></span></div>
      <div class="order-success-thank">Cảm ơn quý khách đã mua hàng!</div>
    <?php else: ?>
      <div class="order-success-info-row" style="color:#e74c3c;text-align:center;">Không tìm thấy thông tin đơn hàng hoặc đơn hàng không thuộc về bạn.</div>
    <?php endif; ?>
    <a href="index.php" class="order-success-btn">Về trang chủ</a>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?> 