<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Xử lý xóa sản phẩm khỏi giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
        remove_from_cart((int)$_POST['remove_product_id']);
        setMessage('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
        redirect('cart.php');
    }
// Xử lý tăng/giảm số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product_id'], $_POST['change_qty'])) {
        $pid = (int)$_POST['update_product_id'];
    $change = (int)$_POST['change_qty'];
    $cart = get_cart_items();
    $current_qty = isset($cart[$pid]['quantity']) ? $cart[$pid]['quantity'] : 1;
    $new_qty = max(1, $current_qty + $change);
    update_cart_quantity($pid, $new_qty);
            setMessage('success', 'Đã cập nhật số lượng sản phẩm');
        redirect('cart.php');
    }

$cart_items = get_cart_items();
$total = get_cart_total();
require_once 'includes/header.php';
?>
<main class="container">
    <h2>Giỏ hàng của bạn</h2>
    <?php echo showMessage(); ?>
    <?php if (empty($cart_items)): ?>
        <div class="cart-empty">
            <svg class="cart-empty-icon" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1.5"/>
                <circle cx="19" cy="21" r="1.5"/>
                <path d="M2.5 4h2l2.5 13h11l2-8H6.5"/>
            </svg>
            <p class="cart-empty-text">Giỏ hàng của bạn đang trống.</p>
            <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="cart-container">
        <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    <th>Xem chi tiết</th>
                    <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                    <td class="cart-name"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="cart-price"><?php echo formatPrice($item['price']); ?></td>
                    <td>
                        <form method="post" class="cart-qty-form" style="display:flex;align-items:center;gap:4px;">
                                    <input type="hidden" name="update_product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="qty-btn" name="change_qty" value="-1">-</button>
                            <span class="cart-qty-value"><?php echo $item['quantity']; ?></span>
                            <button type="submit" class="qty-btn" name="change_qty" value="1">+</button>
                                </form>
                            </td>
                    <td class="cart-subtotal"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                    <td>
                        <a href="product.php?id=<?php echo $item['id']; ?>" class="btn btn-info" target="_blank">Xem chi tiết</a>
                    </td>
                    <td>
                        <form method="post" class="cart-remove-form">
                                    <input type="hidden" name="remove_product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="cart-remove-btn" title="Xóa sản phẩm">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="vertical-align:middle">
                                    <rect x="5" y="7" width="14" height="12" rx="2" stroke="#dc3545" stroke-width="2" fill="#fff"/>
                                    <path d="M9 10v6M12 10v6M15 10v6" stroke="#dc3545" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M3 7h18" stroke="#dc3545" stroke-width="2" stroke-linecap="round"/>
                                    <rect x="9" y="3" width="6" height="4" rx="1" stroke="#dc3545" stroke-width="2" fill="#fff"/>
                                </svg>
                            </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <div class="cart-summary-row">
            <span><b>Tổng tiền:</b></span>
            <span class="cart-total-price"><?php echo formatPrice($total); ?></span>
                </div>
        <div class="cart-action-row">
                    <a href="index.php" class="btn btn-secondary">Tiếp tục mua sắm</a>
            <?php if (isLoggedIn()): ?>
                        <a href="checkout.php" class="btn btn-primary">Thanh toán</a>
            <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Đăng nhập để thanh toán</a>
            <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>
<script>
// Đảm bảo không có lỗi addEventListener khi phần tử không tồn tại
function updateQuantity(button, change) {
    const form = button.closest('form');
    const input = form.querySelector('input[name="update_quantity"]');
    const newValue = parseInt(input.value) + change;
    if (newValue >= 1) {
        input.value = newValue;
        form.submit();
    }
}
// Nếu có đoạn nào dùng addEventListener, hãy luôn kiểm tra null:
// Ví dụ:
// const btn = document.getElementById('some-id');
// if (btn) { btn.addEventListener('click', ...); }
</script>
<?php require_once 'includes/footer.php'; ?> 