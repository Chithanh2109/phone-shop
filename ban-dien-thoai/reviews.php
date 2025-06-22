<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// I need to get the database connection object
// The variable is likely named $conn from database.php
// I will assume it's available in this scope.

function getProductReviews($conn, $product_id, $is_admin = false) {
    // This function can be expanded later
    return [];
}

// Check if product_id is provided in the URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Kiểm tra đăng nhập (chỉ cần đăng nhập để gửi đánh giá, không cần để xem)
// Bạn có thể thêm check isLoggedIn() ở đây nếu muốn chỉ người dùng đăng nhập mới được gửi đánh giá

// Xử lý AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kiểm tra đăng nhập trước khi xử lý POST
        // Bỏ kiểm tra đăng nhập để cho phép người dùng chưa đăng nhập gửi đánh giá
        // if (!isLoggedIn()) {
        //      echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để gửi đánh giá!']);
        //      exit;
        // }
        
        $product_id_post = (int)$_POST['product_id'];
        $rating = (int)$_POST['rating'];
        $comment = sanitizeInput($_POST['comment']);
        
        // Lấy user_id nếu đã đăng nhập, ngược lại đặt là NULL
        $user_id = isLoggedIn() ? $_SESSION['user_id'] : NULL;
        
        // Basic validation server-side
        if ($product_id_post <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu đánh giá không hợp lệ!']);
            exit;
        }

        
        // Kiểm tra sản phẩm tồn tại
        $stmt_check_product = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt_check_product->bind_param("i", $product_id_post);
        $stmt_check_product->execute();
        $product_exists = $stmt_check_product->get_result()->fetch_assoc();
        $stmt_check_product->close();

        if (!$product_exists) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
            exit;
        }
        
        if ($user_id) {
            // Kiểm tra đã đánh giá chưa (chỉ áp dụng cho người dùng đã đăng nhập)
            $stmt_check_review = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
            $stmt_check_review->bind_param("ii", $user_id, $product_id_post);
            $stmt_check_review->execute();
            $review_exists = $stmt_check_review->get_result()->fetch_assoc();
            $stmt_check_review->close();

            if ($review_exists) {
                echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này!']);
                exit;
            }
        }
        
        // Thêm đánh giá
        $stmt_insert = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt_insert->bind_param("iiis", $user_id, $product_id_post, $rating, $comment);
        
        if ($stmt_insert->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cảm ơn bạn đã đánh giá! Đánh giá của bạn sẽ hiển thị sau khi được duyệt.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu đánh giá!']);
        }
        $stmt_insert->close();
        exit;
    }
}

// Fetch reviews - either for a specific product or latest overall reviews
$is_admin = isAdmin();
$product_info = null;

// Base query
$query = "
    SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    JOIN products p ON r.product_id = p.id 
";

$where_clauses = [];
$params = [];
$types = '';

// Fetch product info if a specific product page
if ($product_id) {
    $stmt_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product_info = $result_product->fetch_assoc();
    $stmt_product->close();
    
    if (!$product_info) {
        redirect('index.php');
    }
    
    // For a specific product, admin sees all, user sees approved
    $where_clauses[] = "r.product_id = ?";
    $params[] = $product_id;
    $types .= 'i';
    
    if (!$is_admin) {
        $where_clauses[] = "r.status = 'approved'";
    }
} else {
    // Main reviews page, admin sees all, user sees approved
    if (!$is_admin) {
        $where_clauses[] = "r.status = 'approved'";
    }
}

// Append WHERE clauses if they exist
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}
    
$query .= " ORDER BY r.created_at DESC";

// Limit for main reviews page if not a specific product and not admin
if (!$product_id && !$is_admin) {
     $query .= " LIMIT 20";
}

$stmt_reviews = $conn->prepare($query);
if (!empty($params)) {
    $stmt_reviews->bind_param($types, ...$params);
}
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();
$reviews = $result_reviews->fetch_all(MYSQLI_ASSOC);
$stmt_reviews->close();

// Calculate average rating for the specific product (only approved reviews)
$avg_rating = 0;
if ($product_id) {
    $stmt_avg = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ? AND status = 'approved'");
    $stmt_avg->bind_param("i", $product_id);
    $stmt_avg->execute();
    $result_avg = $stmt_avg->get_result()->fetch_assoc();
    $stmt_avg->close();
    
    if ($result_avg && $result_avg['avg_rating'] !== null) {
        $avg_rating = round($result_avg['avg_rating'], 1);
    }
}
    
$page_title = $product_id ? 'Đánh giá sản phẩm: ' . htmlspecialchars($product_info['name']) : 'Tất cả đánh giá';
require_once 'includes/header.php';
?>

<div class="container" style="margin: 40px auto; max-width: 800px;">
    <h2><?php echo htmlspecialchars($page_title); ?></h2>
    
    <?php if (!empty($reviews)): ?>
        <h3>Các đánh giá <?php echo $product_id ? 'sản phẩm này' : 'mới nhất'; ?> (<?php echo count($reviews); ?>)</h3>
        <?php if ($product_id && $avg_rating > 0): ?>
             <p>Điểm trung bình: <b><?php echo htmlspecialchars($avg_rating); ?></b> / 5</p>
        <?php endif; ?>

        <!-- Hiển thị danh sách đánh giá -->
    <div class="reviews-grid">
        <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                         <?php if ($review['product_image'] && !$product_id): // Show product image only on main reviews page ?>
                             <img src="<?php echo htmlspecialchars(getImageUrl($review['product_image'])); ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>" class="product-image">
                         <?php endif; ?>
            <div class="product-info">
                              <?php if (!$product_id): // Show product name only on main reviews page ?>
                                   <a href="product.php?id=<?php echo $review['product_id']; ?>" class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></a>
                              <?php endif; ?>
                        <div class="rating">
                                  <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                      <span>★</span>
                                <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="review-content">
                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                         <?php if ($review['status'] === 'pending' && isAdmin()): // Show status for admin ?>
                              <p style="color: orange; font-size: 0.9em;"><i>(Đang chờ duyệt)</i></p>
                         <?php endif; ?>
                    </div>
                    <div class="review-footer">
                        <span class="reviewer">Bởi: <?php echo htmlspecialchars($review['user_name'] ?? 'Người dùng ẩn danh'); ?></span>
                        <span class="review-date"><?php echo htmlspecialchars((new DateTime($review['created_at']))->format('d/m/Y H:i')); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
            </div>
            
    <?php elseif ($product_id): // No reviews for this product yet ?>
         <div class="no-reviews">
             Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên gửi đánh giá!
         </div>
    <?php else: // No overall reviews yet ?>
        <div class="no-reviews">
             Chưa có đánh giá nào được gửi.
        </div>
    <?php endif; ?>

        </div>

<?php require_once 'includes/footer.php'; ?> 