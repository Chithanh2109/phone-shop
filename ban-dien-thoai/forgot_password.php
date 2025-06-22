<?php
// Ẩn warning và notice PHP khỏi giao diện
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

require_once 'includes/functions.php';
require_once 'config/database.php';
initSession();

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Quên mật khẩu';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');

    if (empty($email)) {
        $message = '<div class="alert alert-danger">Vui lòng nhập địa chỉ email của bạn.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Địa chỉ email không hợp lệ.</div>';
    } else {
        // Kiểm tra email có tồn tại trong database không
        $stmt = $conn->prepare("SELECT id, username, name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Tạo token reset password
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token hết hạn sau 1 giờ

            // Lưu token vào database
            $stmt_update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt_update->bind_param('ssi', $token, $expires, $user['id']);
            if ($stmt_update->execute()) {
                // Tạo link reset password
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                
                // Gửi email (trong môi trường thực tế, bạn cần cấu hình SMTP)
                $to = $email;
                $subject = "Yêu cầu đặt lại mật khẩu";
                $message = "Xin chào " . htmlspecialchars($user['name']) . ",\n\n";
                $message .= "Bạn đã yêu cầu đặt lại mật khẩu. Vui lòng click vào link sau để đặt lại mật khẩu:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "Link này sẽ hết hạn sau 1 giờ.\n\n";
                $message .= "Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.\n\n";
                $message .= "Trân trọng,\n";
                $message .= "Ban điện thoại";

                $headers = "From: noreply@bandienthoai.com\r\n";
                $headers .= "Reply-To: noreply@bandienthoai.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                if (mail($to, $subject, $message, $headers)) {
                    $message = '<div class="alert alert-success">Chúng tôi đã gửi email hướng dẫn đặt lại mật khẩu đến địa chỉ email của bạn. Vui lòng kiểm tra hộp thư.</div>';
                } else {
                    $message = '<div class="alert alert-danger">Có lỗi xảy ra khi gửi email. Vui lòng thử lại sau.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Có lỗi xảy ra khi cập nhật token: ' . $stmt_update->error . '</div>';
            }
            $stmt_update->close();
        } else {
            // Hiển thị thông báo chung để tránh lộ thông tin email nào có trong hệ thống
            $message = '<div class="alert alert-success">Nếu email của bạn tồn tại trong hệ thống, chúng tôi đã gửi một liên kết đặt lại mật khẩu.</div>';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <h2>Quên mật khẩu</h2>
    <?php echo $message; ?>
    <form action="forgot_password.php" method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn-auth">Gửi yêu cầu đặt lại mật khẩu</button>
    </form>
    <p><a href="login.php">Quay lại trang đăng nhập</a></p>
</div>

<?php require_once 'includes/footer.php'; ?> 