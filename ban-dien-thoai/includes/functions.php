<?php
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Các hàm liên quan đến Session
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;
    
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

function logout() {
    // Lưu giỏ hàng vào cookie trước khi đăng xuất (ngay cả khi rỗng)
    setcookie('saved_cart', isset($_SESSION['cart']) ? json_encode($_SESSION['cart']) : json_encode([]), time() + 3600*24*7, '/');
    session_destroy();
    redirect('login.php');
}

// Các hàm Input/Output
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

function getImageUrl($image) {
    if (empty($image)) {
        return '/ban-dien-thoai/images/no-image.jpg';
    }

    $base_url = '/ban-dien-thoai/';

    // Nếu đường dẫn đã là URL đầy đủ
    if (strpos($image, 'http://') === 0 || strpos($image, 'https://') === 0) {
        return $image;
    }

    // Nếu đường dẫn đã bao gồm /ban-dien-thoai/
    if (strpos($image, '/ban-dien-thoai/') === 0) {
        return $image;
    }

    // Nếu đường dẫn bắt đầu với images/
    if (strpos($image, 'images/') === 0) {
        return $base_url . $image;
    }

    // Nếu là tên file đơn lẻ, thêm vào thư mục products
    return $base_url . 'images/products/' . $image;
}

// Các hàm liên quan đến Database
function getUserById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

function getProductById($id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT p.*, b.name as brand_name, c.name as category_name 
                           FROM products p 
                           LEFT JOIN brands b ON p.brand_id = b.id 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $product;
}

// Các hàm liên quan đến Thông báo
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function showMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message']['type'];
        $text = $_SESSION['message']['text'];
        unset($_SESSION['message']);
        return "<div class='alert alert-{$type}'>{$text}</div>";
    }
    return '';
}

// Các hàm Chuyển hướng
function redirect($url) {
    header("Location: $url");
    exit();
}

function requireAdmin() {
    if (!isAdmin()) {
        setMessage('error', 'Bạn không có quyền truy cập trang này');
        redirect('/ban-dien-thoai/login.php');
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        setMessage('error', 'Vui lòng đăng nhập để tiếp tục');
        redirect('/ban-dien-thoai/login.php');
    }
}

// Các hàm Bảo mật
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Các hàm Upload file
function uploadImage($file, $target_dir = 'images/products/') {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($file['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Kiểm tra file có phải là ảnh
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['error' => 'File không phải là ảnh'];
    }
    
    // Kiểm tra kích thước file
    if ($file['size'] > 5000000) {
        return ['error' => 'File quá lớn (tối đa 5MB)'];
    }
    
    // Kiểm tra định dạng file
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['error' => 'Chỉ chấp nhận file JPG, JPEG, PNG & GIF'];
    }
    
    // Tạo tên file mới
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    }
    
    return ['error' => 'Có lỗi xảy ra khi upload file'];
}

// Các hàm xử lý chuỗi
function createSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = preg_replace('/[^a-z0-9-\s]/', '', $str);
    $str = preg_replace('/([\s]+)/', '-', $str);
    return trim($str, '-');
}

// Các hàm liên quan đến Sản phẩm
function getSpecifications($specs) {
    if (empty($specs)) return [];
    return json_decode($specs, true);
}

function isInWishlist($product_id) {
    global $conn;
    if (!isLoggedIn()) return false;
    
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['user_id'], $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];
    mysqli_stmt_close($stmt);
    return $count > 0;
}

function getProductReviews($product_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "
        SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? AND r.status = 'approved'
        ORDER BY r.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $reviews;
}

function getAverageRating($product_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "
        SELECT AVG(rating) as avg_rating 
        FROM reviews 
        WHERE product_id = ? AND status = 'approved'
    ");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return round($data['avg_rating'] ?? 0, 1);
}

function getProductImages($product_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $images = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $images;
}

// Các hàm Cài đặt
function getSetting($key) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT value FROM settings WHERE key_name = ?");
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $setting = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $setting['value'] ?? null;
}

// ==== Giỏ hàng thuần PHP ====
function add_to_cart($product_id, $quantity = 1) {
    global $conn;
    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    // Lấy thông tin sản phẩm từ database
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, image FROM products WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$product) {
        return false; // Sản phẩm không tồn tại hoặc đã bị vô hiệu hóa
    }
    // Nếu đã có và là số (scalar) thì chuyển thành mảng đúng cấu trúc
    if (isset($_SESSION['cart'][$product_id]) && !is_array($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => (int)$_SESSION['cart'][$product_id] + $quantity
        ];
    } elseif (isset($_SESSION['cart'][$product_id])) {
        // Nếu đã có, cập nhật số lượng
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        // Nếu chưa có, thêm mới vào giỏ hàng
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity
        ];
    }
    // Lưu giỏ hàng vào cookie
    setcookie('saved_cart', json_encode($_SESSION['cart']), time() + 3600*24*30, '/'); // Lưu trong 30 ngày
    return true;
}

function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        // Cập nhật cookie sau khi xóa
        setcookie('saved_cart', json_encode($_SESSION['cart']), time() + 3600*24*30, '/');
    }
}

function update_cart_quantity($product_id, $quantity) {
    if (isset($_SESSION['cart'][$product_id])) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        // Cập nhật cookie sau khi thay đổi số lượng
        setcookie('saved_cart', json_encode($_SESSION['cart']), time() + 3600*24*30, '/');
    }
}

function get_cart_items() {
    // Nếu chưa có giỏ hàng trong session nhưng có trong cookie
    if (!isset($_SESSION['cart']) && isset($_COOKIE['saved_cart'])) {
        $_SESSION['cart'] = json_decode($_COOKIE['saved_cart'], true);
    }
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    // Lọc bỏ các phần tử không phải mảng (tránh lỗi warning)
    $filtered = [];
    foreach ($cart as $k => $item) {
        if (is_array($item) && isset($item['id'], $item['name'], $item['price'], $item['quantity'])) {
            $filtered[$k] = $item;
        }
    }
    return $filtered;
}

function get_cart_count() {
    $count = 0;
    $cart = get_cart_items(); // Sử dụng get_cart_items để đảm bảo đồng bộ với cookie
    foreach ($cart as $item) {
            $count += $item['quantity'];
    }
    return $count;
}

function get_cart_total() {
    $total = 0;
    $cart = get_cart_items(); // Sử dụng get_cart_items để đảm bảo đồng bộ với cookie
    foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Thêm hàm mới để khôi phục giỏ hàng từ cookie khi đăng nhập
function restore_cart_from_cookie() {
    if (isset($_COOKIE['saved_cart'])) {
        $saved_cart = json_decode($_COOKIE['saved_cart'], true);
        if (is_array($saved_cart)) {
            $_SESSION['cart'] = $saved_cart;
        }
    }
}
?> 