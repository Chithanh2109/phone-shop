<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập trang này

$page_title = 'Thêm Người dùng mới';
$current_admin = getCurrentUser();

// Mapping trạng thái người dùng sang tiếng Việt (tái sử dụng từ users.php)
$user_status_vietnamese = [
    'active' => 'Đang hoạt động',
    'inactive' => 'Ngừng hoạt động',
    'pending' => 'Đang chờ kích hoạt',
    'banned' => 'Đã cấm'
];

// --- Xử lý POST request khi form được submit --- //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Mật khẩu không sanitize HTML, sẽ hash
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $role = sanitizeInput($_POST['role']);
    $status = sanitizeInput($_POST['status']);

    $errors = [];

    // Basic validation
    if (empty($username) || empty($name) || empty($email) || empty($password) || empty($role) || empty($status)) {
        $errors[] = 'Vui lòng điền đầy đủ các trường bắt buộc (Tên đăng nhập, Họ tên, Email, Mật khẩu, Vai trò, Trạng thái).';
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không hợp lệ.';
    }

    // Validate username uniqueness
    $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt_check_username->bind_param('s', $username);
    $stmt_check_username->execute();
    if ($stmt_check_username->get_result()->fetch_assoc()) {
        $errors[] = 'Tên đăng nhập đã tồn tại.';
    }
    $stmt_check_username->close();

    // Validate email uniqueness
    $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt_check_email->bind_param('s', $email);
    $stmt_check_email->execute();
    if ($stmt_check_email->get_result()->fetch_assoc()) {
        $errors[] = 'Email đã được sử dụng.';
    }
    $stmt_check_email->close();

    // Validate role and status
    if (!in_array($role, ['user', 'admin'])) { // Chỉ cho phép gán vai trò user hoặc admin
         $errors[] = 'Vai trò không hợp lệ.';
    }
    if (!in_array($status, ['active', 'inactive', 'pending', 'banned'])) {
         $errors[] = 'Trạng thái không hợp lệ.';
    }

    if (empty($errors)) {
        // Hash mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Thêm người dùng mới vào database
        $stmt_insert = $conn->prepare("INSERT INTO users (username, name, email, password, phone, address, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        // Types: s(username), s(name), s(email), s(password), s(phone), s(address), s(role), s(status)
        $stmt_insert->bind_param('ssssssss', $username, $name, $email, $hashed_password, $phone, $address, $role, $status);
        
        if ($stmt_insert->execute()) {
            setMessage('success', 'Thêm người dùng mới thành công.');
            redirect('users.php'); // Chuyển hướng về trang danh sách người dùng
        } else {
            setMessage('danger', 'Có lỗi xảy ra khi thêm người dùng mới: ' . $stmt_insert->error);
        }
        $stmt_insert->close();
    } else {
        // Nếu có lỗi validation, hiển thị lại form với dữ liệu đã nhập và thông báo lỗi
         setMessage('danger', implode('<br>', $errors));
    }
}

// --- Hiển thị Form Thêm Người dùng --- //
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="icon" href="<?php echo getSetting('site_favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../css/style.css"> <!-- Tạm dùng CSS chung -->
    <link rel="stylesheet" href="css/admin.css"> <!-- CSS riêng cho admin -->
    <!-- Có thể cần thêm link tới thư viện icon ở đây -->
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <nav>
                <ul>
                    <li><a href="../index.php" class="sidebar-link">Trang chủ</a></li>
                    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
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
            <!-- Admin Header Top -->
            <header class="admin-header-top">
                <div>
                    <h3><?php echo $page_title; ?></h3>
                </div>
                <div class="user-menu">
                     <span>Xin chào, <b><?php echo htmlspecialchars($current_admin['name'] ?? ''); ?></b></span>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="admin-content">
                <?php echo showMessage(); ?>
                <h1><?php echo $page_title; ?></h1>
                
                <div class="admin-form-container" style="max-width: 700px;">

                     <form action="user_add.php" method="POST">
                         <div class="admin-form-group">
                             <label for="username">Tên đăng nhập:</label>
                             <input type="text" id="username" name="username" class="admin-form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                         </div>

                         <div class="admin-form-group">
                             <label for="name">Họ và tên:</label>
                             <input type="text" id="name" name="name" class="admin-form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                         </div>

                         <div class="admin-form-group">
                             <label for="email">Email:</label>
                             <input type="email" id="email" name="email" class="admin-form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                         </div>

                         <div class="admin-form-group">
                              <label for="password">Mật khẩu:</label>
                              <input type="password" id="password" name="password" class="admin-form-control" required>
                          </div>

                         <div class="admin-form-group">
                             <label for="phone">Điện thoại:</label>
                             <input type="text" id="phone" name="phone" class="admin-form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                         </div>

                         <div class="admin-form-group">
                             <label for="address">Địa chỉ:</label>
                             <textarea id="address" name="address" class="admin-form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                         </div>

                         <div class="admin-form-group">
                             <label for="role">Vai trò:</label>
                             <select id="role" name="role" class="admin-form-control" required>
                                 <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : ''; ?>>Người dùng</option>
                                  <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                             </select>
                         </div>

                         <div class="admin-form-group">
                             <label for="status">Trạng thái:</label>
                             <select id="status" name="status" class="admin-form-control" required>
                                 <?php foreach ($user_status_vietnamese as $value => $label): ?>
                                     <option value="<?php echo $value; ?>" <?php echo (isset($_POST['status']) && $_POST['status'] === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                 <?php endforeach; ?>
                             </select>
                         </div>

                         <div class="admin-form-actions">
                             <button type="submit" class="admin-btn admin-btn-primary">Thêm Người dùng</button>
                             <a href="users.php" class="admin-btn admin-btn-secondary">Hủy</a>
                         </div>

                     </form>

                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 