<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'db.php';
require_once 'class_Notification.php';

$db = new Database();
$conn = $db->connect();
$notificationObj = new Notification($conn);

$user_id = $_SESSION['user_id'] ?? 0;
$staff_name = $_SESSION['name'] ?? 'الموظف';

// ✅ جلب الإشعارات الخاصة بالموظف
$notifications = $notificationObj->getNotificationsByUser($user_id, 'staff');
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
    }
    .notifications-wrapper {
      max-width: 700px;
      margin: 40px auto;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .notification-card {
      padding: 15px;
      border-bottom: 1px solid #eee;
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
      float: left;
      color: red;
      cursor: pointer;
    }
  </style>
</head>
<body>

<div class="notifications-wrapper">
  <h4 class="mb-4 text-center"><i class="fas fa-bell text-danger"></i> إشعارات الموظف</h4>

  <?php if (empty($notifications)): ?>
    <p class="text-muted text-center">لا توجد إشعارات حالياً.</p>
  <?php else: ?>
    <?php foreach ($notifications as $note): ?>
      <?php
        $link = '#';
        if ($note['reference_type'] === 'blood_bank_requests') {
            $link = 'Staff Blood Requests.php';
        } elseif ($note['reference_type'] === 'donation_campaigns') {
            $link = 'view_all_campaigns.php';
        } elseif ($note['reference_type'] === 'blood_test') {
            $link = 'blood_tests_list.php';
             } elseif ($note['reference_type'] === 'blood_stock') {
            $link = 'blood_stock_management.php';
             } elseif ($note['reference_type'] === 'contact_messages') {
            $link = 'staff_contact_messages.php';
        }

        $readClass = $note['is_read'] == 0 ? 'unread' : '';
      ?>
      <div class="notification-card <?= $readClass ?>" data-id="<?= $note['notification_id'] ?>" data-link="<?= $link ?>">
        <div class="d-flex justify-content-between">
          <div><?= htmlspecialchars($note['message']) ?></div>
          <div class="delete-icon" onclick="deleteNotification(event, <?= $note['notification_id'] ?>)">
            <i class="fas fa-times"></i>
          </div>
        </div>
        <div class="notification-time"><?= htmlspecialchars($note['created_at']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="text-center mt-3">
    <a href="dashboard_staff.php" class="btn btn-outline-secondary">العودة للوحة الموظف</a>
  </div>
</div>

<script>
  // قراءة الإشعار عند الضغط
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
