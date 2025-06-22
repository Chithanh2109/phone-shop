<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
initSession();

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Đặt lại mật khẩu';
$message = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_id = null;

// Kiểm tra token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        $valid_token = true;
        $user_id = $user['id'];
    } else {
        $message = '<div class="alert alert-danger">Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.</div>';
    }
} else {
    $message = '<div class="alert alert-danger">Link đặt lại mật khẩu không hợp lệ.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $message = '<div class="alert alert-danger">Vui lòng nhập mật khẩu mới.</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger">Mật khẩu phải có ít nhất 6 ký tự.</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Mật khẩu xác nhận không khớp.</div>';
    } else {
        // Cập nhật mật khẩu mới
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt_update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt_update->bind_param('si', $hashed_password, $user_id);
        
        if ($stmt_update->execute()) {
            $message = '<div class="alert alert-success">Mật khẩu đã được đặt lại thành công. Bạn có thể <a href="login.php">đăng nhập</a> ngay bây giờ.</div>';
            $valid_token = false; // Vô hiệu hóa form sau khi đặt lại mật khẩu thành công
        } else {
            $message = '<div class="alert alert-danger">Có lỗi xảy ra khi cập nhật mật khẩu: ' . $stmt_update->error . '</div>';
        }
        $stmt_update->close();
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <h2>Đặt lại mật khẩu</h2>
    <?php echo $message; ?>
    
    <?php if ($valid_token): ?>
        <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
            <div class="form-group">
                <label for="password">Mật khẩu mới:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-auth">Đặt lại mật khẩu</button>
        </form>
    <?php endif; ?>
    
    <p><a href="login.php">Quay lại trang đăng nhập</a></p>
</div>

<?php require_once 'includes/footer.php'; ?> 