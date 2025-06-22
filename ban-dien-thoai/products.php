<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

initSession();

// Lấy các tham số từ URL
$search_query = $_GET['search'] ?? '';
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = $_GET['sort'] ?? 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12; // Số sản phẩm mỗi trang

// Build the SQL query to fetch products
$sql = "SELECT p.*, b.name as brand_name, c.name as category_name 
        FROM products p 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1";
$count_sql = "SELECT COUNT(p.id) FROM products p 
              LEFT JOIN brands b ON p.brand_id = b.id 
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.is_active = 1";
$params = [];
$types = '';

// Add search condition
if (!empty($search_query)) {
    $sql .= " AND p.name LIKE ?";
    $count_sql .= " AND p.name LIKE ?";
    $params[] = '%' . $search_query . '%';
    $types .= 's';
}

// Add brand filter
if ($brand_id > 0) {
    $sql .= " AND p.brand_id = ?";
    $count_sql .= " AND p.brand_id = ?";
    $params[] = $brand_id;
    $types .= 'i';
}

// Add category filter
if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $count_sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}

// Add price range filter
if ($min_price > 0) {
    $sql .= " AND p.price >= ?";
    $count_sql .= " AND p.price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}
if ($max_price > 0) {
    $sql .= " AND p.price <= ?";
    $count_sql .= " AND p.price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

// Get total products count before adding pagination
$stmt_count = $conn->prepare($count_sql);
if ($stmt_count) {
    if (!empty($types)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_products = $result_count->fetch_row()[0];
    $stmt_count->close();
} else {
    $total_products = 0;
}
$total_pages = ceil($total_products / $per_page);

// Add sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.name DESC";
        break;
    default: // newest
        $sql .= " ORDER BY p.created_at DESC";
}

// Add pagination
$offset = ($page - 1) * $per_page;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

// Get products
$products = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get all brands and categories for filters
$brands_result = $conn->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);
$categories_result = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm <?php echo !empty($search_query) ? "tìm kiếm: " . htmlspecialchars($search_query) : ""; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1><?php echo !empty($search_query) ? "Kết quả tìm kiếm cho: " . htmlspecialchars($search_query) : "Tất cả sản phẩm"; ?></h1>

        <!-- Filters Section -->
        <div class="filters-section">
            <form action="products.php" method="get" class="filters-form">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                
                <!-- Brand Filter -->
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

                <!-- Category Filter -->
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

                <!-- Price Range Filter -->
                <div class="filter-group">
                    <label for="min_price">Giá từ:</label>
                    <input type="number" name="min_price" id="min_price" value="<?php echo $min_price; ?>" min="0" step="100000">
                    <label for="max_price">đến:</label>
                    <input type="number" name="max_price" id="max_price" value="<?php echo $max_price; ?>" min="0" step="100000">
                </div>

                <!-- Sort Filter -->
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

        <?php if (empty($products)): ?>
            <p>Không tìm thấy sản phẩm nào phù hợp.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <img src="<?php echo htmlspecialchars(getImageUrl($product['image'])); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image"
                                 loading="lazy">
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-brand">Thương hiệu: <?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></p>
                        <p class="product-category">Danh mục: <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
                        <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn product-button">Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search_query); ?>&brand=<?php echo $brand_id; ?>&category=<?php echo $category_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&sort=<?php echo $sort; ?>" class="btn">Trang trước</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&brand=<?php echo $brand_id; ?>&category=<?php echo $category_id; ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&sort=<?php echo $sort; ?>" 
                           class="btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

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