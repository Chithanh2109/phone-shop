<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về trang đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = 'Thông tin tài khoản';

// Lấy thông tin người dùng hiện tại
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // Should not happen if isLoggedIn() is true, but for safety
    setMessage('danger', 'Không tìm thấy thông tin người dùng.');
    redirect('login.php');
    exit;
}

// Xử lý cập nhật thông tin nếu submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = [];

    if (empty($name) || empty($email)) {
        $errors[] = 'Vui lòng nhập đầy đủ họ tên và email.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    // Kiểm tra email đã tồn tại cho user khác chưa
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt_check->bind_param('si', $email, $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->fetch_assoc()) {
        $errors[] = 'Email đã được sử dụng.';
    }
    $stmt_check->close();

    if (empty($errors)) {
        $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?";
        $types = "ssss";
        $params = [$name, $email, $phone, $address];
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $types .= "s";
            $params[] = $hashed;
        }
        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;

        $stmt_upd = $conn->prepare($sql);
        $stmt_upd->bind_param($types, ...$params);
        
        if ($stmt_upd->execute()) {
            setMessage('success', 'Cập nhật thông tin thành công!');
            redirect('profile.php');
        } else {
            setMessage('danger', 'Có lỗi xảy ra, vui lòng thử lại: ' . $stmt_upd->error);
            redirect('profile.php');
        }
        $stmt_upd->close();
    } else {
        setMessage('danger', implode('<br>', $errors));
        redirect('profile.php');
    }
}

$is_edit = isset($_GET['edit']) || $_SERVER['REQUEST_METHOD'] === 'POST';

require_once 'includes/header.php';
?>

<div class="container" style="margin: 40px auto; max-width: 700px;">
    <h2>Thông tin tài khoản của bạn</h2>
    <div style="background:#fff;padding:28px 24px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.07);">
        <?php if ($is_edit): ?>
            <form method="post" action="profile.php">
                <div class="form-group">
                    <label>Họ và tên:</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Điện thoại:</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label>Địa chỉ:</label>
                    <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($_POST['address'] ?? $user['address']); ?>">
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới (nếu muốn đổi):</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                <a href="profile.php" class="btn btn-secondary">Hủy</a>
            </form>
        <?php else: ?>
            <p><b>Họ và tên:</b> <?php echo htmlspecialchars($user['name']); ?></p>
            <p><b>Email:</b> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><b>Điện thoại:</b> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><b>Địa chỉ:</b> <?php echo htmlspecialchars($user['address']); ?></p>
            <p><b>Vai trò:</b> <?php echo htmlspecialchars($user['role']); ?></p>
            <a href="profile.php?edit=1" class="btn btn-primary">Chỉnh sửa thông tin</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 