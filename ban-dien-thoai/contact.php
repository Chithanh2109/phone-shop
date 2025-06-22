<?php
require_once 'includes/header.php';
$page_title = 'Liên hệ';
?>
<section class="contact-section" style="background:#f7f8fa;padding:40px 0;min-height:70vh;">
  <div class="container" style="max-width:600px;margin:auto;">
    <h2 style="text-align:center;font-size:2rem;margin-bottom:24px;">Liên hệ với chúng tôi</h2>
    <form class="contact-form" method="post" action="#" style="background:#fff;padding:28px 24px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.07);">
      <div class="mb-3">
        <label for="contact_name" class="form-label">Họ và tên</label>
        <input type="text" class="form-control" id="contact_name" name="contact_name" required>
      </div>
      <div class="mb-3">
        <label for="contact_email" class="form-label">Email</label>
        <input type="email" class="form-control" id="contact_email" name="contact_email" required>
      </div>
      <div class="mb-3">
        <label for="contact_message" class="form-label">Nội dung</label>
        <textarea class="form-control" id="contact_message" name="contact_message" rows="4" required></textarea>
      </div>
      <div style="text-align:center;">
        <button type="submit" class="btn btn-primary" style="padding:10px 32px;font-size:1.1rem;">Gửi liên hệ</button>
      </div>
    </form>
  </div>
</section>
<?php require_once 'includes/footer.php'; ?> 