<?php
require_once 'includes/functions.php';
require_once 'config/database.php';
initSession(); // Đảm bảo session được khởi tạo sớm

// ===== Xử lý tự động đăng nhập nếu đã lưu cookie Remember Me =====
if (!isLoggedIn() && isset($_COOKIE['remember_me'])) {
    $remember = json_decode($_COOKIE['remember_me'], true);
    if ($remember && !empty($remember['username']) && !empty($remember['password'])) {
        $username_or_email = $remember['username'];
        $password = $remember['password'];
        // Tự động điền vào POST để dùng lại logic bên dưới
        $_POST['username'] = $username_or_email;
        $_POST['password'] = $password;
        $_POST['auto_login'] = true;
    }
}

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
    $remember_me = isset($_POST['remember_me']);

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
            // Kiểm tra mật khẩu bằng md5
            if (md5($password) === $user['password']) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name']; // Lưu cả tên thật nếu có
                $_SESSION['user_role'] = $user['role'];

                // Nếu chọn ghi nớ, lưu vào cookie (mã hóa đơn giản, có thể nâng cấp bảo mật)
                if ($remember_me && empty($_POST['auto_login'])) {
                    setcookie('remember_me', json_encode([
                        'username' => $username_or_email,
                        'password' => $password
                    ]), time() + 3600*24*30, '/'); // 30 ngày
                } elseif (!$remember_me) {
                    setcookie('remember_me', '', time() - 3600, '/');
                }

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

<div style="display:flex;justify-content:center;align-items:center;min-height:70vh;background:#f7f7f7;">
  <div class="auth-card">
    <h2 class="auth-title">Đăng nhập</h2>
    <?php echo $message; ?>
    <form action="login.php" method="POST" autocomplete="off" class="auth-form">
      <div>
        <label for="username">Tên đăng nhập hoặc Email:</label>
        <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? (isset($remember['username']) ? $remember['username'] : '')); ?>">
      </div>
      <div>
        <label for="password">Mật khẩu:</label>
        <input type="password" id="password" name="password" required value="<?php echo htmlspecialchars($_POST['password'] ?? (isset($remember['password']) ? $remember['password'] : '')); ?>">
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <input type="checkbox" id="remember_me" name="remember_me" <?php if (!empty($_POST['remember_me']) || isset($remember['username'])) echo 'checked'; ?>>
        <label for="remember_me" style="margin:0;font-size:15px;">Ghi nhớ đăng nhập</label>
      </div>
      <button type="submit">Đăng nhập</button>
    </form>
    <div class="auth-extra">
      <p style="margin-bottom:8px;">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
      <p style="margin-bottom:0;"><a href="forgot_password.php">Quên mật khẩu?</a></p>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?> 