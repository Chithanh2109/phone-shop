<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

$order_id_redirect = null; // Biến để giữ order_id cho việc chuyển hướng

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $user_id = $_SESSION['user_id'];
    
    $order_id_redirect = $order_id; // Lưu lại để chuyển hướng

    // Kiểm tra tính hợp lệ cơ bản
    if ($order_id && $rating >= 1 && $rating <= 5 && !empty($comment)) {
        
        // Kiểm tra xem đơn hàng này có cho phép đánh giá không
        // Logic này giả định người dùng chỉ đánh giá 1 sản phẩm/đơn hàng.
        // Cần có cơ chế phức tạp hơn nếu muốn đánh giá nhiều sản phẩm trong 1 đơn hàng.
        $stmt_order = $conn->prepare(
            "SELECT oi.product_id 
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE oi.order_id = ? AND o.user_id = ? AND o.status = 'completed' 
             LIMIT 1"
        );
        $stmt_order->bind_param("ii", $order_id, $user_id);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();
        $order_item = $result_order->fetch_assoc();
        $stmt_order->close();

        if ($order_item) {
            $product_id = $order_item['product_id'];

            // Lưu đánh giá vào database
            $stmt_review = $conn->prepare(
                "INSERT INTO reviews (product_id, user_id, order_id, rating, comment, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt_review->bind_param("iiiss", $product_id, $user_id, $order_id, $rating, $comment);
            
            if ($stmt_review->execute()) {
                setMessage('success', 'Cảm ơn bạn đã gửi đánh giá!');
            } else {
                // Kiểm tra nếu lỗi do trùng lặp entry (đã đánh giá rồi)
                if ($conn->errno === 1062) { // 1062 là mã lỗi cho Duplicate entry
                     setMessage('warning', 'Bạn đã gửi đánh giá cho đơn hàng này rồi.');
                } else {
                    setMessage('danger', 'Lỗi khi lưu đánh giá: ' . $conn->error);
                }
            }
            $stmt_review->close();
        } else {
            setMessage('danger', 'Không tìm thấy đơn hàng hợp lệ hoặc đơn hàng chưa hoàn thành.');
        }

    } else {
        setMessage('danger', 'Vui lòng điền đầy đủ thông tin đánh giá (sao và bình luận).');
    }
}

// Chuyển hướng về trang chi tiết đơn hàng hoặc trang orders nếu không có id
if ($order_id_redirect) {
    redirect('order_detail.php?id=' . $order_id_redirect);
} else {
    redirect('orders.php');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gửi đánh giá</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Form đánh giá -->
    <div class="container">
        <h1>Gửi đánh giá sản phẩm</h1>
        <form method="POST" action="" class="review-form">
            <!-- ... các trường form hiện tại ... -->
        </form>
    </div>

    <!-- Modal thông báo thành công -->
    <div id="successModal" class="modal <?php echo isset($showSuccessModal) && $showSuccessModal ? 'show' : ''; ?>">
        <div class="modal-content">
            <div class="modal-icon">✅</div>
            <div class="modal-message">Cảm ơn bạn đã gửi đánh giá!</div>
            <button class="modal-close" onclick="closeModal()">Đóng</button>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Hàm đóng modal
    function closeModal() {
        document.getElementById('successModal').classList.remove('show');
    }

    // Tự động đóng modal sau 3 giây
    <?php if (isset($showSuccessModal) && $showSuccessModal): ?>
    setTimeout(function() {
        closeModal();
    }, 3000);
    <?php endif; ?>

    // Đóng modal khi click bên ngoài
    window.onclick = function(event) {
        var modal = document.getElementById('successModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
</body>
</html> 