<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Thêm Sản phẩm mới';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

$message = '';

// --- Lấy danh sách Danh mục và Thương hiệu cho dropdown --- //
$categories = [];
$brands = [];

try {
    // Sử dụng mysqli để lấy dữ liệu
    $result_categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    if ($result_categories) {
        $categories = $result_categories->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi khi truy vấn danh mục: " . $conn->error);
    }

    $result_brands = $conn->query("SELECT id, name FROM brands ORDER BY name ASC");
    if ($result_brands) {
        $brands = $result_brands->fetch_all(MYSQLI_ASSOC);
    } else {
        throw new Exception("Lỗi khi truy vấn thương hiệu: " . $conn->error);
    }
} catch (Exception $e) {
    $message = '<div class="admin-alert admin-alert-danger">' . $e->getMessage() . '</div>';
}
// --- Kết thúc lấy danh sách --- //

// --- Xử lý khi form được submit --- //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_INT);

    $sale_price = null;
    if (isset($_POST['sale_price']) && $_POST['sale_price'] !== '') {
        $sale_price = filter_var($_POST['sale_price'], FILTER_VALIDATE_INT);
    }
    
    $description = sanitizeInput($_POST['description']);
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
    $brand_id = filter_var($_POST['brand_id'], FILTER_VALIDATE_INT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);

    $upload_errors = [];

    // Validation cơ bản
    if (empty($name) || $price === false || empty($description) || $category_id === false || $brand_id === false) {
        $message = '<div class="admin-alert admin-alert-danger">Vui lòng điền đầy đủ và đúng định dạng các thông tin bắt buộc (Tên, Giá, Mô tả, Danh mục, Thương hiệu).</div>';
    } elseif ($sale_price === false) {
        $message = '<div class="admin-alert admin-alert-danger">Giá khuyến mãi không hợp lệ. Vui lòng nhập số nguyên.</div>';
    } elseif ($sale_price !== null && $sale_price >= $price) {
        $message = '<div class="admin-alert admin-alert-danger">Giá khuyến mãi phải nhỏ hơn giá gốc.</div>';
    } else {
        // Bắt đầu giao dịch (transaction) để đảm bảo thêm sản phẩm và ảnh thành công hoặc không thêm gì cả
        $conn->begin_transaction();
        $product_added_successfully = false;
        try {
            // Thêm sản phẩm vào bảng `products`
            $stmt = $conn->prepare("INSERT INTO products (name, price, sale_price, description, category_id, brand_id, is_active, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            // 's' for string, 'i' for integer. sale_price can be null, so it's a bit tricky. We'll handle it.
            // Using 'siisiiii' for (string, int, int, string, int, int, int, int)
            $stmt->bind_param('siisiiii', $name, $price, $sale_price, $description, $category_id, $brand_id, $is_active, $stock);
            
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi thêm sản phẩm: " . $stmt->error);
            }
            $product_added_successfully = true;

            // Lấy ID của sản phẩm vừa thêm
            $product_id = $conn->insert_id;

            // --- Xử lý Upload ảnh --- //
            if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
                $upload_dir = '../images/products/';
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $max_size = 5 * 1024 * 1024; // 5MB

                $image_urls = [];
                $errors = [];

                foreach ($_FILES['product_images']['name'] as $key => $image_name) {
                    $file_tmp = $_FILES['product_images']['tmp_name'][$key];
                    $file_size = $_FILES['product_images']['size'][$key];
                    $file_error = $_FILES['product_images']['error'][$key];
                    $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                    
                    // Kiểm tra lỗi upload
                    if ($file_error !== 0) {
                        $errors[] = "Lỗi upload file '$image_name'. Mã lỗi: " . $file_error;
                        continue;
                    }

                    // Kiểm tra loại file
                    if (!in_array($file_ext, $allowed_types)) {
                        $errors[] = "File '$image_name' có định dạng không hợp lệ. Chỉ cho phép JPG, JPEG, PNG, GIF.";
                        continue;
                    }

                    // Kiểm tra kích thước file
                    if ($file_size > $max_size) {
                        $errors[] = "File '$image_name' có kích thước quá lớn (tối đa 5MB).";
                        continue;
                    }

                    // Tạo tên file duy nhất
                    $new_file_name = uniqid('', true) . '.' . $file_ext;
                    $target_file = $upload_dir . $new_file_name;

                    // Di chuyển file đã upload
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $image_urls[] = $new_file_name; // Lưu tên file để lưu vào DB
                    } else {
                        $errors[] = "Lỗi khi di chuyển file đã upload '$image_name'.";
                    }
                }

                // Lưu đường dẫn ảnh vào bảng `product_images`
                if (!empty($image_urls)) {
                    $stmt_images = $conn->prepare("INSERT INTO product_images (product_id, image) VALUES (?, ?)");
                    foreach ($image_urls as $url) {
                        $stmt_images->bind_param('is', $product_id, $url);
                        if (!$stmt_images->execute()) {
                             // Log a warning, but don't stop the whole process
                            $message .= '<div class="admin-alert admin-alert-warning">Lỗi khi lưu ảnh ' . htmlspecialchars($url) . ': ' . $stmt_images->error . '</div>';
                        }
                    }
                    $stmt_images->close();
                }
                
                // Hiển thị lỗi upload nếu có
                if (!empty($errors)) {
                     // Gộp các lỗi thành một thông báo
                     $message .= '<div class="admin-alert admin-alert-warning">Có lỗi xảy ra khi xử lý ảnh:<br>-' . implode('<br>-', $errors) . '</div>';
                }

            }
            // --- Kết thúc Xử lý Upload ảnh --- //

            $conn->commit(); // Hoàn tất giao dịch
            setMessage('success', 'Thêm sản phẩm mới thành công.' . (!empty($errors) ? ' (Có lỗi với một số ảnh)' : ''));
            redirect('products.php'); // Chuyển hướng về trang danh sách sản phẩm

        } catch (Exception $e) {
            $conn->rollback(); // Hoàn tác giao dịch nếu có lỗi
            $message = '<div class="admin-alert admin-alert-danger">Lỗi khi thêm sản phẩm vào database: ' . $e->getMessage() . '</div>';
             // Nếu có lỗi DB, xóa các file ảnh đã upload (nếu có)
             if ($product_added_successfully && !empty($image_urls)){
                 $upload_dir = '../images/products/';
                 foreach($image_urls as $url){
                     if(file_exists($upload_dir . $url)){
                         unlink($upload_dir . $url);
                     }
                 }
             }
        }
    }
}
// --- Kết thúc Xử lý form submit --- //

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
                <?php echo $message; // Hiển thị lỗi/thông báo từ quá trình xử lý form ?>
                
                <h1><?php echo $page_title; ?></h1>
                
                <div class="admin-form-container">
                    <form action="products_add.php" method="POST" enctype="multipart/form-data"> <!-- Quan trọng: thêm enctype="multipart/form-data" cho form có upload file -->
                        
                        <div class="admin-form-group">
                            <label for="name">Tên sản phẩm:</label>
                            <input type="text" id="name" name="name" class="admin-form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); // Giữ lại giá trị cũ nếu có lỗi form ?>">
                        </div>

                        <div class="admin-form-group">
                            <label for="price">Giá gốc (*)</label>
                            <input type="number" id="price" name="price" step="1" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                        </div>

                        <div class="admin-form-group">
                            <label for="sale_price">Giá khuyến mãi</label>
                            <input type="number" id="sale_price" name="sale_price" step="1" value="<?php echo htmlspecialchars($_POST['sale_price'] ?? ''); ?>">
                            <small>Để trống nếu không có khuyến mãi.</small>
                        </div>

                        <div class="admin-form-group">
                            <label for="description">Mô tả:</label>
                            <textarea id="description" name="description" class="admin-form-control" rows="6" required><?php echo htmlspecialchars($_POST['description'] ?? ''); // Giữ lại giá trị cũ nếu có lỗi form ?></textarea>
                        </div>

                        <div class="admin-form-group">
                            <label for="category_id">Danh mục:</label>
                            <select id="category_id" name="category_id" class="admin-form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$category['id']) ? 'selected' : ''; // Giữ lại giá trị cũ ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="admin-form-group">
                            <label for="brand_id">Thương hiệu:</label>
                            <select id="brand_id" name="brand_id" class="admin-form-control" required>
                                <option value="">-- Chọn thương hiệu --</option>
                                <?php foreach ($brands as $brand): ?>
                                     <option value="<?php echo $brand['id']; ?>" <?php echo (isset($_POST['brand_id']) && (int)$_POST['brand_id'] === (int)$brand['id']) ? 'selected' : ''; // Giữ lại giá trị cũ ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="admin-form-group">
                            <label for="stock">Số lượng kho (*)</label>
                            <input type="number" id="stock" name="stock" required value="<?php echo htmlspecialchars($_POST['stock'] ?? '100'); ?>">
                        </div>

                        <div class="admin-form-group">
                            <label for="is_active">Trạng thái:</label>
                            <div>
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo isset($_POST['is_active']) || !$_SERVER['REQUEST_METHOD'] === 'POST' ? 'checked' : ''; // Mặc định check khi load trang hoặc khi submit có check ?>> 
                                <label for="is_active">Đang hoạt động</label>
                            </div>
                        </div>

                         <div class="admin-form-group">
                            <label for="product_images">Ảnh sản phẩm:</label>
                            <input type="file" id="product_images" name="product_images[]" class="admin-form-control" multiple accept="image/*">
                            <small>Chọn một hoặc nhiều file ảnh (JPG, JPEG, PNG, GIF), tối đa 5MB mỗi file.</small>
                        </div>

                        <div class="admin-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">Thêm sản phẩm</button>
                            <a href="products.php" class="admin-btn admin-btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 