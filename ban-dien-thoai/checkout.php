<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    setMessage('warning', 'Vui lòng đăng nhập để thanh toán');
    redirect('login.php');
    exit();
}

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý cập nhật số lượng sản phẩm
    if (isset($_POST['update_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            update_cart_quantity($product_id, $quantity);
            setMessage('success', 'Đã cập nhật giỏ hàng');
        } else {
            remove_from_cart($product_id);
            setMessage('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        }
        redirect('checkout.php');
        exit();
    }

    // Xử lý xóa sản phẩm
    if (isset($_POST['remove_item'])) {
        $product_id = (int)$_POST['product_id'];
        remove_from_cart($product_id);
        setMessage('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        redirect('checkout.php');
        exit();
    }

    // Xử lý đặt hàng
    if (isset($_POST['place_order'])) {
        // Lấy thông tin giỏ hàng
        $cart_items = get_cart_items();
        
        // Kiểm tra giỏ hàng
        if (empty($cart_items)) {
            setMessage('danger', 'Giỏ hàng trống, không thể đặt hàng');
            redirect('cart.php');
            exit();
        }

        // Lấy và validate thông tin đặt hàng
        $shipping_name = sanitizeInput($_POST['shipping_name'] ?? '');
        $shipping_phone = sanitizeInput($_POST['shipping_phone'] ?? '');
        $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');

        // Validate thông tin
        if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || empty($payment_method)) {
            setMessage('danger', 'Vui lòng điền đầy đủ thông tin nhận hàng');
            redirect('checkout.php');
            exit();
        }

        // Validate phương thức thanh toán
        if (!in_array($payment_method, ['cod', 'banking'])) {
            setMessage('danger', 'Phương thức thanh toán không hợp lệ');
            redirect('checkout.php');
            exit();
        }

        // Bắt đầu transaction
        $conn->begin_transaction();
        $success = false;

        try {
            // Lấy thông tin người dùng và tính tổng tiền
            $user_id = $_SESSION['user_id'];
            $total_amount = get_cart_total();

            // 1. Tạo đơn hàng mới
            $sql_order = "INSERT INTO orders (
                user_id, 
                total_price, 
                shipping_name,
                shipping_phone,
                shipping_address,
                payment_method,
                payment_status,
                order_status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())";

            $stmt_order = $conn->prepare($sql_order);
            if (!$stmt_order) {
                throw new Exception("Lỗi khi chuẩn bị câu lệnh Order: " . $conn->error);
            }
            $stmt_order->bind_param("isssss", $user_id, $total_amount, $shipping_name, $shipping_phone, $shipping_address, $payment_method);
            if (!$stmt_order->execute()) {
                throw new Exception("Lỗi khi thực thi câu lệnh Order: " . $stmt_order->error);
            }

            // Lấy ID đơn hàng vừa tạo
            $order_id = $conn->insert_id;

            // 2. Lưu chi tiết đơn hàng
            $sql_detail = "INSERT INTO order_details (
                order_id,
                product_id,
                quantity,
                price,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())";

            $stmt_detail = $conn->prepare($sql_detail);

            foreach ($cart_items as $item) {
                $product_id = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];

                if (!$stmt_detail) {
                    throw new Exception("Lỗi khi chuẩn bị câu lệnh Detail: " . $conn->error);
                }
                $stmt_detail->bind_param("iiii", $order_id, $product_id, $quantity, $price);
                if (!$stmt_detail->execute()) {
                    throw new Exception("Lỗi khi thực thi câu lệnh Detail: " . $stmt_detail->error);
                }

                // Cập nhật số lượng sản phẩm trong kho
                $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                if (!$stmt_update_stock || !$stmt_update_stock->bind_param("ii", $quantity, $product_id) || !$stmt_update_stock->execute()) {
                    throw new Exception("Lỗi khi cập nhật kho: " . ($stmt_update_stock->error ?? $conn->error));
                }
                $stmt_update_stock->close();
            }

            // Nếu mọi thứ thành công
            $success = true;

        } catch (Exception $e) {
            error_log("Order placement error: " . $e->getMessage());
        }

        if ($success) {
            $conn->commit();
            // Xóa giỏ hàng
            unset($_SESSION['cart']);
            setcookie('saved_cart', '', time() - 3600, '/');
            // Thông báo thành công
            setMessage('success', 'Đặt hàng thành công! Mã đơn hàng của bạn là: ' . $order_id);
            redirect('order_success.php?order_id=' . $order_id);
        } else {
            $conn->rollback();
            setMessage('danger', 'Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại sau.');
            redirect('checkout.php');
        }
        exit();
    }
}

// Lấy thông tin người dùng
$user_id = $_SESSION['user_id'];
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_info = $result_user->fetch_assoc();
$stmt_user->close();

// Lấy thông tin giỏ hàng
$cart_items = get_cart_items();
$total = get_cart_total();

// Kiểm tra giỏ hàng
if (empty($cart_items)) {
    setMessage('info', 'Giỏ hàng của bạn đang trống');
    redirect('cart.php');
    exit();
}

// Hiển thị trang
$page_title = 'Thanh toán';
require_once 'includes/header.php';
?>

<style>
@media (min-width: 800px) {
  .checkout-2col-wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    gap: 40px;
    margin: 40px auto 0 auto;
    max-width: 1000px;
    padding: 0 16px;
  }
  .checkout-2col-form {
    flex: 1 1 380px;
    max-width: 440px;
  }
  .checkout-2col-summary {
    flex: 1 1 320px;
    max-width: 400px;
  }
}
@media (max-width: 799px) {
  .checkout-2col-wrapper {
    display: block;
    margin: 0;
    padding: 0;
  }
  .checkout-2col-form, .checkout-2col-summary {
    max-width: 100%;
    margin: 0 auto 0 auto;
  }
}
</style>

<div class="checkout-2col-wrapper">
  <div class="checkout-2col-form" style="background:#fff;padding:32px 28px 28px 28px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.07);min-width:320px;max-width:100%;width:100%;margin-bottom:32px;">
    <h2 style="text-align:center;margin-bottom:24px;">Thông tin thanh toán</h2>
    <form id="checkout-form" method="POST" action="checkout.php" autocomplete="off" style="display:flex;flex-direction:column;gap:18px;">
      <input type="hidden" name="place_order" value="1">
      <div>
        <label for="shipping_name" style="display:block;margin-bottom:6px;font-weight:500;">Họ tên người nhận:</label>
        <input type="text" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" required style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;">
      </div>
      <div>
        <label for="shipping_phone" style="display:block;margin-bottom:6px;font-weight:500;">Số điện thoại:</label>
        <input type="tel" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" required style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;">
      </div>
      <div>
        <label for="shipping_address" style="display:block;margin-bottom:6px;font-weight:500;">Địa chỉ nhận hàng:</label>
        <textarea id="shipping_address" name="shipping_address" rows="3" required style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:6px;resize:vertical;"><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
      </div>
      <div>
        <label style="display:block;margin-bottom:6px;font-weight:500;">Phương thức thanh toán:</label>
        <div style="display:flex;gap:16px;align-items:center;">
          <label style="display:flex;align-items:center;gap:6px;font-weight:400;">
            <input type="radio" name="payment_method" id="payment_cod" value="cod" required> <span>COD</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;font-weight:400;">
            <input type="radio" name="payment_method" id="payment_banking" value="banking"> <span>Chuyển khoản</span>
          </label>
        </div>
      </div>
      <div id="banking-info" style="display:none;background:#f6fafd;border:1px solid #b3e0ff;padding:14px 14px 8px 14px;border-radius:8px;margin-top:-8px;">
        <h4 style="margin:0 0 8px 0;font-size:1.08rem;color:#007bff;">Thông tin chuyển khoản</h4>
        <p style="margin:0 0 4px 0;"><strong>Ngân hàng:</strong> MB Bank</p>
        <p style="margin:0 0 4px 0;"><strong>Chủ tài khoản:</strong> NGUYỄN CHÍ THANH</p>
        <p style="margin:0 0 4px 0;"><strong>Số tài khoản:</strong> 7789454444427</p>
        <p style="margin:0 0 8px 0;color:#555;"><i>Nội dung chuyển khoản: Mã đơn hàng của bạn</i></p>
        <div style="text-align:center;margin-bottom:8px;">
          <img src="images/products/Qr.png" alt="QR Code" style="width:120px;height:120px;object-fit:contain;">
        </div>
      </div>
      <button type="submit" id="submit-checkout" style="padding:12px 0;background:#007bff;color:#fff;border:none;border-radius:6px;font-size:17px;font-weight:600;cursor:pointer;">Đặt hàng</button>
    </form>
  </div>
  <div class="checkout-2col-summary checkout-order-summary" style="background:#fff;padding:24px 20px 18px 20px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.07);max-width:100%;width:100%;margin:0 auto 32px auto;">
    <h3 style="margin-bottom:18px;font-size:1.15rem;">Đơn hàng của bạn</h3>
    <ul style="list-style:none;padding:0;margin:0 0 12px 0;">
      <?php foreach ($cart_items as $item): 
        $stmt = $conn->prepare("SELECT name, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $item['id']);
        $stmt->execute();
        $result_product = $stmt->get_result();
        $product = $result_product->fetch_assoc();
        $stmt->close();
        $stmt_image = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
        $stmt_image->bind_param("i", $item['id']);
        $stmt_image->execute();
        $result_image = $stmt_image->get_result();
        $image = $result_image->fetch_assoc();
        $stmt_image->close();
        $image_url = getImageUrl($image ? $image['image'] : ($product['image'] ?? ''));
      ?>
      <li style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:#f8f9fa;box-shadow:0 1px 4px rgba(44,62,80,0.04);">
        <div style="flex:1;min-width:0;">
          <div style="font-weight:500;font-size:1rem;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;">
            <?php echo htmlspecialchars($product['name']); ?>
          </div>
          <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
            <form method="post" action="checkout.php" style="display:flex;align-items:center;gap:2px;">
              <input type="hidden" name="update_cart" value="1">
              <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
              <input type="hidden" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">
              <button type="submit" style="width:24px;height:24px;border:none;background:#eee;border-radius:4px;font-size:16px;font-weight:bold;cursor:pointer;" <?php if ($item['quantity'] <= 1) echo 'disabled'; ?>>-</button>
            </form>
            <span style="width:32px;text-align:center;border:1px solid #ccc;border-radius:4px;padding:2px 0;font-size:15px;background:#fff;"><?php echo $item['quantity']; ?></span>
            <form method="post" action="checkout.php" style="display:flex;align-items:center;gap:2px;">
              <input type="hidden" name="update_cart" value="1">
              <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
              <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
              <button type="submit" style="width:24px;height:24px;border:none;background:#eee;border-radius:4px;font-size:16px;font-weight:bold;cursor:pointer;">+</button>
            </form>
            <form method="post" action="checkout.php" style="margin-left:4px;">
              <input type="hidden" name="remove_item" value="1">
              <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
              <button type="submit" style="border:none;background:transparent;color:#e74c3c;font-size:18px;cursor:pointer;" title="Xóa sản phẩm">×</button>
            </form>
          </div>
        </div>
        <div style="font-weight:600;color:#e74c3c;font-size:1.05rem;min-width:60px;text-align:right;">
          <?php echo formatPrice($item['price'] * $item['quantity']); ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:1rem;">
      <span>Tạm tính:</span>
      <span><?php echo formatPrice($total); ?></span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-size:1rem;">
      <span>Phí vận chuyển:</span>
      <span>Miễn phí</span>
    </div>
    <hr style="margin:10px 0;">
    <div style="display:flex;justify-content:space-between;align-items:center;font-size:1.08rem;font-weight:600;color:#007bff;">
      <span>Tổng cộng:</span>
      <span><?php echo formatPrice($total); ?></span>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Hiện/ẩn thông tin chuyển khoản
  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  const bankingInfo = document.getElementById('banking-info');
  paymentMethods.forEach(method => {
    method.addEventListener('change', function() {
      if (this.value === 'banking') {
        bankingInfo.style.display = 'block';
      } else {
        bankingInfo.style.display = 'none';
      }
    });
  });
  // Validate form trước khi submit
  const checkoutForm = document.getElementById('checkout-form');
  const submitButton = document.getElementById('submit-checkout');
  checkoutForm.addEventListener('submit', function(e) {
    const shippingName = document.getElementById('shipping_name').value;
    const shippingPhone = document.getElementById('shipping_phone').value;
    const shippingAddress = document.getElementById('shipping_address').value;
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!shippingName || !shippingPhone || !shippingAddress || !paymentMethod) {
      e.preventDefault();
      alert('Vui lòng điền đầy đủ thông tin và chọn phương thức thanh toán');
      return;
    }
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
  });
});
</script>

<?php require_once 'includes/footer.php'; ?> 