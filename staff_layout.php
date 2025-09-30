<?php
if (!isset($_SESSION)) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'db.php';

$staff_id = $_SESSION['user_id'] ?? 0;
$staff_name = $_SESSION['name'] ?? 'موظف';

// عدد الإشعارات غير المقروءة للموظف
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'staff' AND user_id = ? AND is_read = 0");
$stmt->execute([$staff_id]);
$notification_count = $stmt->fetchColumn();

// جلب أحدث 5 إشعارات للموظف
$stmt = $conn->prepare("SELECT user_id, message, created_at, reference_type, reference_id FROM notifications WHERE recipient_role = 'staff' AND user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$staff_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة الموظف</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="../css/staff_dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    #wrapper {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }
    #sidebar-wrapper {
      width: 280px;
      background-color: #dc3545;
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
      background-color: #dc3545;
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
  <div class="text-white" id="sidebar-wrapper">
    <div class="sidebar-heading text-center py-4 fw-bold border-bottom">🔬 لوحة الموظف</div>
    <div class="list-group list-group-flush my-3">
      <a href="dashboard_staff.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'dashboard_staff.php' ? 'active' : '' ?>">لوحة التحكم</a>
      <a href="manage_campaigns.php" class="list-group-item list-group-item-action">إدارة الحملات التطوعية</a>
      <a href="blood_stock_management.php" class="list-group-item list-group-item-action">إدارة المخزون الدم</a>
      <a href="total_blood_stock.php" class="list-group-item list-group-item-action">مخزون الدم الكلي سليم /منتهي الصلاحية</a>
      <a href="manage_blood_requests.php" class="list-group-item list-group-item-action">إدارة طلبات الدم المستشفى</a>
      <a href="add_donors.php" class="list-group-item list-group-item-action">إضافة متبرعين جدد</a>
      <a href="staff_create_report.php" class="list-group-item list-group-item-action">إصدار التقارير</a>
      <a href="staff_profile.php" class="list-group-item list-group-item-action">الصفحة الشخصية</a>
      <a href="staff_request_from_donors.php" class="list-group-item list-group-item-action">طلبات الدم من المتبرعين</a>
      <a href="Staff Blood Requests.php" class="list-group-item list-group-item-action">طلبات الواردة من المتبرعين</a>
      <a href="staff_contact_messages.php" class="list-group-item list-group-item-action">رسائل دعم الفني</a>
      <a href="patients_list.php" class="list-group-item list-group-item-action">بيانات المرضى الخاصة بالمستشفيات</a>
      <a href="analysis_requests.php" class="list-group-item list-group-item-action">طلبات التحليل</a>
      <a href="blood_tests_list.php" class="list-group-item list-group-item-action">عرض تفاصيل التحاليل</a>
      <a href="notifications_staff.php" class="list-group-item list-group-item-action">الإشعارات</a>
      <a href="logout.php" class="list-group-item list-group-item-action">تسجيل الخروج</a>
    </div>
  </div>

  <div id="page-content-wrapper" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
      <div class="container-fluid">
        <span class="navbar-brand">مرحباً <?= htmlspecialchars($staff_name) ?> 👨‍⚕️</span>

        <!-- جرس الإشعارات -->
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link position-relative notification-bell" href="notifications_staff.php" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                    if ($note['reference_type'] === 'blood_test') {
                      $link = 'blood_tests_list.php';
                    } elseif ($note['reference_type'] === 'blood_request') {
                      $link = 'Staff Blood Requests.php';
                    } elseif ($note['reference_type'] === 'donation_campaigns') {
                      $link = 'manage_campaigns.php';
                    }
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
