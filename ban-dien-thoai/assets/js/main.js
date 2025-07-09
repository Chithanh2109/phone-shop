// ========================================
// FILE: main.js - JavaScript chính cho website bán điện thoại
// Mô tả: Xử lý tương tác người dùng, giỏ hàng, đánh giá sản phẩm
// ========================================

// Khởi tạo giỏ hàng từ localStorage hoặc tạo mới nếu chưa có
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Cấu hình animation cho các hiệu ứng
const config = {
    duration: {
        fast: 200,      // Thời gian nhanh (200ms)
        normal: 300,    // Thời gian bình thường (300ms)
        slow: 1000      // Thời gian chậm (1000ms)
    },
    scale: {
        hover: 1.1,     // Tỷ lệ phóng to khi hover
        bounce: 1.2,    // Tỷ lệ bounce animation
        pulse: 1.1      // Tỷ lệ pulse animation
    }
};

// ========================================
// CHỨC NĂNG GIỎ HÀNG
// ========================================

/**
 * Thêm sản phẩm vào giỏ hàng
 * @param {number} productId - ID của sản phẩm cần thêm
 */
function addToCart(productId) {
    // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        // Nếu đã có thì tăng số lượng lên 1
        existingItem.quantity += 1;
    } else {
        // Nếu chưa có thì thêm mới với số lượng = 1
        cart.push({
            id: productId,
            quantity: 1
        });
    }
    // Lưu giỏ hàng và cập nhật hiển thị
    saveCart();
    updateCartCount();
    showNotification('Đã thêm sản phẩm vào giỏ hàng');
}

/**
 * Cập nhật số lượng sản phẩm hiển thị trên header
 */
function updateCartCount() {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        // Tính tổng số lượng tất cả sản phẩm trong giỏ hàng
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

/**
 * Lưu giỏ hàng vào localStorage
 */
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// ========================================
// CHỨC NĂNG ĐÁNH GIÁ SẢN PHẨM
// ========================================

/**
 * Gửi đánh giá sản phẩm lên server
 * @param {number} productId - ID của sản phẩm được đánh giá
 */
function submitReview(productId) {
    // Lấy giá trị rating và comment từ form
    const rating = document.querySelector('input[name="rating"]:checked')?.value;
    const comment = document.getElementById('review-comment').value;
    
    // Kiểm tra xem đã chọn rating chưa
    if (!rating) {
        alert('Vui lòng chọn số sao đánh giá!');
        return;
    }
    
    // Gửi request POST đến server
    fetch('reviews.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&rating=${rating}&comment=${encodeURIComponent(comment)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cảm ơn bạn đã đánh giá!');
            location.reload(); // Tải lại trang để hiển thị đánh giá mới
        } else {
            alert(data.message || 'Có lỗi xảy ra!');
        }
    });
}

// ========================================
// CHỨC NĂNG VALIDATION FORM
// ========================================

/**
 * Kiểm tra các input có rỗng không
 * @param {Array} inputIds - Mảng các ID của input cần kiểm tra
 * @returns {boolean} - true nếu có input rỗng, false nếu tất cả đều có dữ liệu
 */
function areInputsEmpty(inputIds) {
    for (const id of inputIds) {
        const inputElement = document.getElementById(id);
        if (!inputElement || inputElement.value.trim() === '') {
            return true; // Tìm thấy input rỗng
        }
    }
    return false; // Không có input rỗng nào
}

/**
 * Validate form đăng nhập
 * @returns {boolean} - true nếu form hợp lệ, false nếu không
 */
function validateLoginForm() {
    // Kiểm tra các trường bắt buộc
    if (areInputsEmpty(['username', 'password'])) {
        alert('Vui lòng điền đầy đủ thông tin Tên đăng nhập và Mật khẩu!');
        return false;
    }
    return true;
}

/**
 * Validate form đăng ký
 * @returns {boolean} - true nếu form hợp lệ, false nếu không
 */
function validateRegisterForm() {
    // Kiểm tra các trường bắt buộc
    if (areInputsEmpty(['username', 'password', 'confirm-password', 'email'])) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return false;
    }
    
    // Kiểm tra mật khẩu xác nhận có khớp không
    if (document.getElementById('password').value !== document.getElementById('confirm-password').value) {
        alert('Mật khẩu xác nhận không khớp!');
        return false;
    }
    
    // Kiểm tra email có hợp lệ không
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(document.getElementById('email').value)) {
        alert('Email không hợp lệ!');
        return false;
    }
    
    return true;
}

// ========================================
// CHỨC NĂNG TÌM KIẾM VÀ SẮP XẾP
// ========================================

/**
 * Tìm kiếm sản phẩm
 */
function searchProducts() {
    const searchInput = document.getElementById('search-input').value;
    if (searchInput.trim()) {
        // Chuyển hướng đến trang index với tham số tìm kiếm
        window.location.href = `index.php?search=${encodeURIComponent(searchInput)}`;
    }
}

/**
 * Sắp xếp sản phẩm theo tiêu chí
 * @param {string} value - Loại sắp xếp (price_asc, price_desc, name)
 */
function sortProducts(value) {
    const productGrid = document.querySelector('.product-grid');
    const products = Array.from(productGrid.children);

    // Sắp xếp mảng sản phẩm
    products.sort((a, b) => {
        const priceA = parseFloat(a.querySelector('.sale-price').textContent.replace(/[^\d]/g, ''));
        const priceB = parseFloat(b.querySelector('.sale-price').textContent.replace(/[^\d]/g, ''));
        const nameA = a.querySelector('h3').textContent;
        const nameB = b.querySelector('h3').textContent;

        switch (value) {
            case 'price_asc':
                return priceA - priceB; // Sắp xếp giá tăng dần
            case 'price_desc':
                return priceB - priceA; // Sắp xếp giá giảm dần
            case 'name':
                return nameA.localeCompare(nameB); // Sắp xếp theo tên
            default:
                return 0;
        }
    });

    // Sử dụng DocumentFragment để tối ưu hiệu suất
    const fragment = document.createDocumentFragment();
    products.forEach(product => fragment.appendChild(product));
    productGrid.appendChild(fragment);
}

/**
 * Chuyển trang trong phân trang
 * @param {number} page - Số trang muốn chuyển đến
 */
function changePage(page) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('page', page);
    window.location.href = currentUrl.toString();
}

// ========================================
// CHỨC NĂNG DANH SÁCH YÊU THÍCH
// ========================================

// Khởi tạo danh sách yêu thích từ localStorage
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

/**
 * Thêm/xóa sản phẩm khỏi danh sách yêu thích
 * @param {number} productId - ID của sản phẩm
 */
function addToWishlist(productId) {
    if (!wishlist.includes(productId)) {
        // Thêm vào danh sách yêu thích
        wishlist.push(productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        showNotification('Đã thêm sản phẩm vào danh sách yêu thích');
    } else {
        // Xóa khỏi danh sách yêu thích
        wishlist = wishlist.filter(id => id !== productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        showNotification('Đã xóa sản phẩm khỏi danh sách yêu thích');
    }
}

// ========================================
// HỆ THỐNG THÔNG BÁO
// ========================================

/**
 * Hiển thị thông báo cho người dùng
 * @param {string} message - Nội dung thông báo
 */
function showNotification(message) {
    // Tạo element thông báo
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    // Hiệu ứng xuất hiện
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    // Tự động ẩn sau 2 giây
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 2000);
}

// Thêm CSS cho thông báo
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #2ecc71;
        color: white;
        padding: 1rem 2rem;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 9999;
    }

    .notification.show {
        transform: translateY(0);
        opacity: 1;
    }
`;
document.head.appendChild(style); 

// ========================================
// CẬP NHẬT GIỎ HÀNG TỪ SERVER
// ========================================

/**
 * Cập nhật số lượng giỏ hàng từ server
 */
function updateCartCount() {
    fetch('cart.php?action=count')
    .then(res => res.json())
    .then(cartData => {
        const cartCountSpan = document.querySelector('.cart-count');
        if (cartCountSpan) {
            cartCountSpan.textContent = cartData.count;
        }
    });
}

// ========================================
// CÁC HÀM ANIMATION (SỬ DỤNG JQUERY)
// ========================================

/**
 * Khởi tạo hiệu ứng hover cho ảnh sản phẩm
 */
function initProductImageHover() {
    $('.product-image-container').hover(
        function() {
            // Khi hover vào - phóng to ảnh
            $(this).find('img').css({
                'transform': `scale(${config.scale.hover})`,
                'transition': 'transform 0.3s ease-in-out'
            });
        },
        function() {
            // Khi rời khỏi - trở về kích thước ban đầu
            $(this).find('img').css({
                'transform': 'scale(1)',
                'transition': 'transform 0.3s ease-in-out'
            });
        }
    );
}

/**
 * Khởi tạo chức năng chuyển đổi tab
 */
function initTabSwitching() {
    $('.tab-link').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const target = $this.data('tab');
        
        // Ẩn tab hiện tại và hiển thị tab mới
        $('.tab-content.active').fadeOut(config.duration.fast, function() {
            $(this).removeClass('active');
            $(target).fadeIn(config.duration.fast).addClass('active');
        });

        // Cập nhật trạng thái active của tab link
        $('.tab-link').removeClass('active');
        $this.addClass('active');
    });
}

/**
 * Khởi tạo animation khi thêm vào giỏ hàng
 */
function initCartAnimation() {
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();
        const $productCard = $(this).closest('.product-card');
        const $productImage = $productCard.find('.product-image img');
        const $cartIcon = $('.cart i');
        
        // Tạo ảnh bay từ sản phẩm đến giỏ hàng
        const $flyingImage = $('<img>')
            .attr('src', $productImage.attr('src'))
            .css({
                'position': 'fixed',
                'width': '50px',
                'height': '50px',
                'border-radius': '50%',
                'z-index': '9999',
                'object-fit': 'cover',
                'box-shadow': '0 0 10px rgba(0,0,0,0.2)'
            });

        const productOffset = $productImage.offset();
        const cartOffset = $cartIcon.offset();

        $('body').append($flyingImage);

        // Animation bay từ sản phẩm đến giỏ hàng
        $flyingImage.css({
            'top': productOffset.top + 'px',
            'left': productOffset.left + 'px'
        }).animate({
            'top': cartOffset.top + 'px',
            'left': cartOffset.left + 'px',
            'width': '20px',
            'height': '20px',
            'opacity': '0.5'
        }, config.duration.slow, function() {
            $(this).remove();
            animateCartIcon($cartIcon);
        });
    });
}

/**
 * Animation cho icon giỏ hàng
 * @param {jQuery} $cartIcon - Element icon giỏ hàng
 */
function animateCartIcon($cartIcon) {
    $cartIcon.addClass('cart-bounce');
    setTimeout(() => {
        $cartIcon.removeClass('cart-bounce');
    }, config.duration.normal);
}

/**
 * Khởi tạo điều khiển số lượng sản phẩm
 */
function initQuantityControls() {
    $('.quantity-btn').on('click', function() {
        const $input = $(this).siblings('.quantity-input');
        const currentVal = parseInt($input.val());
        
        if ($(this).hasClass('plus')) {
            // Tăng số lượng
            $input.val(currentVal + 1);
        } else if ($(this).hasClass('minus') && currentVal > 1) {
            // Giảm số lượng (tối thiểu = 1)
            $input.val(currentVal - 1);
        }
        
        animateQuantityInput($input);
    });
}

/**
 * Animation cho input số lượng
 * @param {jQuery} $input - Element input số lượng
 */
function animateQuantityInput($input) {
    $input.addClass('quantity-pulse');
    setTimeout(() => {
        $input.removeClass('quantity-pulse');
    }, config.duration.normal);
}

// ========================================
// KHỞI TẠO TẤT CẢ CHỨC NĂNG KHI TRANG LOAD XONG
// ========================================

$(document).ready(function() {
    // Khởi tạo các animation
    initProductImageHover();
    initTabSwitching();
    initCartAnimation();
    initQuantityControls();

    // Khởi tạo bộ lọc sản phẩm
    const brandFilter = document.getElementById('brandFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const priceFilter = document.getElementById('priceFilter');
    const searchInput = document.getElementById('searchInput');
    const productCards = document.querySelectorAll('.product-card');

    if (brandFilter || categoryFilter || priceFilter || searchInput) {
        /**
         * Lọc sản phẩm theo các tiêu chí
         */
        function filterProducts() {
            const brand = brandFilter ? brandFilter.value.toLowerCase() : '';
            const category = categoryFilter ? categoryFilter.value.toLowerCase() : '';
            const price = priceFilter ? priceFilter.value : '';
            const search = searchInput ? searchInput.value.toLowerCase() : '';

            productCards.forEach(card => {
                const productBrandElement = card.querySelector('.product-brand');
                const productCategoryElement = card.querySelector('.product-category');
                const productNameElement = card.querySelector('.product-name');
                const productPriceElement = card.querySelector('.product-price');
                
                if (!productBrandElement || !productCategoryElement || !productNameElement || !productPriceElement) {
                    return;
                }

                const productBrand = productBrandElement.textContent.toLowerCase();
                const productCategory = productCategoryElement.textContent.toLowerCase();
                const productName = productNameElement.textContent.toLowerCase();
                const priceText = productPriceElement.textContent;
                const productPrice = parseFloat(priceText.replace(/[^\d.]/g, ''));

                let show = true;

                // Lọc theo hãng
                if (brand && productBrand !== brand) show = false;
                // Lọc theo danh mục
                if (category && productCategory !== category) show = false;
                // Lọc theo giá
                if (price) {
                    if (price.includes('-')) {
                        const [min, max] = price.split('-').map(Number);
                        if (productPrice < min || productPrice > max) show = false;
                    } else if (price === '>100000000') {
                        if (productPrice <= 100000000) show = false;
                    }
                }
                // Lọc theo tên sản phẩm
                if (search && !productName.includes(search)) show = false;

                // Hiển thị hoặc ẩn sản phẩm
                card.style.display = show ? 'block' : 'none';
            });
        }

        // Gắn event listener cho các bộ lọc
        if (brandFilter) brandFilter.addEventListener('change', filterProducts);
        if (categoryFilter) categoryFilter.addEventListener('change', filterProducts);
        if (priceFilter) priceFilter.addEventListener('change', filterProducts);
        if (searchInput) searchInput.addEventListener('input', filterProducts);
        
        // Chạy lọc lần đầu
        filterProducts();
    }

    // Khởi tạo gallery ảnh sản phẩm
    const mainProductImage = document.querySelector('.main-product-image');
    const thumbnailImages = document.querySelectorAll('.thumbnail-image');

    if (mainProductImage && thumbnailImages.length > 0) {
        thumbnailImages.forEach(thumbnail => {
            if (thumbnail) {
                // Khi click vào ảnh nhỏ thì hiển thị ảnh lớn
                thumbnail.addEventListener('click', function() {
                    mainProductImage.src = this.src;
                });
            }
        });
    }
});