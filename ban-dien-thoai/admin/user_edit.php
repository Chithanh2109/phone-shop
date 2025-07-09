<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập trang này

$page_title = 'Chỉnh sửa Người dùng';
$current_admin = getCurrentUser();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;

if ($user_id > 0) {
    // Lấy thông tin người dùng từ database
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role != 'admin' LIMIT 1"); // Không cho phép sửa thông tin admin khác
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        setMessage('danger', 'Người dùng không tồn tại hoặc không được phép chỉnh sửa.');
        redirect('users.php');
    }
} else {
    setMessage('danger', 'Không có ID người dùng được chỉ định.');
    redirect('users.php');
}

// --- Xử lý khi form được gửi đi (POST request) --- //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $username = sanitizeInput($_POST['username']);
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $role = sanitizeInput($_POST['role']);
    $status = sanitizeInput($_POST['status']);
    $password = $_POST['password']; // Mật khẩu không cần sanitize HTML, sẽ hash

    $errors = [];

    // Kiểm tra dữ liệu bắt buộc
    if (empty($username) || empty($name) || empty($email) || empty($role) || empty($status)) {
        $errors[] = 'Vui lòng điền đầy đủ các trường bắt buộc.';
    }

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không hợp lệ.';
    }

    // Kiểm tra tên đăng nhập đã tồn tại (trừ user hiện tại)
    $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    $stmt_check_username->bind_param('si', $username, $user_id);
    $stmt_check_username->execute();
    if ($stmt_check_username->get_result()->fetch_assoc()) {
        $errors[] = 'Tên đăng nhập đã tồn tại.';
    }
    $stmt_check_username->close();

    // Kiểm tra email đã tồn tại (trừ user hiện tại)
    $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt_check_email->bind_param('si', $email, $user_id);
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->fetch_assoc()) {
        $errors[] = 'Email đã được sử dụng.';
    }
    $stmt_check_email->close();

    // Kiểm tra vai trò và trạng thái hợp lệ
    if (!in_array($role, ['user', 'admin'])) { // Chỉ cho phép gán vai trò user hoặc admin
         $errors[] = 'Vai trò không hợp lệ.';
    }
    if (!in_array($status, ['active', 'inactive'])) {
         $errors[] = 'Trạng thái không hợp lệ.';
    }


    if (empty($errors)) {
        // Cập nhật thông tin người dùng
        $sql = "UPDATE users SET username = ?, name = ?, email = ?, phone = ?, address = ?, role = ?, status = ?";
        $types = "sssssss";
        $params = [$username, $name, $email, $phone, $address, $role, $status];

        // Nếu có nhập mật khẩu mới, cập nhật cả mật khẩu
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $types .= "s";
            $params[] = $hashed_password;
        }

        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;

        $stmt_update = $conn->prepare($sql);
        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            setMessage('success', 'Cập nhật thông tin người dùng thành công.');
            redirect('users.php'); // Chuyển hướng về trang danh sách người dùng
        } else {
            setMessage('danger', 'Có lỗi xảy ra khi cập nhật thông tin người dùng: ' . $stmt_update->error);
            // Để lại dữ liệu form cũ
            $user = $_POST; // Gán lại dữ liệu post vào $user để hiển thị trên form
            $user['id'] = $user_id; // Đảm bảo ID không bị mất
        }
        $stmt_update->close();
    } else {
        // Nếu có lỗi validation, hiển thị lại form với dữ liệu đã nhập và thông báo lỗi
         setMessage('danger', implode('<br>', $errors));
         $user = $_POST; // Gán lại dữ liệu post vào $user để hiển thị trên form
         $user['id'] = $user_id; // Đảm bảo ID không bị mất
    }
}

// --- Hiển thị Form Chỉnh sửa --- //
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="icon" href="<?php echo getSetting('site_favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="css/admin.css"> 
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
        <h2>Quản trị</h2>
            <nav>
                <ul>
                    <li><a href="index.php" class="sidebar-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Bảng điều khiển</a></li>
                    <li><a href="products.php" class="sidebar-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">Quản lý Sản phẩm</a></li>
                    <li><a href="orders.php" class="sidebar-link <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">Quản lý Đơn hàng</a></li>
                    <li><a href="users.php" class="sidebar-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">Quản lý Người dùng</a></li>
                    <li><a href="reviews.php" class="sidebar-link <?php echo ($current_page == 'reviews.php') ? 'active' : ''; ?>">Quản lý Đánh giá</a></li>
                    <li><a href="online_payments.php" class="sidebar-link <?php echo ($current_page == 'online_payments.php') ? 'active' : ''; ?>">Quản lý Thanh toán</a></li>
                    <li><a href="faq_manage.php" class="sidebar-link <?php echo ($current_page == 'faq_manage.php') ? 'active' : ''; ?>">Quản lý Câu hỏi Thường gặp</a></li>
                    <li><a href="../logout.php" class="sidebar-link">Đăng xuất</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Admin Main Content -->
        <div class="admin-main-content">
            <!-- Main Content Area -->
            <main class="admin-content">
                <h1><?php echo $page_title; ?>: <?php echo htmlspecialchars($user['name'] ?? ''); ?></h1>
                
                <div class="admin-form-container" style="max-width: 700px;">

                     <form action="user_edit.php?id=<?php echo $user['id']; ?>" method="POST">
                         <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                         <div class="admin-form-group">
                             <label for="username">Tên đăng nhập:</label>
                             <input type="text" id="username" name="username" class="admin-form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                         </div>

                         <div class="admin-form-group">
                             <label for="name">Họ và tên:</label>
                             <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                         </div>

                         <div class="admin-form-group">
                             <label for="email">Email:</label>
                             <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                         </div>

                         <div class="admin-form-group">
                             <label for="phone">Điện thoại:</label>
                             <input type="text" id="phone" name="phone" class="admin-form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                         </div>

                         <div class="admin-form-group">
                             <label for="address">Địa chỉ:</label>
                             <textarea id="address" name="address" class="admin-form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                         </div>

                         <div class="admin-form-group">
                             <label for="role">Vai trò:</label>
                             <select id="role" name="role" class="admin-form-control" required>
                                 <option value="user" <?php echo (isset($user['role']) && $user['role'] === 'user') ? 'selected' : ''; ?>>Người dùng</option>
                                 <!-- Admin không thể tự hạ vai trò của mình -->
                                 <?php if ($current_admin && $current_admin['id'] !== $user['id']): ?>
                                     <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                 <?php endif; ?>
                             </select>
                         </div>

                         <div class="admin-form-group">
                             <label for="status">Trạng thái:</label>
                             <select id="status" name="status" class="admin-form-control" required>
                                 <option value="active" <?php echo (isset($user['status']) && $user['status'] === 'active') ? 'selected' : ''; ?>>Đang hoạt động</option>
                                 <option value="inactive" <?php echo (isset($user['status']) && $user['status'] === 'inactive') ? 'selected' : ''; ?>>Ngừng hoạt động</option>
                             </select>
                         </div>

                          <div class="admin-form-group">
                              <label for="password">Mật khẩu (để trống nếu không đổi):</label>
                              <input type="password" id="password" name="password" class="admin-form-control">
                          </div>

                         <div class="admin-form-actions">
                             <button type="submit" class="admin-btn admin-btn-primary">Lưu thay đổi</button>
                             <a href="users.php" class="admin-btn admin-btn-secondary">Hủy</a>
                         </div>

                     </form>

                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 