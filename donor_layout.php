<?php
if (!isset($_SESSION)) session_start();

// تحقق من صلاحية المتبرع
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'db.php'; // تأكد من المسار الصحيح

$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['name'] ?? 'متبرع';

// عدد الإشعارات غير المقروءة
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'donor' AND user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetchColumn();

// جلب أحدث 5 إشعارات
$stmt = $conn->prepare("SELECT user_id, message, created_at, reference_type FROM notifications WHERE recipient_role = 'donor' AND user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة المتبرع</title>

  <!-- ✅ ملفات التنسيق -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="../css/donor_dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">

  <!-- ✅ أيقونات -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>

    body, #wrapper, #sidebar-wrapper, #page-content-wrapper, .list-group-item, .sidebar-heading {
  font-family: 'Cairo', sans-serif;
}

    #wrapper {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    #sidebar-wrapper {
      width: 280px;
      background-color: #c82333;
      height: 100vh;
      position: fixed;
      top: 0;
      right: 0;
      overflow-y: auto;
    }

    .sidebar-heading {
      font-size: 22px;
      color: white;
    }

    .list-group-item {
      font-size: 15px;
      padding: 15px 20px;
      background-color: #c82333;
      color: white;
      border: none;
    }

    .list-group-item.active,
    .list-group-item:hover {
      background-color: #a71d2a;
      color: white;
    }

    #page-content-wrapper {
      margin-right: 280px;
      width: calc(100% - 280px);
      overflow-y: auto;
      height: 100vh;
    }

    .notification-bell {
      position: relative;
      cursor: pointer;
    }

    .notification-bell .badge {
      position: absolute;
      top: -6px;
      left: -6px;
    }

    .dropdown-menu-notifications {
      max-height: 400px;
      overflow-y: auto;
      width: 320px;
    }
  </style>
</head>
<body>

<div class="d-flex" id="wrapper">
  <!-- ✅ السايد بار -->
  <div class="text-white" id="sidebar-wrapper">
    <div class="sidebar-heading text-center py-4 fw-bold border-bottom">🩸 بنك الدم</div>
    <div class="list-group list-group-flush my-3">
      <a href="dashboard_doner.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'dashboard_doner.php' ? 'active' : '' ?>">لوحة التحكم</a>
      <a href="profile.php" class="list-group-item list-group-item-action">الملف الشخصي</a>
      <a href="request_blood.php" class="list-group-item list-group-item-action">طلب تبرع</a>
      <a href="analysis_results.php" class="list-group-item list-group-item-action">نتائج التحاليل</a>
      <a href="donations_log.php" class="list-group-item list-group-item-action">سجل التبرعات</a>
      <a href="blood_requests_for_donor.php" class="list-group-item list-group-item-action">طلبات واردة</a>
      <a href="DonationCampaigns.php" class="list-group-item list-group-item-action">حملات تطوعية</a>
      <a href="request_blood_bank.php" class="list-group-item list-group-item-action">طلب الدم من بنك الدم</a>
      <a href="notifications.php" class="list-group-item list-group-item-action">الإشعارات</a>
      <a href="logout.php" class="list-group-item list-group-item-action">تسجيل الخروج</a>
    </div>
  </div>

  <!-- ✅ المحتوى الرئيسي -->
  <div id="page-content-wrapper" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom px-4">
      <div class="container-fluid">
        <span class="navbar-brand">مرحباً <?= htmlspecialchars($user_name) ?> 👋</span>

        <!-- 🔔 جرس الإشعارات -->
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link position-relative notification-bell" href="notifications.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-bell fa-lg"></i>
              <?php if ($notification_count > 0): ?>
                <span class="badge bg-danger rounded-circle"><?= $notification_count ?></span>
              <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-notifications p-2">
              <li class="dropdown-header">الإشعارات الأخيرة</li>
              <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $note): ?>
                  <?php
                    $link = '#';
                    if ($note['reference_type'] === 'blood_test') $link = 'analysis_results.php';
                    elseif ($note['reference_type'] === 'request') $link = 'blood_requests_for_donor.php';
                  ?>
                  <li>
                    <a href="<?= $link ?>" class="dropdown-item small">
                      <?= htmlspecialchars($note['message']) ?><br>
                      <small class="text-muted"><?= $note['created_at'] ?></small>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li><span class="dropdown-item text-muted small">لا توجد إشعارات جديدة</span></li>
              <?php endif; ?>
            </ul>
          </li>
        </ul>

      </div>
    </nav>

    <div class="container-fluid px-4 py-4">
