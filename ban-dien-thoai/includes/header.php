<?php
// Cấu hình UTF-8 cho tiếng Việt
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

$base_dir = __DIR__ . '/../'; // Định nghĩa lại biến $base_dir

require_once $base_dir . 'config/database.php';
require_once $base_dir . 'includes/functions.php';
initSession();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('site_name'); ?></title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Bạn có thể cần thêm liên kết thư viện icon ở đây (ví dụ: Font Awesome) -->
</head>
<body>
    <?php echo showMessage(); ?>
    <header class="fixed-header">
        <!-- Thanh trên cùng (Tùy chọn, giữ lại nếu muốn) -->
        <!--
        <div class="header-top">
            <div class="container">
                <div class="contact-info">
                    <span>Hotline: <?php echo getSetting('site_phone'); ?></span>
                    <span>Email: <?php echo getSetting('site_email'); ?></span>
                </div>
                <div class="user-menu">
                    <?php if (isLoggedIn()): ?>
                        <span>Xin chào, <?php echo htmlspecialchars(getCurrentUser()['name'] ?? ''); ?></span>
                        <?php if (isAdmin()): ?>
                            <a href="admin/">Quản trị</a>
                        <?php endif; ?>
                        <a href="profile.php">Tài khoản</a>
                        <a href="orders.php">Đơn hàng</a>
                        <a href="logout.php">Đăng xuất</a>
                    <?php else: ?>
                        <a href="login.php">Đăng nhập</a>
                        <a href="register.php">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        -->

        <!-- Header chính -->
        <div class="header-main-bar">
            <div class="header-content">
                <div class="header-icon-link">
                    <a href="index.php">
                        🏠
                        <span>Trang chủ</span>
                    </a>
                </div>
                <div class="header-icon-link">
                    <a href="products.php">
                        📱
                        <span>Sản phẩm</span>
                    </a>
                </div>
                 <div class="header-icon-link">
                    <a href="https://zalo.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('site_phone')); ?>" target="_blank">
                        📞
                        <span>Gọi mua hàng</span>
                        <span><?php echo getSetting('site_phone'); ?></span>
                    </a>
                </div>
                <!-- Thanh tìm kiếm đã được di chuyển đến đây -->
                <div class="search-bar">
                    <form action="index.php" method="GET">
                        <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                 <div class="header-icon-link">
                    <a href="orders.php">
                        📦
                        <span>Tra cứu đơn hàng</span>
                    </a>
                </div>
                 <div class="header-icon-link">
                    <a href="faq.php">
                        ❓
                        <span>Câu hỏi thường gặp</span>
                    </a>
                </div>
                 <div class="cart">
                     <a href="cart.php" class="cart-link">
                         🛒
                         <span>Giỏ hàng</span>
                         <?php if (get_cart_count() > 0): ?>
                             <span class="cart-count"><?php echo get_cart_count(); ?></span>
                         <?php endif; ?>
                     </a>
                 </div>
                <div class="header-icon-link user-account dropdown">
                     <?php if (isLoggedIn()): ?>
                        <a href="#" class="dropdown-toggle">
                            👤
                            <span>Tài khoản</span>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php">Thông tin tài khoản</a>
                            <a href="orders.php">Đơn hàng của tôi</a>
                            <?php /* if (isAdmin()): ?>
                                 <a href="admin/">Trang quản trị</a>
                            <?php endif; */ ?>
                            <a href="logout.php">Đăng xuất</a>
                        </div>
                     <?php else: ?>
                        <a href="#" class="dropdown-toggle">
                            👤
                            <span>Tài khoản</span>
                        </a>
                         <div class="dropdown-content">
                            <a href="login.php">Đăng nhập</a>
                            <a href="register.php">Đăng ký</a>
                        </div>
                     <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Thanh điều hướng (Tùy chọn, cho các liên kết bổ sung) -->
        <!-- Giữ lại cấu trúc thanh điều hướng gốc đã được comment để tham khảo -->
        <!--
        <div class="header-nav">
            <div class="container">
                <nav>
                    <ul>
                        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                        <li <?php if ($current_page == 'index.php' || $current_page == 'product.php') echo 'class="active"'; ?>><a href="index.php">Trang chủ</a></li>
                        <li <?php if ($current_page == 'reviews.php') echo 'class="active"'; ?>><a href="reviews.php">Đánh giá</a></li>
                        <li <?php if ($current_page == 'faq.php') echo 'class="active"'; ?>><a href="faq.php">Câu hỏi thường gặp</a></li>
                        <li <?php if ($current_page == 'contact.php') echo 'class="active"'; ?>><a href="contact.php">Liên hệ</a></li>
                    </ul>
                </nav>
            </div>
        </div>
        -->
    </header>
</body>
</html> 