<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';
initSession(); // Khởi tạo session ngay từ đầu

// Debug: Kiểm tra trạng thái session và nội dung
// echo "Debug: Session Status: " . session_status() . "<br>";
// echo "Debug: Session Content: <br>";
// print_r($_SESSION);
// echo "<br>";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT p.*, b.name as brand, c.name as category FROM products p 
    JOIN brands b ON p.brand_id = b.id 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "<h2>Sản phẩm không tồn tại!</h2>";
    exit;
}

// Lấy tất cả ảnh từ product_images
$product_images = getProductImages($product['id']);

// Nếu không có ảnh trong product_images, sử dụng ảnh từ bảng products làm ảnh chính
$main_image_source = !empty($product_images) ? $product_images[0]['image'] : $product['image'];

// Lấy cài đặt ảnh QR từ database
$qr_image_path = 'images/default_qr.jpg'; // Giá trị mặc định
$result_qr = $conn->query("SELECT value FROM settings WHERE key_name = 'qr_payment_image' LIMIT 1");
if ($result_qr && $result_qr->num_rows > 0) {
    $qr_setting = $result_qr->fetch_assoc();
    $qr_image_path = $qr_setting['value'] ?? $qr_image_path;
}

// Lấy đánh giá sản phẩm
$reviews = getProductReviews($product['id']);
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($total_rating / count($reviews), 1);
}

// Lấy sản phẩm liên quan (cùng danh mục hoặc thương hiệu)
$stmt = $conn->prepare("SELECT p.*, b.name as brand_name 
                       FROM products p 
                       LEFT JOIN brands b ON p.brand_id = b.id 
                       WHERE p.id != ? 
                       AND p.is_active = 1 
                       AND (p.category_id = ? OR p.brand_id = ?) 
                       ORDER BY RAND() 
                       LIMIT 4");
$stmt->bind_param('iii', $product['id'], $product['category_id'], $product['brand_id']);
$stmt->execute();
$result_related = $stmt->get_result();
$related_products = $result_related->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    if (!isLoggedIn()) {
        setMessage('error', 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng');
        redirect('login.php');
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity <= 0) {
        setMessage('error', 'Số lượng không hợp lệ');
        redirect("product.php?id={$product['id']}");
    } else {
        if (isset($_POST['buy_now'])) {
            // Xử lý nút Mua ngay
            if (add_to_cart($product['id'], $quantity)) {
                setMessage('success', 'Đã thêm sản phẩm vào giỏ hàng');
                redirect('checkout.php'); // Chuyển thẳng đến trang thanh toán
            } else {
                setMessage('error', 'Không thể thêm sản phẩm vào giỏ hàng');
                redirect("product.php?id={$product['id']}");
            }
        } else if (isset($_POST['add_to_cart'])) {
            // Xử lý nút Thêm vào giỏ
            if (add_to_cart($product['id'], $quantity)) {
                setMessage('success', 'Đã thêm sản phẩm vào giỏ hàng');
                redirect('cart.php'); // Chuyển đến trang giỏ hàng
            } else {
                setMessage('error', 'Không thể thêm sản phẩm vào giỏ hàng');
                redirect("product.php?id={$product['id']}");
            }
        }
    }
}

// Xử lý gửi đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {

    if (!isLoggedIn()) {
        setMessage('error', 'Vui lòng đăng nhập để đánh giá sản phẩm');
        redirect('login.php');
    }

    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($rating < 1 || $rating > 5) {
        setMessage('error', 'Vui lòng chọn số sao từ 1 đến 5');
    } elseif (empty($comment)) {
        setMessage('error', 'Vui lòng nhập nội dung đánh giá');
    } else {
        // Thêm đánh giá mới (không kiểm tra đã đánh giá chưa)
        $stmt_insert = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
        $userId = isLoggedIn() ? $_SESSION['user_id'] : NULL;
        $stmt_insert->bind_param('iiis', $userId, $product['id'], $rating, $comment);
        if ($stmt_insert->execute()) {
            setMessage('success', 'Cảm ơn bạn đã đánh giá sản phẩm');
            redirect("product.php?id={$product['id']}");
        } else {
            setMessage('error', 'Có lỗi xảy ra, vui lòng thử lại: ' . $stmt_insert->error);
        }
        $stmt_insert->close();
    }
}

// Sau đó mới include header hoặc xuất HTML
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Chi tiết sản phẩm</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main class="container product-detail-page">
    <div class="product-details-section">
        <div class="product-images-gallery">
            <div class="main-product-image-display">
                <img src="<?php echo htmlspecialchars(getImageUrl($main_image_source)); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="main-product-image" loading="lazy">
            </div>
            </div>
        </div>

    <!-- Thông tin sản phẩm -->
        <div class="product-info-section">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <?php if (!empty($product['description'])): ?>
          <div class="product-description">
            <h2>Chi tiết sản phẩm</h2>
            <div><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>
          </div>
        <?php endif; ?>
        <div class="product-brand-category">
            <span>Thương hiệu: <b><?php echo htmlspecialchars($product['brand']); ?></b></span> |
            <span>Danh mục: <b><?php echo htmlspecialchars($product['category']); ?></b></span>
        </div>
            <div class="product-rating-display">
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $avg_rating ? 'active' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span>(<?php echo count($reviews); ?> đánh giá)</span>
            </div>
                <div class="product-page-price">
                    <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                        <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                    <?php else: ?>
                        <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>
        <div class="product-page-stock <?php echo ($product['stock'] > 10) ? 'in-stock' : (($product['stock'] > 0) ? 'low-stock' : 'out-of-stock'); ?>">
                    <?php echo $product['stock'] > 0 ? "Còn lại: {$product['stock']} sản phẩm" : "Hết hàng"; ?>
                </div>
        <form method="post" class="add-to-cart-form">
            <div class="quantity-selector">
                <label for="quantity">Số lượng:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
            </div>
            <div class="product-action-buttons">
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg">🛒 Thêm vào giỏ</button>
                <button type="submit" name="buy_now" class="btn btn-secondary btn-lg">⚡ Mua ngay</button>
            </div>
        </form>
            <?php if (!empty($product['specifications'])): ?>
            <div class="product-specifications">
                <h3>Thông số kỹ thuật</h3>
                <div class="specs-grid">
                    <?php 
                    $specs = json_decode($product['specifications'], true);
                    if ($specs && is_array($specs)):
                        foreach ($specs as $key => $value): 
                    ?>
                        <div class="spec-item">
                            <span class="spec-label"><?php echo htmlspecialchars($key); ?>:</span>
                            <span class="spec-value"><?php echo htmlspecialchars($value); ?></span>
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
            <?php endif; ?>
        <?php if (!empty($product['features'])): ?>
            <div class="product-features">
                <h2>Tính năng nổi bật</h2>
                <div><?php echo nl2br(htmlspecialchars($product['features'])); ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($product['specs'])): ?>
            <div class="product-specs">
                <h2>Thông số kỹ thuật</h2>
                <div><?php echo nl2br(htmlspecialchars($product['specs'])); ?></div>
            </div>
        <?php endif; ?>
    </div>
   
    <div class="product-accessory-suggestion">
      <h2>Phụ kiện tặng kèm</h2>
      <ul>
        <li>Ốp lưng bảo vệ</li>
        <li>Kính cường lực</li>
        <li>Sạc nhanh chính hãng</li>
        <li>Tai nghe Bluetooth</li>
        <li>Cáp sạc dự phòng</li>
      </ul>
    </div>

    <!-- Phần đánh giá sản phẩm -->
    <div class="reviews-section-product-page">
        <h2>Đánh giá sản phẩm</h2>
            <form method="post" class="review-form-product-page">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <h3>Gửi đánh giá của bạn</h3>
                <div class="rating-input">
                    <label for="rating">Số sao:</label>
                    <select name="rating" id="rating" required>
                        <option value="">Chọn sao</option>
                        <option value="5">5 Sao - Tuyệt vời</option>
                        <option value="4">4 Sao - Rất tốt</option>
                        <option value="3">3 Sao - Tốt</option>
                        <option value="2">2 Sao - Tạm được</option>
                        <option value="1">1 Sao - Rất tệ</option>
                    </select>
                </div>
                <div class="comment-input">
                    <label for="comment">Bình luận:</label>
                    <textarea name="comment" id="comment" rows="4" required></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">Gửi đánh giá</button>
            </form>
        <?php if (empty($reviews)): ?>
            <p>Chưa có đánh giá nào cho sản phẩm này.</p>
        <?php else: ?>
            <h3>Các đánh giá khác (<?php echo count($reviews); ?>)</h3>
            <div class="existing-reviews">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <p><strong><?php echo htmlspecialchars($review['user_name'] ?? 'Ẩn danh'); ?></strong> - <?php echo htmlspecialchars((new DateTime($review['created_at']))->format('d/m/Y H:i')); ?></p>
                        <div class="stars">
                             <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                 <span>★</span>
                             <?php endfor; ?>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'includes/footer.php'; ?>
<script>
    function changeImage(src) {
        document.getElementById('mainImage').src = src;
    }

    function setRating(rating) {
        document.getElementById('rating').value = rating;
        const stars = document.querySelectorAll('.rating-input-product-page span');
        stars.forEach((star, index) => {
            star.classList.toggle('active', index < rating);
        });
    }
</script>
</body>
</html> 