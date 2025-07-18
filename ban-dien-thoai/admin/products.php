<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin();

$page_title = 'Quản lý Sản phẩm';
$current_admin = getCurrentUser();

// Biến phân trang
$per_page = 10; // Số sản phẩm mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Xử lý tìm kiếm theo tên sản phẩm
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$where = '';
$params = [];
$types = '';
if ($search_name !== '') {
    $where = "WHERE p.name LIKE ? ";
    $params[] = '%' . $search_name . '%';
    $types .= 's';
}

// Tạo truy vấn SQL để lấy sản phẩm có phân trang
$sql = "SELECT p.*, c.name as category_name, b.name as brand_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        $where
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lấy tổng số sản phẩm
$count_sql = "SELECT COUNT(*) FROM products p $where";
if ($where) {
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param(substr($types, 0, 1), $params[0]);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_products = $result_count->fetch_row()[0];
    $stmt_count->close();
} else {
    $result_count = $conn->query("SELECT COUNT(*) FROM products");
    $total_products = $result_count->fetch_row()[0];
}
$total_pages = ceil($total_products / $per_page);
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

        <div class="admin-main-content">
            <main class="admin-content">
                <?php echo showMessage(); ?>
                
                <h1><?php echo $page_title; ?></h1>

                <!-- Hiển thị tổng số sản phẩm -->
                <p class="admin-total-products">Tổng số sản phẩm: <b><?php echo $total_products; ?></b></p>
                
                <div class="admin-actions">
                    <a href="products_add.php" class="admin-btn admin-btn-primary">Thêm Sản phẩm mới</a>
                </div>

                <div class="admin-search-bar" style="margin-bottom: 18px;">
                    <form method="get" action="products.php" style="display:flex;gap:10px;align-items:center;">
                        <input type="text" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="Nhập tên sản phẩm..." class="admin-form-control" style="max-width:220px;">
                        <button type="submit" class="admin-btn admin-btn-primary">Tìm kiếm</button>
                        <a href="products.php" class="admin-btn admin-btn-secondary">Xóa lọc</a>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Giá</th>
                                <th>Danh mục</th>
                                <th>Thương hiệu</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <?php 
                                            // Lấy ảnh đại diện sản phẩm
                                            $stmt_image = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
                                                $stmt_image->bind_param('i', $product['id']);
                                                $stmt_image->execute();
                                                $result_image = $stmt_image->get_result();
                                                $main_image = $result_image->fetch_assoc();
                                                $stmt_image->close();
                                                
                                            $image_path = $main_image ? $main_image['image'] : $product['image'];
                                            $image_url = getImageUrl($image_path);
                                            $image_path_check = $_SERVER['DOCUMENT_ROOT'] . parse_url($image_url, PHP_URL_PATH);
                                            if (!file_exists($image_path_check)) {
                                                $image_url = getImageUrl('no-image.jpg');
                                                }
                                            ?>
                                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo formatPrice($product['price']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo ($product['is_active'] == 1) ? 'Đang hoạt động' : 'Ngừng hoạt động'; ?></td>
                                        <td class="admin-actions">
                                            <a href="products_edit.php?id=<?php echo $product['id']; ?>" class="admin-btn admin-btn-secondary">Sửa</a>
                                            <a href="products_delete.php?id=<?php echo $product['id']; ?>" class="admin-btn admin-btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?\n(Sản phẩm sẽ được đánh dấu là ngừng hoạt động)');">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">Không có sản phẩm nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>" class="btn">Trang trước</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>" class="btn">Trang sau</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html> 