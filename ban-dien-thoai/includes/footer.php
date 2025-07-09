    </main>

    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <!-- Hỗ trợ khách hàng -->
                <div class="footer-section customer-service">
                    <h4><span style="margin-right:6px;">💬</span>Hỗ trợ khách hàng</h4>
                    <ul class="footer-links">
                        <li><a href="orders.php">Tra cứu đơn hàng</a></li>
                        <li><a href="cart.php">Giỏ hàng</a></li>
                        <li><a href="profile.php">Tài khoản</a></li>
                        <li><a href="faq.php">Câu hỏi thường gặp</a></li>
                        <li><a href="about.php">Về chúng tôi</a></li>
                    </ul>
                </div>
                <!-- Thông tin liên hệ -->
                <div class="footer-section contact-info">
                    <h4><span style="margin-right:6px;">📞</span>Liên hệ</h4>
                    <ul class="footer-links">
                        <li><span>🏠 <?php echo getSetting('site_address'); ?></span></li>
                        <li><span>☎ <?php echo getSetting('site_phone'); ?></span></li>
                        <li><span>✉ <?php echo getSetting('site_email'); ?></span></li>
                        <li><span>🕒 Thứ 2 - Thứ bảy: 8:00 - 21:30</span></li>
                        <li><a href="https://maps.google.com/?q=<?php echo urlencode(getSetting('site_address')); ?>" target="_blank" title="Xem trên Google Maps">🗺️ Xem trên Google Maps</a></li>
                    </ul>
                </div>
                <!-- Mạng xã hội -->
                <div class="footer-section social-media">
                    <h4><span style="margin-right:6px;">🌐</span>Kết nối</h4>
                    <div class="footer-social-links">
                        <a href="#" title="Facebook" class="footer-social-icon" style="color:#1877f3"><svg width="22" height="22" fill="currentColor"><use href="#icon-facebook"></use></svg></a>
                        <a href="#" title="Zalo" class="footer-social-icon" style="color:#0088ff"><svg width="22" height="22" fill="currentColor"><use href="#icon-zalo"></use></svg></a>
                        <a href="#" title="Email" class="footer-social-icon" style="color:#e44d26"><svg width="22" height="22" fill="currentColor"><use href="#icon-mail"></use></svg></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="copyright">
            
                    </div>
                </div>
            </div>
        </div>

        <!-- Nút lên đầu trang -->
        <button id="back-to-top" class="back-to-top" title="Lên đầu trang">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
            </svg>
        </button>
        <!-- SVG icon sprite (ẩn) -->
        <svg style="display:none;">
            <symbol id="icon-facebook" viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 5 3.66 9.13 8.44 9.88v-6.99H7.9v-2.89h2.54V9.84c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.89h-2.34v6.99C18.34 21.13 22 17 22 12z"/></symbol>
            <symbol id="icon-zalo" viewBox="0 0 32 32"><circle cx="16" cy="16" r="16" fill="#0088ff"/><text x="8" y="22" font-size="12" fill="#fff">Zalo</text></symbol>
            <symbol id="icon-mail" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 2v.01L12 13 4 6.01V6h16zM4 20V8.99l8 6.99 8-6.99V20H4z"/></symbol>
        </svg>
    </footer>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/ban-dien-thoai/assets/js/main.js"></script>
    <script>
        // Nút lên đầu trang
        $(function(){
            var $btn = $('#back-to-top');
            $(window).on('scroll', function(){
                if ($(window).scrollTop() > 300) $btn.addClass('show');
                else $btn.removeClass('show');
            });
            $btn.on('click', function(){
                $('html, body').animate({scrollTop:0}, 600);
            });
        });
    </script>
</body>
</html> 