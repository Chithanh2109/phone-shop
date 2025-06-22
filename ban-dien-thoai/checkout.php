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

// Lấy thông báo
$message = showMessage();

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

<div style="padding: 20px; max-width: 400px; margin: 20px auto; border: 1px solid #ccc; border-radius: 5px;">
    <h2>Thông tin thanh toán</h2>
    
    <?php echo $message; ?>

    <form id="checkout-form" method="POST" action="checkout.php">
        <input type="hidden" name="place_order" value="1">
        
        <div style="margin-bottom: 15px;">
            <label for="shipping_name" style="display: block; margin-bottom: 5px;">Họ tên người nhận</label>
            <input type="text" id="shipping_name" name="shipping_name" 
                   value="<?php echo htmlspecialchars($user_info['name'] ?? ''); ?>" required style="width: 350px; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="shipping_phone" style="display: block; margin-bottom: 5px;">Số điện thoại</label>
            <input type="tel" id="shipping_phone" name="shipping_phone" 
                   value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" required style="width: 350px; padding: 8px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="shipping_address" style="display: block; margin-bottom: 5px;">Địa chỉ nhận hàng</label>
            <textarea id="shipping_address" name="shipping_address" 
                      rows="3" required style="width: 350px; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 10px;">Phương thức thanh toán</label>
            <div style="margin-bottom: 5px;">
                <input type="radio" name="payment_method" 
                       id="payment_cod" value="cod" required> <label for="payment_cod">Thanh toán khi nhận hàng (COD)</label>
            </div>
            <div>
                <input type="radio" name="payment_method" 
                       id="payment_banking" value="banking"> <label for="payment_banking">Chuyển khoản ngân hàng</label>
            </div>

            <!-- Thông tin chuyển khoản -->
            <div id="banking-info" style="display: none; margin-top: 15px; padding: 15px; background: #f2f2f2; border-radius: 5px; width: 350px; box-sizing: border-box;">
                <h4>Thông tin chuyển khoản</h4>
                <p style="margin: 5px 0;"><strong>Ngân hàng:</strong> MB Bank</p>
                <p style="margin: 5px 0;"><strong>Chủ tài khoản:</strong> NGUYỄN CHÍ THANH</p>
                <p style="margin: 5px 0;"><strong>Số tài khoản:</strong> 7789454444427</p>
                <p style="margin: 5px 0; font-size: 0.9em; color: #555;"><i>Nội dung chuyển khoản: Mã đơn hàng của bạn</i></p>
                <div style="text-align: center; margin-top: 15px;">
                    <img src="images/products/Qr.png" alt="QR Code" style="max-width: 150px; height: auto;">
                </div>
            </div>
        </div>

        <button type="submit" id="submit-checkout" style="width: 350px; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; font-size: 1em; cursor: pointer;">Đặt hàng</button>
    </form>

    <div style="margin-top: 30px;">
        <h3>Đơn hàng của bạn</h3>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($cart_items as $item): 
                $stmt = $conn->prepare("SELECT name, image FROM products WHERE id = ?");
                $stmt->bind_param("i", $item['id']);
                $stmt->execute();
                $result_product = $stmt->get_result();
                $product = $result_product->fetch_assoc();
                $stmt->close();
                
                // Lấy ảnh sản phẩm
                $stmt_image = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
                $stmt_image->bind_param("i", $item['id']);
                $stmt_image->execute();
                $result_image = $stmt_image->get_result();
                $image = $result_image->fetch_assoc();
                $stmt_image->close();
                $image_url = getImageUrl($image ? $image['image'] : ($product['image'] ?? ''));
            ?>
            <li style="display: flex; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #eee;">
                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px;">
                <div style="flex-grow: 1;">
                    <h6 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($product['name']); ?></h6>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <!-- Quantity and Remove Controls -->
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <!-- Update Quantity Form -->
                            <form method="post" action="checkout.php" style="display:inline-flex; align-items:center;">
                                <input type="hidden" name="update_cart" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="button" class="quantity-btn minus" style="width: 25px; height: 25px; padding: 0; text-align: center; border: 1px solid #ccc; background-color: #eee; cursor: pointer;">-</button>
                                <input type="text" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input" style="width: 40px; text-align: center; padding: 3px; border: 1px solid #ccc;">
                                <button type="button" class="quantity-btn plus" style="width: 25px; height: 25px; padding: 0; text-align: center; border: 1px solid #ccc; background-color: #eee; cursor: pointer;">+</button>
                                <!-- Hidden submit button to be triggered by JS -->
                                <button type="submit" class="update-cart-submit" style="display: none;">Update</button>
                            </form>
                            <!-- Remove Item Form -->
                            <form method="post" action="checkout.php" style="display:inline-block; margin-left: 10px;">
                                <input type="hidden" name="remove_item" value="1">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-remove" data-id="<?php echo $item['id']; ?>" style="background: none; border: none; color: #dc3545; cursor: pointer; padding: 0;">Xóa</button>
                            </form>
                        </div>
                        <span style="font-weight: bold;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold;">
            <span>Tạm tính:</span>
            <span><?php echo formatPrice($total); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold;">
            <span>Phí vận chuyển:</span>
            <span>Miễn phí</span>
        </div>
        <hr style="margin: 15px 0;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 1.2em; font-weight: bold;">
            <span>Tổng cộng:</span>
            <span style="color: #007bff;"><?php echo formatPrice($total); ?></span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý hiển thị/ẩn thông tin chuyển khoản
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
    const submitButton = checkoutForm.querySelector('button[type="submit"]');

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

        // Vô hiệu hóa nút submit để tránh submit nhiều lần
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
    });

    // --- Script xử lý số lượng và xóa sản phẩm ---
    const quantityControls = document.querySelectorAll('.quantity-btn');

    quantityControls.forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            const quantityInput = form.querySelector('.quantity-input');
            let currentValue = parseInt(quantityInput.value);
            const productId = form.querySelector('input[name="product_id"]').value;

            if (this.classList.contains('plus')) {
                quantityInput.value = currentValue + 1;
            } else if (this.classList.contains('minus')) {
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                } else if (currentValue === 1) {
                     // Xử lý xóa sản phẩm khi số lượng về 0 hoặc nhỏ hơn
                     if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng không?')) {
                         // Find the remove form associated with this item
                         const itemContainer = this.closest('li'); // Adjusted selector
                         const removeForm = itemContainer ? itemContainer.querySelector('form input[name="remove_item"]').closest('form') : null;

                         if (removeForm) {
                             removeForm.submit(); // Submit the remove form
                             return; // Stop further processing
                         }
                     }
                     return; // Stop if user cancels deletion or remove form not found
                }
            }

            // Submit the update form after changing quantity (only if not deleting)
            // Check if quantityInput is still valid after potential deletion attempt
            if (parseInt(quantityInput.value) > 0) {
                 form.submit();
            }
        });
    });

    // Handle direct input change in quantity field
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            let currentValue = parseInt(this.value);
             if (isNaN(currentValue) || currentValue < 1) {
                 this.value = 1; // Reset to 1 if invalid
             }
            form.submit(); // Submit the form on change
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 