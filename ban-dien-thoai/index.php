<?php
// Cấu hình cho tiếng Việt
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

// Xử lý tìm kiếm và sắp xếp
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12; // Giảm số lượng sản phẩm mỗi trang để tối ưu hiển thị

// Xây dựng câu truy vấn
$query = "SELECT p.*, b.name as brand_name, c.name as category_name 
          FROM products p 
          LEFT JOIN brands b ON p.brand_id = b.id 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.is_active = 1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// Sắp xếp
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'price_asc':
        $order_by = "p.price ASC";
        break;
    case 'price_desc':
        $order_by = "p.price DESC";
        break;
    case 'name':
        $order_by = "p.name ASC";
        break;
}
$query .= " ORDER BY " . $order_by;

// Phân trang
$offset = ($page - 1) * $per_page;
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

// Thực thi truy vấn với mysqli
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $products = [];
}

// Đếm tổng số sản phẩm cho phân trang
$count_query = "SELECT COUNT(*) as total FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.is_active = 1";
$count_params = [];
$count_types = "";

if ($search) {
    $count_query .= " AND (p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ?)";
    $search_param = "%$search%";
    $count_params = [$search_param, $search_param, $search_param];
    $count_types = "sss";
}

$count_stmt = mysqli_prepare($conn, $count_query);
if ($count_stmt) {
    if (!empty($count_params)) {
        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_products = mysqli_fetch_assoc($count_result)['total'] ?? 0;
    mysqli_stmt_close($count_stmt);
} else {
    $total_products = 0;
}

$total_pages = ceil($total_products / $per_page);

$first_item = $offset + 1;
$last_item = min($offset + $per_page, $total_products);

// Lấy danh sách các hãng điện thoại
$brands = [];
$brand_query = "SELECT id, name FROM brands ORDER BY name ASC";
$brand_result = mysqli_query($conn, $brand_query);
if ($brand_result) {
    while ($row = mysqli_fetch_assoc($brand_result)) {
        $brands[] = $row;
    }
}
// Lấy danh sách các danh mục sản phẩm
$categories = [];
$category_query = "SELECT id, name FROM categories ORDER BY name ASC";
$category_result = mysqli_query($conn, $category_query);
if ($category_result) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row;
    }
}

$page_title = 'Trang chủ';
?>

<body class="homepage">
    <?php include 'includes/header.php'; ?>

    <main class="index-main">
        <div class="container">
        <!-- Danh mục sản phẩm -->
        <div class="category-categories" style="margin-bottom: 24px;">
            <strong>Danh mục sản phẩm:</strong>
            <?php foreach ($categories as $category): ?>
                <a href="products.php?category_id=<?php echo $category['id']; ?>" class="btn btn-outline" style="margin: 4px 4px 4px 0;">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- Danh mục các hãng điện thoại -->
        <div class="brand-categories" style="margin-bottom: 16px;">
            <strong>Hãng điện thoại:</strong>
            <?php foreach ($brands as $brand): ?>
                <a href="products.php?brand_id=<?php echo $brand['id']; ?>" class="btn btn-outline" style="margin: 4px 4px 4px 0;">
                    <?php echo htmlspecialchars($brand['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Hiển thị tổng số sản phẩm và số sản phẩm trên mỗi trang -->
        <p class="total-products-info" style="margin-bottom: 18px; color: #29467c; font-weight: 500; font-size: 1.08rem;">
            Đang xem <b><?php echo $first_item; ?>–<?php echo $last_item; ?></b> trên tổng <b><?php echo $total_products; ?></b> sản phẩm
        </p>

        <!-- Danh sách sản phẩm -->
        <div class="product-grid">
            <?php if (empty($products)): ?>
                <div class="alert alert-error">Không tìm thấy sản phẩm nào.</div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php 
                                // Lấy ảnh từ bảng product_images với sort_order
                                $stmt_image = mysqli_prepare($conn, "
                                    SELECT image 
                                    FROM product_images 
                                    WHERE product_id = ? 
                                    ORDER BY sort_order ASC, id ASC 
                                    LIMIT 1
                                ");
                                if ($stmt_image) {
                                    mysqli_stmt_bind_param($stmt_image, "i", $product['id']);
                                    mysqli_stmt_execute($stmt_image);
                                    $result_image = mysqli_stmt_get_result($stmt_image);
                                    $main_product_image_data = mysqli_fetch_assoc($result_image);
                                    mysqli_stmt_close($stmt_image);
                                } else {
                                    $main_product_image_data = null;
                                }
                                
                                // Xác định URL hình ảnh
                                if ($main_product_image_data && !empty($main_product_image_data['image'])) {
                                    $image_url = getImageUrl($main_product_image_data['image']);
                                } elseif (!empty($product['image'])) {
                                    $image_url = getImageUrl($product['image']);
                                } else {
                                    $image_url = getImageUrl('no-image.jpg');
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image" 
                                 loading="lazy"
                                 onerror="this.src='<?php echo getImageUrl('no-image.jpg'); ?>'">
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></p>
                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        
                        <div class="product-price">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                <span class="original-price" style="text-decoration: line-through; color: #888; margin-left: 8px;">
                                    <?php echo formatPrice($product['price']); ?>
                                </span>
                            <?php else: ?>
                                <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="product-stock <?php 
                            echo $product['stock'] > 10 ? 'in-stock' : 
                                ($product['stock'] > 0 ? 'low-stock' : 'out-of-stock'); ?>">
                            <?php 
                            if($product['stock'] > 10) {
                                echo "Còn hàng";
                            } elseif($product['stock'] > 0) {
                                echo "Sắp hết hàng";
                            } else {
                                echo "Hết hàng";
                            }
                            ?>
                        </div>

                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                            Chi tiết
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="btn">
                        &laquo; Trang trước
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                       class="btn <?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="btn">
                        Trang sau &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>