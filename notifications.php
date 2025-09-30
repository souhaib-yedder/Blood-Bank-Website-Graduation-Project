<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'db.php';
require_once 'class_Notification.php';

$db = new Database();
$conn = $db->connect();
$notificationObj = new Notification($conn);

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['name'] ?? 'متبرع';

// ✅ جلب الإشعارات
$notifications = $notificationObj->getNotificationsByUser($user_id, 'donor');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الإشعارات</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <style>
    body {
      background-color: #f0f2f5;
      font-family: 'Segoe UI', sans-serif;
    }
    .notifications-wrapper {
      max-width: 600px;
      margin: 40px auto;
      padding: 20px;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .notification-card {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      border-bottom: 1px solid #eee;
      transition: background-color 0.3s;
      position: relative;
    }
    .notification-card.unread {
      background-color: #eaf3ff;
      font-weight: bold;
    }
    .notification-icon {
      font-size: 20px;
      color: #c82333;
    }
    .notification-text {
      flex: 1;
    }
    .notification-time {
      font-size: 12px;
      color: #888;
    }
    .delete-icon {
      color: red;
      cursor: pointer;
    }
    .delete-icon:hover {
      color: darkred;
    }
    .back-btn {
      display: block;
      margin-top: 20px;
      text-align: center;
    }
    .notification-card:hover {
      background-color: #f1f1f1;
      cursor: pointer;
    }
  </style>
</head>
<body>

<div class="notifications-wrapper">
  <h4 class="mb-4 text-center"><i class="fas fa-bell text-danger"></i> إشعاراتك</h4>

  <?php if (empty($notifications)): ?>
    <p class="text-muted text-center">لا توجد إشعارات بعد.</p>
  <?php else: ?>
    <?php foreach ($notifications as $note): ?>
      <?php
   
$link = '#';
if (in_array($note['reference_type'], ['blood_request', 'accept_bank_request', 'reject_bank_request', 'bank_request'])) {
    $link = 'blood_requests_for_donor.php';
} elseif ($note['reference_type'] === 'donation_campaigns') {
    $link = 'DonationCampaigns.php?all=1';
} elseif ($note['reference_type'] === 'blood_test') {
    $link = 'view_blood_test.php?id=' . urlencode($note['reference_id']);
}


        $readClass = $note['is_read'] == 0 ? 'unread' : '';
      ?>
      <div class="notification-card <?= $readClass ?>" data-id="<?= $note['notification_id'] ?>" data-link="<?= $link ?>">
        <div class="notification-icon">
          <i class="fas fa-circle"></i>
        </div>
        <div class="notification-text">
          <?= htmlspecialchars($note['message']) ?>
          <div class="notification-time"><?= htmlspecialchars($note['created_at']) ?></div>
        </div>
        <div class="delete-icon" onclick="deleteNotification(event, <?= $note['notification_id'] ?>)">
          <i class="fas fa-times"></i>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="back-btn">
    <a href="dashboard_doner.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-right"></i> العودة للوحة التحكم</a>
  </div>
</div>

<script>
  // عند الضغط على البطاقة
  $('.notification-card').on('click', function(e) {
    if ($(e.target).closest('.delete-icon').length) return;

    const card = $(this);
    const id = card.data('id');
    const link = card.data('link');

    $.post('notification_action.php', { action: 'read', id: id }, function() {
      card.removeClass('unread');
      window.location.href = link;
    });
  });

  // حذف الإشعار
  function deleteNotification(event, id) {
    event.stopPropagation();
    if (confirm('هل أنت متأكد من حذف هذا الإشعار؟')) {
      $.post('notification_action.php', { action: 'delete', id: id }, function() {
        $('[data-id="' + id + '"]').fadeOut(300, function() { $(this).remove(); });
      });
    }
  }
</script>

</body>
</html>
