<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
initSession(); // Đảm bảo session được khởi tạo sớm

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}
$page_title = 'Đăng nhập';
$message = ''; // Biến để hiển thị thông báo lỗi hoặc thành công
// Code xử lý đăng nhập sẽ ở đây sau
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = sanitizeInput($_POST['username'] ?? ''); // Lấy tên đăng nhập hoặc email từ input
    $password = sanitizeInput($_POST['password']);

    if (empty($username_or_email) || empty($password)) {
        $message = '<div class="alert alert-danger">Vui lòng điền đầy đủ Tên đăng nhập và Mật khẩu.</div>';
    } else {
        // Kiểm tra cả username và email
        $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name']; // Lưu cả tên thật nếu có
                $_SESSION['user_role'] = $user['role'];

                // Khôi phục giỏ hàng từ cookie
                restore_cart_from_cookie();

                // Hiển thị thông báo đăng nhập thành công
                setMessage('success', 'Đăng nhập thành công!');

                // Chuyển hướng dựa vào role
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                } else {
                    redirect('index.php');
                }
            } else {
                $message = '<div class="alert alert-danger">Mật khẩu không chính xác.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Tài khoản không tồn tại.</div>';
        }
    }
}

require_once 'includes/header.php'; // Header được nạp sau khi xử lý logic

?>

<div class="auth-container">
    <h2>Đăng nhập</h2>
    <?php echo $message; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Tên đăng nhập hoặc Email:</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Đăng nhập</button>
    </form>
    <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
    <p><a href="forgot_password.php">Quên mật khẩu?</a></p>
</div>

<?php require_once 'includes/footer.php'; ?> 