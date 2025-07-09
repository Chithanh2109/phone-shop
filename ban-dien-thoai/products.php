<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

initSession();

//   LẤY CÁC THAM SỐ TỪ URL     //
$search_query = $_GET['search'] ?? ''; // Từ khóa tìm kiếm
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0; // ID thương hiệu được chọn
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0; // ID danh mục được chọn
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0; // Giá tối thiểu
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0; // Giá tối đa
$sort = $_GET['sort'] ?? 'newest'; // Cách sắp xếp (mặc định: mới nhất)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Trang hiện tại
$per_page = 12; // Số sản phẩm hiển thị trên mỗi trang

//   XÂY DỰNG CÂU LỆNH SQL ĐỂ LẤY SẢN PHẨM   //
// Câu lệnh chính: Lấy tất cả thông tin sản phẩm + tên thương hiệu + tên danh mục
$sql = "SELECT p.*, b.name as brand_name, c.name as category_name 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1"; // Chỉ lấy sản phẩm đang hoạt động

// Câu lệnh đếm tổng số sản phẩm (để tính phân trang)
$count_sql = "SELECT COUNT(p.id) FROM products p 
              LEFT JOIN brands b ON p.brand_id = b.id 
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.is_active = 1";

$params = []; // Mảng chứa các tham số cho prepared statement
$types = ''; // Chuỗi chứa kiểu dữ liệu của các tham số

//   THÊM ĐIỀU KIỆN TÌM KIẾM     //
if (!empty($search_query)) {
    $sql .= " AND p.name LIKE ?"; // Tìm kiếm theo tên sản phẩm
    $count_sql .= " AND p.name LIKE ?";
    $params[] = '%' . $search_query . '%'; // Thêm wildcard để tìm kiếm linh hoạt
    $types .= 's'; // Kiểu string
}

//   THÊM BỘ LỌC THEO THƯƠNG HIỆU    //
if ($brand_id > 0) {
    $sql .= " AND p.brand_id = ?";
    $count_sql .= " AND p.brand_id = ?";
    $params[] = $brand_id;
    $types .= 'i'; // Kiểu integer
}

//   THÊM BỘ LỌC THEO DANH MỤC   //
if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $count_sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= 'i'; // Kiểu integer
}

//   THÊM BỘ LỌC THEO KHOẢNG GIÁ     //
if ($min_price > 0) {
    $sql .= " AND p.price >= ?"; // Giá từ min_price trở lên
    $count_sql .= " AND p.price >= ?";
    $params[] = $min_price;
    $types .= 'd'; // Kiểu decimal/float
}
if ($max_price > 0) {
    $sql .= " AND p.price <= ?"; // Giá đến max_price trở xuống
    $count_sql .= " AND p.price <= ?";
    $params[] = $max_price;
    $types .= 'd'; // Kiểu decimal/float
}

//   TÍNH TỔNG SỐ SẢN PHẨM VÀ SỐ TRANG   //
$stmt_count = $conn->prepare($count_sql);
if ($stmt_count) {
    if (!empty($types)) {
        $stmt_count->bind_param($types, ...$params); // Bind các tham số
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_products = $result_count->fetch_row()[0]; // Lấy số lượng sản phẩm
    $stmt_count->close();
} else {
    $total_products = 0;
}
$total_pages = ceil($total_products / $per_page); // Tính tổng số trang

// ===== THÊM ĐIỀU KIỆN SẮP XẾP ===== //
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC"; // Giá tăng dần
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC"; // Giá giảm dần
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC"; // Tên A-Z
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC"; // Tên Z-A
        break;
    default: // newest
        $sql .= " ORDER BY p.created_at DESC"; // Mới nhất trước
}

//   THÊM PHÂN TRANG     //
$offset = ($page - 1) * $per_page; // Tính vị trí bắt đầu
$sql .= " LIMIT ? OFFSET ?"; // Giới hạn số sản phẩm và vị trí bắt đầu
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii'; // Thêm 2 kiểu integer cho LIMIT và OFFSET

//   THỰC THI TRUY VẤN VÀ LẤY DỮ LIỆU SẢN PHẨM  
$products = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params); // Bind các tham số
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC); // Lấy tất cả dữ liệu dạng associative array
    } else {
        echo '<div style="color:red;">Lỗi truy vấn sản phẩm: ' . $conn->error . '</div>';
        $products = [];
    }
    $stmt->close();
} else {
    echo '<div style="color:red;">Lỗi prepare truy vấn sản phẩm: ' . $conn->error . '</div>';
    $products = [];
}

//   LẤY DANH SÁCH THƯƠNG HIỆU VÀ DANH MỤC CHO BỘ LỌC    //
// Lấy tất cả thương hiệu
$brands_result = $conn->query("SELECT * FROM brands ORDER BY name");
if ($brands_result) {
    $brands = $brands_result->fetch_all(MYSQLI_ASSOC);
} else {
    echo '<div style="color:red;">Lỗi truy vấn brands: ' . $conn->error . '</div>';
    $brands = [];
}

// Lấy tất cả danh mục
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
if ($categories_result) {
    $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
} else {
    echo '<div style="color:red;">Lỗi truy vấn categories: ' . $conn->error . '</div>';
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm <?php echo !empty($search_query) ? "tìm kiếm: " . htmlspecialchars($search_query) : ""; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1><?php echo !empty($search_query) ? "Kết quả tìm kiếm cho: " . htmlspecialchars($search_query) : "Tất cả sản phẩm"; ?></h1>

        <!--     PHẦN BỘ LỌC     -->
        <div class="filters-section">
            <form action="products.php" method="get" class="filters-form">
                <!-- Giữ lại từ khóa tìm kiếm hiện tại -->
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                
                <!-- Bộ lọc theo thương hiệu -->
                <div class="filter-group">
                    <label for="brand">Thương hiệu:</label>
                    <select name="brand" id="brand">
                        <option value="">Tất cả</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo $brand_id == $brand['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bộ lọc theo danh mục -->
                <div class="filter-group">
                    <label for="category">Danh mục:</label>
                    <select name="category" id="category">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bộ lọc theo khoảng giá -->
                <div class="filter-group">
                    <label for="min_price">Giá từ:</label>
                    <input type="number" name="min_price" id="min_price" value="<?php echo $min_price; ?>" min="0" step="100000">
                    <label for="max_price">đến:</label>
                    <input type="number" name="max_price" id="max_price" value="<?php echo $max_price; ?>" min="0" step="100000">
                </div>

                <!-- Bộ lọc sắp xếp -->
                <div class="filter-group">
                    <label for="sort">Sắp xếp theo:</label>
                    <select name="sort" id="sort">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Lọc</button>
                <a href="products.php" class="btn btn-secondary">Xóa bộ lọc</a>
            </form>
        </div>

        <!--  HIỂN THỊ SẢN PHẨM      -->
        <?php if (empty($products)): ?>
            <p>Không tìm thấy sản phẩm nào phù hợp.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php
                            // Lấy ảnh chính của sản phẩm từ bảng product_images
                            $stmt_image = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
                            $stmt_image->bind_param("i", $product['id']);
                            $stmt_image->execute();
                            $result_image = $stmt_image->get_result();
                            $main_image = $result_image->fetch_assoc();
                            $stmt_image->close();

                            // Xác định đường dẫn ảnh
                            if ($main_image && !empty($main_image['image'])) {
                                $image_url = 'images/products/' . $main_image['image']; // Ảnh từ bảng product_images
                            } elseif (!empty($product['image'])) {
                                $image_url = 'images/products/' . $product['image']; // Ảnh từ bảng products
                            } else {
                                $image_url = 'images/products/no-image.jpg'; // Ảnh mặc định
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-image"
                                 loading="lazy"> <!-- Lazy loading để tối ưu tốc độ -->
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-brand">Thương hiệu: <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></p>
                        <p class="product-category">Danh mục: <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
                        <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn product-button">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!--     PHÂN TRANG    -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <!-- Nút "Trang trước" -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search_query); ?>&brand=<?php echo $brand_id; ?>&category=<?php echo $category_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&sort=<?php echo $sort; ?>" class="btn">Trang trước</a>
                    <?php endif; ?>

                    <!-- Hiển thị các số trang -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&brand=<?php echo $brand_id; ?>&category=<?php echo $category_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&sort=<?php echo $sort; ?>" 
                           class="btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Nút "Trang sau" -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search_query); ?>&brand=<?php echo $brand_id; ?>&category=<?php echo $category_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&sort=<?php echo $sort; ?>" class="btn">Trang sau</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html> 