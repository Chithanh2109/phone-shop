<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Xử lý thêm, xóa, cập nhật số lượng sản phẩm trong giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product_id']) && isset($_POST['add_quantity'])) {
        $pid = (int)$_POST['add_product_id'];
        $qty = (int)$_POST['add_quantity'];
        if (isset($_POST['buy_now'])) {
            update_cart_quantity($pid, $qty);
            redirect('cart.php');
        } else {
            add_to_cart($pid, $qty);
            redirect('cart.php');
        }
    }
    if (isset($_POST['remove_product_id'])) {
        remove_from_cart((int)$_POST['remove_product_id']);
        setMessage('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        redirect('cart.php');
    }
    if (isset($_POST['update_product_id']) && isset($_POST['update_quantity'])) {
        $pid = (int)$_POST['update_product_id'];
        $qty = (int)$_POST['update_quantity'];
        if ($qty > 0) {
            update_cart_quantity($pid, $qty);
            setMessage('success', 'Đã cập nhật số lượng sản phẩm');
        } else {
            remove_from_cart($pid);
            setMessage('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        }
        redirect('cart.php');
    }
}

require_once 'includes/header.php';

$cart_items = get_cart_items();
$total = get_cart_total();
?>
<main class="container">
    <h2>Giỏ hàng của bạn</h2>
    <?php echo showMessage(); ?>
    <?php if (empty($cart_items)): ?>
        <div class="cart-empty">
            <p>Giỏ hàng của bạn đang trống.</p>
            <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td class="product-info">
                                <?php
                                    // Lấy ảnh đại diện trang chủ (index.php)
                                    $stmt_image = mysqli_prepare($conn, "SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
                                    if ($stmt_image) {
                                        mysqli_stmt_bind_param($stmt_image, "i", $item['id']);
                                        mysqli_stmt_execute($stmt_image);
                                        $result_image = mysqli_stmt_get_result($stmt_image);
                                        $main_image = mysqli_fetch_assoc($result_image);
                                        mysqli_stmt_close($stmt_image);
                                    } else {
                                        $main_image = null;
                                    }

                                    if (!$main_image && !empty($item['image'])) {
                                        $image_url = getImageUrl($item['image']);
                                    } else {
                                        $image_url = $main_image ? getImageUrl($main_image['image']) : getImageUrl('no-image.jpg');
                                    }
                                    // Kiểm tra file vật lý tồn tại
                                    $image_path_check = $_SERVER['DOCUMENT_ROOT'] . parse_url($image_url, PHP_URL_PATH);
                                    if (!file_exists($image_path_check)) {
                                        $image_url = getImageUrl('no-image.jpg');
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>"
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="product-thumbnail">
                                <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            </td>
                            <td class="product-price"><?php echo formatPrice($item['price']); ?></td>
                            <td class="quantity-control">
                                <form method="post" class="quantity-form">
                                    <input type="hidden" name="update_product_id" value="<?php echo $item['id']; ?>">
                                    <div class="quantity-controls">
                                        <button type="button" class="btn-quantity" onclick="updateQuantity(this, -1)">-</button>
                                        <input type="number" name="update_quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" class="quantity-input" onchange="this.form.submit()">
                                        <button type="button" class="btn-quantity" onclick="updateQuantity(this, 1)">+</button>
                                    </div>
                                </form>
                            </td>
                            <td class="subtotal"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                            <td class="actions">
                                <form method="post" class="remove-form">
                                    <input type="hidden" name="remove_product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove" title="Xóa sản phẩm" style="background: none; border: none; color: #dc3545; cursor: pointer; padding: 0;">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Tổng tiền:</span>
                    <span class="total-price"><?php echo formatPrice($total); ?></span>
                </div>
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-secondary">Tiếp tục mua sắm</a>
            <?php if (isLoggedIn()): ?>
                        <a href="checkout.php" class="btn btn-primary">Thanh toán</a>
            <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Đăng nhập để thanh toán</a>
            <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
function updateQuantity(button, change) {
    const form = button.closest('form');
    const input = form.querySelector('input[name="update_quantity"]');
    const newValue = parseInt(input.value) + change;
    
    if (newValue >= 1) {
        input.value = newValue;
        form.submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 