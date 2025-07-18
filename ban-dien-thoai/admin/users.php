<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

// trạng thái người dùng
$user_status_vietnamese = [
    'active' => 'Đang hoạt động',
    'inactive' => 'Ngừng hoạt động',
    'pending' => 'Đang chờ kích hoạt',
    'banned' => 'Đã cấm'
];

$page_title = 'Quản lý Người dùng';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

// Xử lý tìm kiếm theo tên người dùng
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$where = "WHERE role != 'admin'";
$params = [];
$types = '';
if ($search_name !== '') {
    $where .= " AND name LIKE ? ";
    $params[] = '%' . $search_name . '%';
    $types .= 's';
}

$sql = "SELECT * FROM users $where ORDER BY created_at DESC";
$stmt = null;
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result_users = $stmt->get_result();
    $users = $result_users->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result_users = $conn->query($sql);
    $users = $result_users->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo getSetting('site_name'); ?></title>
    <link rel="icon" href="<?php echo getSetting('site_favicon'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Tạm dùng CSS chung -->
    <link rel="stylesheet" href="css/admin.css"> <!-- CSS riêng cho admin -->
    <!-- Có thể cần thêm link tới thư viện icon ở đây -->
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <h2>Quản trị</h2>
            <nav>
                <ul>
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
            <!-- Main Content Area -->
            <main class="admin-content">
                <?php echo showMessage(); ?>
                <h1><?php echo $page_title; ?></h1>
                
                <!-- Thêm người dùng mới -->
                <div class="admin-actions" style="margin-bottom: 20px;">
                    <a href="user_add.php" class="admin-btn admin-btn-primary">Thêm Người dùng mới</a>
                </div>
                
                <!-- Thêm form tìm kiếm vào trước bảng người dùng -->
                <div class="admin-search-bar" style="margin-bottom: 18px;">
                    <form method="get" action="users.php" style="display:flex;gap:10px;align-items:center;">
                        <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="Nhập tên người dùng..." class="admin-form-control" style="max-width:220px;">
                        <button type="submit" class="admin-btn admin-btn-primary">Tìm kiếm</button>
                        <a href="users.php" class="admin-btn admin-btn-secondary">Xóa lọc</a>
                    </form>
                </div>
                
                <!-- Nội dung quản lý người dùng sẽ ở đây -->
                 <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ và tên</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Địa chỉ</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['address'] ?? ''); ?></td>
                                        <td><?php echo $user['role']; ?></td>
                                        <td><?php echo htmlspecialchars($user_status_vietnamese[$user['status']] ?? $user['status']); ?></td>
                                        <td><?php echo $user['created_at']; ?></td>
                                        <td class="admin-actions">
                                            <!-- Liên kết Sửa -->
                                            <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-secondary">Sửa</a>
                                            <!-- Liên kết Xóa -->
                                            <a href="user_delete.php?id=<?php echo $user['id']; ?>" class="admin-btn admin-btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này không?');">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10">Không có người dùng nào (trừ admin).</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 