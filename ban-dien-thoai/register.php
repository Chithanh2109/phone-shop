<?php
// require_once 'includes/header.php'; // Move this line below
require_once 'config/database.php';
require_once 'includes/functions.php';

// Khởi tạo session nếu chưa được khởi tạo trong header.php
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }
// Hàm initSession() trong functions.php nên xử lý việc này

initSession(); // Đảm bảo session được khởi tạo

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if (isLoggedIn()) {
    redirect('index.php');
}

// Biến để hiển thị thông báo
$message = showMessage();

// Biến để lưu trữ dữ liệu form nếu có lỗi validation, giữ lại thông tin đã nhập
$old_input = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'username' => '',
];

// Xử lý khi form được gửi đi (sử dụng phương thức POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    $confirm_password = sanitizeInput($_POST['confirm_password'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');

    // Cập nhật old_input với dữ liệu POST hiện tại để giữ lại giá trị trên form
    $old_input = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'username' => $username,
    ];

    // --- Validation (Kiểm tra hợp lệ dữ liệu) --- //
    $errors = []; // Mảng lưu trữ các lỗi validation

    // Bỏ validation cho 'name'
    // if (empty($name)) {
    //     $errors[] = 'Vui lòng điền Họ và tên.';
    // }
    if (empty($email)) {
        $errors[] = 'Vui lòng điền Email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Định dạng email không hợp lệ.';
    }
    if (empty($password)) {
        $errors[] = 'Vui lòng điền Mật khẩu.';
    } elseif (strlen($password) < 6) { // Ví dụ: Mật khẩu tối thiểu 6 ký tự
         $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if (empty($confirm_password)) {
        $errors[] = 'Vui lòng điền Xác nhận mật khẩu.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Mật khẩu và xác nhận mật khẩu không khớp.';
    }
    if (empty($phone)) {
        $errors[] = 'Vui lòng điền Số điện thoại.';
    }
    if (empty($address)) {
        $errors[] = 'Vui lòng điền Địa chỉ.';
    }
    if (empty($username)) {
        $errors[] = 'Vui lòng điền Tên đăng nhập.';
    }

    // Nếu không có lỗi validation ban đầu
    if (empty($errors)) {
        // Kiểm tra xem email và username đã tồn tại trong database chưa
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt_check_email->bind_param('s', $email);
        $stmt_check_email->execute();
        $result_email = $stmt_check_email->get_result();
        if ($result_email->fetch_assoc()) {
            $errors[] = 'Email đã tồn tại. Vui lòng sử dụng email khác.';
        }
        $stmt_check_email->close();

        $stmt_check_username = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt_check_username->bind_param('s', $username);
        $stmt_check_username->execute();
        $result_username = $stmt_check_username->get_result();
        if ($result_username->fetch_assoc()) {
            $errors[] = 'Tên đăng nhập đã tồn tại. Vui lòng sử dụng tên đăng nhập khác.';
        }
        $stmt_check_username->close();

        // Nếu không có lỗi trùng lặp
        if (empty($errors)) {
            // Hash mật khẩu trước khi lưu vào database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Câu lệnh SQL để chèn người dùng mới
            $sql_insert_user = "INSERT INTO users (username, name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
            $stmt_insert_user = $conn->prepare($sql_insert_user);
            $stmt_insert_user->bind_param('ssssss', $username, $name, $email, $hashed_password, $phone, $address);

            // Thực thi câu lệnh chèn
            if ($stmt_insert_user->execute()) {
                // Đăng ký thành công
                setMessage('success', 'Đăng ký thành công! Bạn có thể <a href="login.php">Đăng nhập</a> ngay bây giờ.');
                redirect('register.php');
            } else {
                // Xử lý lỗi từ mysqli
                if ($conn->errno === 1062) { // 1062 là mã lỗi cho duplicate entry
                    $errors[] = 'Tên đăng nhập hoặc Email đã tồn tại.';
                } else {
                    $errors[] = 'Lỗi khi thêm người dùng vào database: ' . $stmt_insert_user->error;
                }
            }
            $stmt_insert_user->close();
        }
    }

    // Nếu có lỗi (validation hoặc database), lưu thông báo lỗi vào session
    if (!empty($errors)) {
         // Kết hợp các lỗi thành một danh sách HTML và lưu vào session
         setMessage('danger', '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>');
         // Không redirect ở đây để form giữ lại dữ liệu đã nhập
    }
}

require_once 'includes/header.php'; // Include header after all potential redirects

?>

<div class="auth-container">
    <h2>Đăng ký tài khoản</h2>
    <?php echo $message; // Hiển thị thông báo từ session ?>
    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="name">Họ và tên:</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($old_input['name']); ?>">
        </div>
        <div class="form-group">
            <label for="username">Tên đăng nhập:</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($old_input['username']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($old_input['email']); ?>">
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" required>
        </div>
         <div class="form-group">
            <label for="confirm_password">Xác nhận mật khẩu:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <div class="form-group">
            <label for="phone">Điện thoại:</label>
            <input type="text" id="phone" name="phone" required value="<?php echo htmlspecialchars($old_input['phone']); ?>">
        </div>
        <div class="form-group">
            <label for="address">Địa chỉ:</label>
            <input type="text" id="address" name="address" class="form-control" required value="<?php echo htmlspecialchars($old_input['address']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Đăng ký</button>
    </form>
    <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
</div>

<?php require_once 'includes/footer.php'; ?> 