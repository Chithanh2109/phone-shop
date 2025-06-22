<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
initSession();

$page_title = 'Câu hỏi thường gặp';
require_once 'includes/header.php';

// Truy vấn các câu hỏi thường gặp từ database (chỉ lấy những câu active)
$sql = "SELECT question, answer FROM faqs WHERE is_active = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);

$faqs = [];
if ($result) {
    $faqs = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
} else {
    // Optional: Log error if query fails
    // error_log("FAQ fetch error: " . $conn->error);
}

?>

<div class="container">
    <h1 style="text-align: center; margin-bottom: 30px;">Câu hỏi thường gặp (FAQs)</h1>

    <div class="faq-list">
        <?php if (count($faqs) > 0): ?>
            <?php foreach ($faqs as $faq): ?>
                <div class="faq-item">
                    <h2 class="question"><?php echo htmlspecialchars($faq['question']); ?></h2>
                    <div class="answer">
                        <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p> <!-- Sử dụng nl2br để hiển thị xuống dòng -->
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="faq-item">
                <p>Hiện chưa có câu hỏi thường gặp nào được hiển thị.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 