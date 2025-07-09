<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    setMessage('danger', 'Thiếu ID đơn hàng.');
    redirect('orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng cũ
$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    setMessage('danger', 'Không tìm thấy đơn hàng.');
    redirect('orders.php');
    exit;
}

// Lấy danh sách sản phẩm trong đơn hàng cũ
$stmt = $conn->prepare('SELECT product_id, quantity FROM order_details WHERE order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) {
    setMessage('danger', 'Đơn hàng không có sản phẩm.');
    redirect('orders.php');
    exit;
}

// Thêm từng sản phẩm vào giỏ hàng session
foreach ($items as $item) {
    $pid = $item['product_id'];
    $qty = $item['quantity'];
    add_to_cart($pid, $qty);
}
setMessage('success', 'Đã thêm lại sản phẩm vào giỏ hàng. Bạn có thể chỉnh sửa trước khi đặt lại.');
redirect('cart.php'); 