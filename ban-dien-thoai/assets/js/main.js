// Xử lý giỏ hàng
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Animation configurations
const config = {
    duration: {
        fast: 200,
        normal: 300,
        slow: 1000
    },
    scale: {
        hover: 1.1,
        bounce: 1.2,
        pulse: 1.1
    }
};

// Cart Functions
function addToCart(productId) {
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            quantity: 1
        });
    }
    saveCart();
    updateCartCount();
    showNotification('Đã thêm sản phẩm vào giỏ hàng');
}

// Cập nhật số lượng giỏ hàng trên header
function updateCartCount() {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Xử lý đánh giá sản phẩm
function submitReview(productId) {
    const rating = document.querySelector('input[name="rating"]:checked')?.value;
    const comment = document.getElementById('review-comment').value;
    
    if (!rating) {
        alert('Vui lòng chọn số sao đánh giá!');
        return;
    }
    
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
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra!');
        }
    });
}

// Helper function to check if inputs are empty
function areInputsEmpty(inputIds) {
    for (const id of inputIds) {
        const inputElement = document.getElementById(id);
        if (!inputElement || inputElement.value.trim() === '') {
            // Optionally show a specific message here, or rely on the calling function
            return true; // Found an empty input
        }
    }
    return false; // No empty inputs found
}

// Xử lý form đăng nhập
function validateLoginForm() {
    // Sử dụng hàm helper để kiểm tra các trường bắt buộc
    if (areInputsEmpty(['username', 'password'])) {
        alert('Vui lòng điền đầy đủ thông tin Tên đăng nhập và Mật khẩu!');
        return false;
    }
    
    return true;
}

// Xử lý form đăng ký
function validateRegisterForm() {
    // Sử dụng hàm helper để kiểm tra các trường bắt buộc
    if (areInputsEmpty(['username', 'password', 'confirm-password', 'email'])) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return false;
    }
    
    if (document.getElementById('password').value !== document.getElementById('confirm-password').value) {
        alert('Mật khẩu xác nhận không khớp!');
        return false;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(document.getElementById('email').value)) {
        alert('Email không hợp lệ!');
        return false;
    }
    
    return true;
}

// Xử lý tìm kiếm sản phẩm
function searchProducts() {
    const searchInput = document.getElementById('search-input').value;
    if (searchInput.trim()) {
        window.location.href = `index.php?search=${encodeURIComponent(searchInput)}`;
    }
}

// Xử lý sắp xếp sản phẩm
function sortProducts(value) {
    const productGrid = document.querySelector('.product-grid');
    const products = Array.from(productGrid.children);

    products.sort((a, b) => {
        const priceA = parseFloat(a.querySelector('.sale-price').textContent.replace(/[^\d]/g, ''));
        const priceB = parseFloat(b.querySelector('.sale-price').textContent.replace(/[^\d]/g, ''));
        const nameA = a.querySelector('h3').textContent;
        const nameB = b.querySelector('h3').textContent;

        switch (value) {
            case 'price_asc':
                return priceA - priceB;
            case 'price_desc':
                return priceB - priceA;
            case 'name':
                return nameA.localeCompare(nameB);
            default:
                return 0;
        }
    });

    // Tối ưu hóa: Sử dụng DocumentFragment để thêm các phần tử đã sắp xếp
    const fragment = document.createDocumentFragment();
    products.forEach(product => fragment.appendChild(product));
    productGrid.appendChild(fragment); // Thêm toàn bộ fragment vào DOM trong 1 thao tác
}

// Xử lý phân trang
function changePage(page) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('page', page);
    window.location.href = currentUrl.toString();
}

// Wishlist functionality
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

function addToWishlist(productId) {
    if (!wishlist.includes(productId)) {
        wishlist.push(productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        showNotification('Đã thêm sản phẩm vào danh sách yêu thích');
    } else {
        wishlist = wishlist.filter(id => id !== productId);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
        showNotification('Đã xóa sản phẩm khỏi danh sách yêu thích');
    }
}

// Notification system
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 100);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 2000);
}

// Add notification styles
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
    }

    .notification.show {
        transform: translateY(0);
        opacity: 1;
    }
`;
document.head.appendChild(style); 

// --- Cart Page JavaScript --- //

// Cập nhật hàm updateQuantity để chỉ cập nhật số lượng hiển thị mà không tải lại trang
// function updateQuantity(productId, newQuantity) {
// ... removed code ...
// }

// function removeItem(productId) {
// ... removed code ...
// }

// Hàm mới để cập nhật tổng tiền hiển thị trên trang giỏ hàng
// function updateCartTotalDisplay() {
// ... removed code ...
// }

// Basic currency formatting function (adapt as needed)
// function formatCurrency(amount) {
// ... removed code ...
// }

// Cập nhật số lượng hiển thị trên header (AJAX call)
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

// --- End Cart Page JavaScript --- //

// Animation Functions
function initProductImageHover() {
    $('.product-image-container').hover(
        function() {
            $(this).find('img').css({
                'transform': `scale(${config.scale.hover})`,
                'transition': 'transform 0.3s ease-in-out'
            });
        },
        function() {
            $(this).find('img').css({
                'transform': 'scale(1)',
                'transition': 'transform 0.3s ease-in-out'
            });
        }
    );
}

function initTabSwitching() {
    $('.tab-link').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const target = $this.data('tab');
        
        $('.tab-content.active').fadeOut(config.duration.fast, function() {
            $(this).removeClass('active');
            $(target).fadeIn(config.duration.fast).addClass('active');
        });

        $('.tab-link').removeClass('active');
        $this.addClass('active');
    });
}

function initCartAnimation() {
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();
        const $productCard = $(this).closest('.product-card');
        const $productImage = $productCard.find('.product-image img');
        const $cartIcon = $('.cart i');
        
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

function animateCartIcon($cartIcon) {
    $cartIcon.addClass('cart-bounce');
    setTimeout(() => {
        $cartIcon.removeClass('cart-bounce');
    }, config.duration.normal);
}

function initQuantityControls() {
    $('.quantity-btn').on('click', function() {
        const $input = $(this).siblings('.quantity-input');
        const currentVal = parseInt($input.val());
        
        if ($(this).hasClass('plus')) {
            $input.val(currentVal + 1);
        } else if ($(this).hasClass('minus') && currentVal > 1) {
            $input.val(currentVal - 1);
        }
        
        animateQuantityInput($input);
    });
}

function animateQuantityInput($input) {
    $input.addClass('quantity-pulse');
    setTimeout(() => {
        $input.removeClass('quantity-pulse');
    }, config.duration.normal);
}

// Initialize all functionality when document is ready
$(document).ready(function() {
    // Initialize animations
    initProductImageHover();
    initTabSwitching();
    initCartAnimation();
    initQuantityControls();

    // Initialize product filtering
    const brandFilter = document.getElementById('brandFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const priceFilter = document.getElementById('priceFilter');
    const searchInput = document.getElementById('searchInput');
    const productCards = document.querySelectorAll('.product-card');

    if (brandFilter || categoryFilter || priceFilter || searchInput) {
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

                if (brand && productBrand !== brand) show = false;
                if (category && productCategory !== category) show = false;
                if (price) {
                    if (price.includes('-')) {
                        const [min, max] = price.split('-').map(Number);
                        if (productPrice < min || productPrice > max) show = false;
                    } else if (price === '>100000000') {
                        if (productPrice <= 100000000) show = false;
                    }
                }
                if (search && !productName.includes(search)) show = false;

                card.style.display = show ? 'block' : 'none';
            });
        }

        if (brandFilter) brandFilter.addEventListener('change', filterProducts);
        if (categoryFilter) categoryFilter.addEventListener('change', filterProducts);
        if (priceFilter) priceFilter.addEventListener('change', filterProducts);
        if (searchInput) searchInput.addEventListener('input', filterProducts);
        
        filterProducts();
    }

    // Initialize product image gallery
    const mainProductImage = document.querySelector('.main-product-image');
    const thumbnailImages = document.querySelectorAll('.thumbnail-image');

    if (mainProductImage && thumbnailImages.length > 0) {
        thumbnailImages.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                mainProductImage.src = this.src;
            });
        });
    }
});