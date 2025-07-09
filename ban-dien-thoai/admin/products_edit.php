<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
initSession();
requireAdmin(); // Chỉ cho phép admin truy cập

$page_title = 'Sửa Sản phẩm';
// Lấy thông tin admin hiện tại
$current_admin = getCurrentUser();

$message = '';
$product = null;
$product_images = [];
$upload_dir = '../images/products/';

// --- Lấy ID sản phẩm từ URL --- //
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    setMessage('danger', 'Không tìm thấy ID sản phẩm cần sửa.');
    redirect('products.php');
}

// --- Lấy thông tin sản phẩm hiện tại --- //
try {
    $stmt_product = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt_product->bind_param('i', $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();
    $stmt_product->close();

    if (!$product) {
        setMessage('danger', 'Sản phẩm không tồn tại.');
        redirect('products.php');
    }

    // Lấy ảnh sản phẩm
    $stmt_images = $conn->prepare("SELECT id, image FROM product_images WHERE product_id = ? ORDER BY id ASC");
    $stmt_images->bind_param('i', $product_id);
    $stmt_images->execute();
    $result_images = $stmt_images->get_result();
    $product_images = $result_images->fetch_all(MYSQLI_ASSOC);
    $stmt_images->close();

} catch (Exception $e) { // Catch mysqli errors
    setMessage('danger', 'Lỗi khi lấy thông tin sản phẩm: ' . $e->getMessage());
    redirect('products.php');
}

// --- Lấy danh sách Danh mục và Thương hiệu cho dropdown (giống trang thêm) --- //
$categories = [];
$brands = [];

try {
    $result_categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $result_categories->fetch_all(MYSQLI_ASSOC);

    $result_brands = $conn->query("SELECT id, name FROM brands ORDER BY name ASC");
    $brands = $result_brands->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message .= '<div class="admin-alert admin-alert-danger">Lỗi khi lấy danh mục/thương hiệu: ' . $e->getMessage() . '</div>';
}
// --- Kết thúc lấy danh sách --- //

// --- Xử lý khi form được submit --- //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) { // Đảm bảo có sản phẩm hợp lệ trước khi xử lý POST
    $name = sanitizeInput($_POST['name']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_INT);
    
    $sale_price = null;
    if (isset($_POST['sale_price']) && $_POST['sale_price'] !== '') {
        $sale_price = filter_var($_POST['sale_price'], FILTER_VALIDATE_INT);
    }

    $description = sanitizeInput($_POST['description']);
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT); // Validate int
    $brand_id = filter_var($_POST['brand_id'], FILTER_VALIDATE_INT); // Validate int
    $is_active = isset($_POST['is_active']) ? 1 : 0; // Checkbox
    $images_to_delete = isset($_POST['delete_images']) ? (array)$_POST['delete_images'] : []; // Lấy danh sách ID ảnh cần xóa

    // Validation cơ bản
    if (empty($name) || $price === false || empty($description) || $category_id === false || $brand_id === false) {
        $message = '<div class="admin-alert admin-alert-danger">Vui lòng điền đầy đủ và đúng định dạng các thông tin bắt buộc (Tên, Giá, Mô tả, Danh mục, Thương hiệu).</div>';
    } elseif ($sale_price === false) {
        $message = '<div class="admin-alert admin-alert-danger">Giá khuyến mãi không hợp lệ. Vui lòng nhập số nguyên.</div>';
    } elseif ($sale_price !== null && $sale_price >= $price) {
        $message = '<div class="admin-alert admin-alert-danger">Giá khuyến mãi phải nhỏ hơn giá gốc.</div>';
    } else {
        // Bắt đầu giao dịch (transaction)
        $conn->begin_transaction();
        $uploaded_image_files = []; // Mảng lưu tên file ảnh mới upload thành công

        try {
            // --- Cập nhật thông tin sản phẩm vào bảng `products` --- //
            $stmt_update_product = $conn->prepare("UPDATE products SET name = ?, price = ?, sale_price = ?, description = ?, category_id = ?, brand_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            // Types: s (name), i (price), i (sale_price), s (description), i (category_id), i (brand_id), i (is_active), i (id)
            $stmt_update_product->bind_param('siisiiii', $name, $price, $sale_price, $description, $category_id, $brand_id, $is_active, $product_id);
            if (!$stmt_update_product->execute()) {
                throw new Exception("Lỗi khi cập nhật sản phẩm: " . $stmt_update_product->error);
            }
            $stmt_update_product->close();

            // --- Xử lý Xóa ảnh cũ --- //
            if (!empty($images_to_delete)) {
                $placeholders = implode(',', array_fill(0, count($images_to_delete), '?'));
                $types = str_repeat('i', count($images_to_delete)) . 'i'; // types for IDs to delete + product_id
                $params = array_merge($images_to_delete, [$product_id]);
                
                // Lấy tên file của các ảnh sẽ bị xóa để xóa file vật lý
                $stmt_get_image_names = $conn->prepare("SELECT image FROM product_images WHERE id IN ($placeholders) AND product_id = ?");
                $stmt_get_image_names->bind_param($types, ...$params);
                $stmt_get_image_names->execute();
                $result_deleted_images = $stmt_get_image_names->get_result();
                $deleted_image_files = $result_deleted_images->fetch_all(MYSQLI_ASSOC); // fetch as assoc array
                $stmt_get_image_names->close();


                // Xóa bản ghi trong database
                $stmt_delete_images_db = $conn->prepare("DELETE FROM product_images WHERE id IN ($placeholders) AND product_id = ?");
                $stmt_delete_images_db->bind_param($types, ...$params);
                 if (!$stmt_delete_images_db->execute()) {
                    throw new Exception("Lỗi khi xóa ảnh khỏi database: " . $stmt_delete_images_db->error);
                }
                $stmt_delete_images_db->close();


                // Xóa file ảnh vật lý
                foreach ($deleted_image_files as $image_row) {
                    $file_path = $upload_dir . $image_row['image'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }
            // --- Kết thúc Xử lý Xóa ảnh cũ --- //

            // --- Xử lý Upload ảnh mới (tương tự trang thêm) --- //
             $upload_errors = [];
            if (isset($_FILES['new_product_images']) && !empty($_FILES['new_product_images']['name'][0])) {
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                $max_size = 5 * 1024 * 1024; // 5MB

                foreach ($_FILES['new_product_images']['name'] as $key => $image_name) {
                    $file_tmp = $_FILES['new_product_images']['tmp_name'][$key];
                    $file_size = $_FILES['new_product_images']['size'][$key];
                    $file_error = $_FILES['new_product_images']['error'][$key];
                    $file_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                    
                    // Kiểm tra lỗi upload
                    if ($file_error !== 0) {
                        $upload_errors[] = "Lỗi upload file '$image_name'. Mã lỗi: " . $file_error;
                        continue;
                    }

                    // Kiểm tra loại file
                    if (!in_array($file_ext, $allowed_types)) {
                        $upload_errors[] = "File '$image_name' có định dạng không hợp lệ. Chỉ cho phép JPG, JPEG, PNG, GIF.";
                        continue;
                    }

                    // Kiểm tra kích thước file
                    if ($file_size > $max_size) {
                        $upload_errors[] = "File '$image_name' có kích thước quá lớn (tối đa 5MB).";
                        continue;
                    }

                    // Tạo tên file duy nhất
                    $new_file_name = uniqid('', true) . '.' . $file_ext;
                    $target_file = $upload_dir . $new_file_name;

                    // Di chuyển file đã upload
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $uploaded_image_files[] = $new_file_name; // Lưu tên file để lưu vào DB
                    } else {
                        $upload_errors[] = "Lỗi khi di chuyển file đã upload '$image_name'.";
                    }
                }

                // Lưu đường dẫn ảnh mới vào bảng `product_images`
                if (!empty($uploaded_image_files)) {
                    $stmt_insert_images = $conn->prepare("INSERT INTO product_images (product_id, image) VALUES (?, ?)");
                    foreach ($uploaded_image_files as $url) {
                        $stmt_insert_images->bind_param('is', $product_id, $url);
                        if (!$stmt_insert_images->execute()) {
                            $upload_errors[] = "Lỗi khi lưu ảnh '$url' vào database: " . $stmt_insert_images->error;
                        }
                    }
                    $stmt_insert_images->close();
                }
                 // Gộp các lỗi upload vào message chung
                 if(!empty($upload_errors)){
                     $message .= '<div class="admin-alert admin-alert-warning">Có lỗi xảy ra khi xử lý ảnh mới:<br>-' . implode('<br>-', $upload_errors) . '</div>';
                 }

            }
            // --- Kết thúc Xử lý Upload ảnh mới --- //

            $conn->commit(); // Hoàn tất giao dịch
            setMessage('success', 'Cập nhật sản phẩm thành công.' . (!empty($upload_errors) ? ' (Có lỗi với một số ảnh mới)' : ''));
            redirect('products.php'); // Chuyển hướng về trang danh sách sản phẩm

        } catch (Exception $e) {
            $conn->rollback(); // Hoàn tác giao dịch nếu có lỗi
            $message = '<div class="admin-alert admin-alert-danger">Lỗi khi cập nhật sản phẩm vào database: ' . $e->getMessage() . '</div>';
             // Nếu có lỗi DB, xóa các file ảnh mới đã upload (nếu có)
             if (!empty($uploaded_image_files)){
                 foreach($uploaded_image_files as $url){
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
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Tạm dùng CSS chung -->
    <link rel="stylesheet" href="css/admin.css"> <!-- CSS riêng cho admin -->
</head>
<body>
    <div class="admin-wrapper">
        <!-- Admin Sidebar -->
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
                <?php echo showMessage(); ?>
                <?php echo $message; // Hiển thị lỗi/thông báo từ quá trình xử lý form ?>
                
                <h1><?php echo $page_title; ?>: <?php echo htmlspecialchars($product['name'] ?? ''); ?></h1>
                
                <div class="admin-form-container">
                    <form action="products_edit.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data"> <!-- Quan trọng: thêm enctype="multipart/form-data" cho form có upload file -->
                        
                        <div class="admin-form-group">
                            <label for="name">Tên sản phẩm:</label>
                            <input type="text" id="name" name="name" class="admin-form-control" required value="<?php echo htmlspecialchars($product['name'] ?? ''); // Điền dữ liệu cũ ?>">
                        </div>

                        <div class="admin-form-group">
                            <label for="price">Giá gốc (*)</label>
                            <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" step="1" required>
                        </div>

                        <div class="admin-form-group">
                            <label for="sale_price">Giá khuyến mãi</label>
                            <input type="number" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($product['sale_price'] ?? ''); ?>" step="1">
                            <small>Để trống nếu không có khuyến mãi.</small>
                        </div>

                        <div class="admin-form-group">
                            <label for="description">Mô tả chi tiết</label>
                            <textarea name="description" id="description" class="admin-form-control" rows="6"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="admin-form-group">
                            <label for="category_id">Danh mục:</label>
                            <select id="category_id" name="category_id" class="admin-form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($product['category_id']) && (int)$product['category_id'] === (int)$category['id']) ? 'selected' : ''; // Chọn danh mục cũ ?>>
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
                                     <option value="<?php echo $brand['id']; ?>" <?php echo (isset($product['brand_id']) && (int)$product['brand_id'] === (int)$brand['id']) ? 'selected' : ''; // Chọn thương hiệu cũ ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="admin-form-group">
                            <label for="is_active">Trạng thái hoạt động:</label>
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($product['is_active'] ?? 1) == 1 ? 'checked' : ''; ?>> Hiển thị sản phẩm trên website
                        </div>

                         <div class="admin-form-group">
                            <label>Ảnh hiện tại:</label>
                            <div class="product-images-preview">
                                <?php if (!empty($product_images)): ?>
                                    <?php foreach ($product_images as $image): ?>
                                        <div class="product-image-item">
                                            <img src="<?php echo getImageUrl($image['image']); ?>" alt="Product Image">
                                            <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>" id="delete_image_<?php echo $image['id']; ?>">
                                            <label for="delete_image_<?php echo $image['id']; ?>">Xóa</label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>Chưa có ảnh nào cho sản phẩm này.</p>
                                <?php endif; ?>
                            </div>
                         </div>

                         <div class="admin-form-group">
                            <label for="new_product_images">Thêm ảnh mới:</label>
                            <input type="file" id="new_product_images" name="new_product_images[]" class="admin-form-control" multiple accept="image/*">
                            <small>Chọn một hoặc nhiều file ảnh mới (JPG, JPEG, PNG, GIF), tối đa 5MB mỗi file.</small>
                        </div>

                        <div class="admin-form-group">
                            <label for="specs">Thông số kỹ thuật</label>
                            <textarea name="specs" id="specs" class="admin-form-control" rows="4"><?php echo htmlspecialchars($product['specs'] ?? ''); ?></textarea>
                        </div>

                        <div class="admin-form-group">
                            <label for="features">Tính năng nổi bật</label>
                            <textarea name="features" id="features" class="admin-form-control" rows="4"><?php echo htmlspecialchars($product['features'] ?? ''); ?></textarea>
                        </div>

                        <div class="admin-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">Lưu thay đổi</button>
                            <a href="products.php" class="admin-btn admin-btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>
    
</body>
</html> 