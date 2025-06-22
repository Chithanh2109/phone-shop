# phone-shop
## 1. Chức năng chính của website
### a. Trang người dùng (khách hàng)
- **Trang chủ (`index.php`)**: Hiển thị danh sách sản phẩm điện thoại, các thông tin nổi bật.
- **Xem sản phẩm (`products.php`, `product.php`)**: Danh sách và chi tiết từng sản phẩm, hình ảnh, mô tả, giá.
- **Giỏ hàng (`cart.php`)**: Quản lý các sản phẩm đã chọn mua.
- **Thanh toán (`checkout.php`)**: Nhập thông tin giao hàng, chọn phương thức thanh toán.
- **Đơn hàng (`orders.php`, `order_detail.php`, `order_success.php`, `confirm_delivery.php`)**: Xem lịch sử đơn hàng, chi tiết từng đơn, xác nhận đã nhận hàng.
- **Đánh giá sản phẩm (`reviews.php`, `submit_review.php`)**: Xem và gửi đánh giá cho sản phẩm.
- **Tài khoản cá nhân (`login.php`, `register.php`, `profile.php`, `forgot_password.php`, `reset_password.php`, `logout.php`)**: Đăng nhập, đăng ký, xem/sửa thông tin cá nhân, quên mật khẩu, đăng xuất.
- **Trang thông tin (`about.php`, `contact.php`, `faq.php`, `privacy.php`)**: Giới thiệu, liên hệ, câu hỏi thường gặp, chính sách bảo mật.
### b. Trang quản trị (admin)
- **Quản lý sản phẩm (`admin/products.php`, `products_add.php`, `products_edit.php`, `products_delete.php`)**: Thêm, sửa, xóa, xem danh sách sản phẩm.
- **Quản lý đơn hàng (`admin/orders.php`, `order_edit.php`, `order_delete.php`, `order_detail.php`, `update_payment_status.php`, `online_payments.php`)**: Xem, sửa, xóa, cập nhật trạng thái đơn hàng, quản lý thanh toán online.
- **Quản lý người dùng (`admin/users.php`, `user_add.php`, `user_edit.php`, `user_delete.php`)**: Thêm, sửa, xóa, xem danh sách người dùng.
- **Quản lý đánh giá (`admin/reviews.php`, `review_delete.php`, `review_status.php`)**: Xem, duyệt, xóa đánh giá sản phẩm.
- **Quản lý FAQ (`admin/faq_manage.php`, `faq_add.php`, `faq_edit.php`, `faq_delete.php`)**: Thêm, sửa, xóa câu hỏi thường gặp.

---

## 2. Vận hành tổng thể

### a. Luồng người dùng thông thường
1. **Truy cập trang chủ** → Xem sản phẩm → Chọn sản phẩm → Thêm vào giỏ hàng.
2. **Vào giỏ hàng** → Kiểm tra sản phẩm → Tiến hành thanh toán.
3. **Điền thông tin giao hàng** → Chọn phương thức thanh toán → Đặt hàng.
4. **Nhận xác nhận đơn hàng** → Theo dõi trạng thái đơn hàng trong tài khoản.
5. **Sau khi nhận hàng** → Xác nhận đã nhận → Đánh giá sản phẩm.

### b. Luồng quản trị viên
1. **Đăng nhập trang admin**.
2. **Quản lý sản phẩm**: Thêm mới, cập nhật, xóa sản phẩm.
3. **Quản lý đơn hàng**: Xem chi tiết, cập nhật trạng thái, xử lý thanh toán.
4. **Quản lý người dùng**: Thêm, sửa, xóa tài khoản khách hàng.
5. **Quản lý đánh giá**: Duyệt, ẩn/hiện, xóa đánh giá không phù hợp.
6. **Quản lý FAQ**: Cập nhật các câu hỏi thường gặp.

---

## 3. Cấu trúc kỹ thuật

- **Cơ sở dữ liệu**: File `ban_dien_thoai.sql` chứa cấu trúc và dữ liệu mẫu.
- **Kết nối DB**: Thông qua `config/database.php`.
- **Tái sử dụng giao diện**: Các file trong `includes/` như `header.php`, `footer.php`, `functions.php`.
- **Tài nguyên tĩnh**: Ảnh sản phẩm trong `images/products/`, CSS trong `assets/css/` và JS trong `assets/js/`.
- **Phân quyền**: Trang admin nằm trong thư mục `admin/`, chỉ truy cập được khi đăng nhập với quyền quản trị.

---

## 4. Một số điểm nổi bật

- **Bảo mật**: Có chức năng quên mật khẩu, reset mật khẩu, xác nhận đơn hàng.
- **Thanh toán**: Hỗ trợ thanh toán online (có file `online_payments.php`).
- **Quản lý đánh giá**: Cho phép duyệt/xóa đánh giá, tránh spam.
- **FAQ**: Hệ thống câu hỏi thường gặp, dễ dàng cập nhật từ admin.

---
#công nghệ được sử dụng trong website này như sau:

---

## 1. Ngôn ngữ lập trình & Backend

- **PHP**  
  Toàn bộ các file xử lý logic, giao diện động đều sử dụng PHP (các file `.php` như `index.php`, `products.php`, `admin/*.php`, v.v.).  
  PHP là ngôn ngữ phía server, xử lý request, truy vấn cơ sở dữ liệu, render HTML động.
## 2. Cơ sở dữ liệu
- **MySQL**  
  File `ban_dien_thoai.sql` cho thấy website sử dụng MySQL để lưu trữ dữ liệu (sản phẩm, đơn hàng, người dùng, đánh giá, v.v.).  
  Kết nối tới MySQL được cấu hình trong `config/database.php`.
  - **server**  
  chạy bằng ứng dụng xampp
## 3. Frontend
- **HTML/CSS/JavaScript**  
  - Giao diện được xây dựng bằng HTML kết hợp với CSS (các file trong `assets/css/`, `admin/css/`, `css/`).
  - JavaScript được sử dụng để tăng tính tương tác cho trang web (file `assets/js/main.js`).
- **Hình ảnh**  
  - Ảnh sản phẩm và icon được lưu trong thư mục `images/products/`.
## 4. Quản trị & Phân quyền
- **Hệ thống phân quyền**  
  - Có phân biệt giữa người dùng thông thường và quản trị viên (các file trong `admin/` chỉ dành cho admin).
  - Xác thực đăng nhập, quản lý session (thường được xử lý trong các file PHP, có thể sử dụng session của PHP).
## 5. Công nghệ khác (có thể có)
- **Không thấy dấu hiệu sử dụng framework**  
  - Không có dấu hiệu sử dụng framework PHP hiện đại như Laravel, CodeIgniter, hay CMS như WordPress.  
  - Website này có vẻ là PHP thuần (thuần túy tự code).
- **Không thấy dấu hiệu sử dụng frontend framework**  
  - Không có file cấu hình cho React, Vue, Angular, v.v.  
  - JavaScript chủ yếu là thuần (vanilla JS).
## 6. Tóm tắt luồng vận hành công nghệ
1. **Trình duyệt gửi request** →  
2. **PHP xử lý request** (lấy dữ liệu từ MySQL, xử lý logic) →  
3. **Render HTML động** (chèn dữ liệu vào giao diện) →  
4. **Trả về trình duyệt** (hiển thị, CSS/JS tăng tương tác).
