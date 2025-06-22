    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Về chúng tôi</h3>
                    <p><?php echo getSetting('site_description'); ?></p>
                    <p><a href="about.php" style="color: inherit; text-decoration: none;">Tìm hiểu thêm</a></p>
                </div>
                <div class="footer-section">
                    <h3>Liên hệ</h3>
                    <p>Địa chỉ: <?php echo getSetting('site_address'); ?></p>
                    <p>Điện thoại: <?php echo getSetting('site_phone'); ?></p>
                    <p>Email: <?php echo getSetting('site_email'); ?></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name'); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Include jQuery from a CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <script src="/ban-dien-thoai/assets/js/main.js"></script>
</body>
</html> 