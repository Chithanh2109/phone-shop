<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

initSession();

if (isLoggedIn()) {
    redirect('index.php');
}

// Biến thông báo
$old_input = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    $confirm_password = sanitizeInput($_POST['confirm_password'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');

    $old_input = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'username' => $username,
    ];

    $errors = [];
    // Validation
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Họ và tên phải có ít nhất 2 ký tự.';
    }
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Tên đăng nhập phải có ít nhất 3 ký tự và chỉ chứa chữ, số, gạch dưới.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (empty($password) || strlen($password) < 3) {
        $errors[] = 'Mật khẩu phải có ít nhất 3 ký tự.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Mật khẩu và xác nhận mật khẩu không khớp.';
    }
    if (empty($phone) || !preg_match('/^[0-9]{10,11}$/', $phone)) {
        $errors[] = 'Số điện thoại phải có 10-11 chữ số.';
    }
    if (empty($address) || strlen($address) < 5) {
        $errors[] = 'Địa chỉ phải có ít nhất 5 ký tự.';
    }

    // Kiểm tra trùng email, username
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $errors[] = 'Email hoặc tên đăng nhập đã tồn tại.';
        }
        $stmt->close();
    }

    // Nếu không có lỗi, thêm user
    if (empty($errors)) {
        // Mã hóa mật khẩu bằng md5
        $hashed_password = md5($password);
        $stmt = $conn->prepare('INSERT INTO users (username, name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, "user")');
        $stmt->bind_param('ssssss', $username, $name, $email, $hashed_password, $phone, $address);
        if ($stmt->execute()) {
            setMessage('success', 'Đăng ký thành công! <a href="login.php">Đăng nhập ngay</a>.');
            redirect('register.php');
        } else {
            $errors[] = 'Lỗi hệ thống, vui lòng thử lại.';
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        setMessage('danger', '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>');
    }
}

require_once 'includes/header.php';
?>
<div style="display:flex;justify-content:center;align-items:center;min-height:70vh;background:#f7f7f7;">
  <div class="auth-card">
    <h2 class="auth-title">Đăng ký tài khoản</h2>
    <form action="register.php" method="POST" autocomplete="off" class="auth-form">
      <div>
        <label for="name">Họ và tên:</label>
        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($old_input['name']); ?>">
      </div>
      <div>
        <label for="username">Tên đăng nhập:</label>
        <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($old_input['username']); ?>">
      </div>
      <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($old_input['email']); ?>">
      </div>
      <div>
        <label for="password">Mật khẩu:</label>
        <input type="password" id="password" name="password" required>
      </div>
      <div>
        <label for="confirm_password">Xác nhận mật khẩu:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
      </div>
      <div>
        <label for="phone">Điện thoại:</label>
        <input type="text" id="phone" name="phone" required value="<?php echo htmlspecialchars($old_input['phone']); ?>">
      </div>
      <div>
        <label for="address">Địa chỉ:</label>
        <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($old_input['address']); ?>">
      </div>
      <button type="submit">Đăng ký</button>
    </form>
    <div class="auth-extra">
      <p style="margin-bottom:0;">Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?> 