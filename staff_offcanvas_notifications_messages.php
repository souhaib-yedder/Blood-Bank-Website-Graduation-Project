<!-- Offcanvas للإشعارات -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="notificationsOffcanvas" aria-labelledby="notificationsOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="notificationsOffcanvasLabel">
      <i class="fas fa-bell me-2"></i>
      الإشعارات
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- محتوى الإشعارات هنا -->
    <?php if (count($notifications) > 0): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($notifications as $note): ?>
          <?php
            $link = '#';
            $icon = 'fas fa-info-circle';
            $color = 'text-info';
            
            if ($note['reference_type'] === 'blood_test') {
              $link = 'blood_tests_list.php';
              $icon = 'fas fa-flask';
              $color = 'text-success';
            } elseif ($note['reference_type'] === 'blood_request') {
              $link = 'Staff Blood Requests.php';
              $icon = 'fas fa-tint';
              $color = 'text-danger';
            } elseif ($note['reference_type'] === 'donation_campaigns') {
              $link = 'manage_campaigns.php';
              $icon = 'fas fa-calendar-alt';
              $color = 'text-primary';
            }
          ?>
          <li class="list-group-item d-flex align-items-start">
            <i class="<?= $icon ?> me-3 mt-1 <?= $color ?> fa-lg"></i>
            <div>
              <a href="<?= $link ?>" class="text-decoration-none text-dark">
                <strong><?= htmlspecialchars($note['message']) ?></strong><br>
                <small class="text-muted"><?= $note['created_at'] ?></small>
              </a>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="alert alert-info text-center" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        لا توجد إشعارات جديدة
      </div>
    <?php endif; ?>
    <div class="text-center mt-3">
      <a href="notifications_staff.php" class="btn btn-outline-primary btn-sm">
        عرض جميع الإشعارات
        <i class="fas fa-arrow-left ms-2"></i>
      </a>
    </div>
  </div>
</div>

<!-- Offcanvas للرسائل -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="messagesOffcanvas" aria-labelledby="messagesOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="messagesOffcanvasLabel">
      <i class="fas fa-envelope me-2"></i>
      رسائل الدعم الفني
    </h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- محتوى الرسائل هنا (مثال) -->
    <?php if (count($messages) > 0): ?>
      <ul class="list-group list-group-flush">
        <?php foreach ($messages as $msg): ?>
          <li class="list-group-item d-flex align-items-start">
            <i class="fas fa-comment-dots me-3 mt-1 text-primary fa-lg"></i>
            <div>
              <strong><?= htmlspecialchars($msg['subject']) ?></strong><br>
              <small class="text-muted"><?= htmlspecialchars($msg['message']) ?></small><br>
              <small class="text-muted">من: <?= htmlspecialchars($msg['name']) ?> (<?= htmlspecialchars($msg['email']) ?>)</small><br>
              <small class="text-muted">بتاريخ: <?= $msg['created_at'] ?></small>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="alert alert-info text-center" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        لا توجد رسائل دعم فني جديدة
      </div>
    <?php endif; ?>
    <div class="text-center mt-3">
      <a href="staff_contact_messages.php" class="btn btn-outline-primary btn-sm">
        عرض جميع الرسائل
        <i class="fas fa-arrow-left ms-2"></i>
      </a>
    </div>
  </div>
</div>
