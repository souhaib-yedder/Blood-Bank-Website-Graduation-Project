<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'db.php';
require_once 'class_Notification.php';

$db = new Database();
$conn = $db->connect();
$notificationObj = new Notification($conn);

$user_id = $_SESSION['user_id']; // المستخدم الحالي

// جلب إشعارات هذا المستخدم فقط
$notifications = $notificationObj->getNotificationsByUserId($user_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إشعارات الأدمن</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    body {
      background-color: #f0f2f5;
    }
    .notifications-wrapper {
      max-width: 700px;
      margin: 40px auto;
    }
    .card {
      background-color: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      padding: 20px;
    }
    .notification-card {
      padding: 15px;
      border-bottom: 1px solid #eee;
      position: relative;
      cursor: pointer;
    }
    .notification-card.unread {
      background-color: #eaf3ff;
      font-weight: bold;
    }
    .notification-time {
      font-size: 12px;
      color: gray;
    }
    .delete-icon {
      position: absolute;
      left: 10px;
      top: 15px;
      color: red;
      cursor: pointer;
    }
  </style>
</head>
<body>

<div class="notifications-wrapper">
  <div class="card">
    <h4 class="mb-4 text-center"><i class="fas fa-bell text-danger"></i> إشعارات الأدمن</h4>

    <?php if (empty($notifications)): ?>
      <p class="text-muted text-center">لا توجد إشعارات حالياً.</p>
    <?php else: ?>
      <?php foreach ($notifications as $note): ?>
        <?php $readClass = $note['is_read'] == 0 ? 'unread' : ''; ?>
        <div class="notification-card <?= $readClass ?>" data-id="<?= $note['notification_id'] ?>" data-link="#">
          <?= htmlspecialchars($note['message']) ?>
          <div class="notification-time"><?= $note['created_at'] ?></div>
          <span class="delete-icon" onclick="deleteNotification(event, <?= $note['notification_id'] ?>)"><i class="fas fa-times"></i></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  // تعليم الإشعار كمقروء عند الضغط عليه
  $('.notification-card').on('click', function(e) {
    if ($(e.target).closest('.delete-icon').length) return;
    const id = $(this).data('id');
    $.post('notification_action.php', { action: 'read', id: id }, () => $(this).removeClass('unread'));
  });

  // حذف الإشعار
  function deleteNotification(e, id) {
    e.stopPropagation();
    if (confirm("هل تريد حذف الإشعار؟")) {
      $.post('notification_action.php', { action: 'delete', id: id }, () => {
        $('[data-id="' + id + '"]').fadeOut(300, function() { $(this).remove(); });
      });
    }
  }
</script>

</body>
</html>
