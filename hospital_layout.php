<?php
if (!isset($_SESSION)) session_start();

// تحقق من صلاحية المستشفى
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: unauthorized.php");
    exit();
}

if (!isset($_SESSION['hospital_id'])) {
    $user_id = $_SESSION['user_id'];

    // الاتصال بقاعدة البيانات
    require_once 'db.php';
    $db = new Database();
    $conn = $db->connect();

    // جلب hospitals_id من جدول hospitals بناءً على user_id
    $stmt = $conn->prepare("SELECT hospitals_id FROM hospitals WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // إذا تم العثور على hospitals_id، نقوم بتخزينه في الجلسة
        $_SESSION['hospital_id'] = $row['hospitals_id'];
    } else {
        // في حال لم يتم العثور على المستشفى في قاعدة البيانات
        $_SESSION['error'] = "❌ لم يتم العثور على بيانات المستشفى المرتبطة بهذا الحساب.";
      

           echo "<script>window.location.href='login.php';</script>";

    exit;
    }
}

require_once 'db.php';
$db = new Database();
$conn = $db->connect();

$hospital_id = $_SESSION['hospital_id'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'مستشفى';

// عدد الإشعارات غير المقروءة
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'hospital' AND user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$notification_count = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة المستشفى</title>

  <!-- ✅ ملفات التنسيق -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="../css/hospital_dashboard.css">

  <!-- ✅ أيقونات -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
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
  padding-top: 0;
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

  </style>
</head>
<body>

<div class="d-flex" id="wrapper">

  <!-- ✅ السايد بار -->
  <div class="text-white" id="sidebar-wrapper">
    <div class="sidebar-heading text-center py-4 fw-bold border-bottom">🩸 بنك الدم</div>
    <div class="list-group list-group-flush my-3">
      <a href="dashboard_hospital.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'dashboard_hospital.php' ? 'active' : '' ?>">الصفحة الرئيسية</a>
      <a href="blood_request.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'blood_request.php' ? 'active' : '' ?>">طلب الدم</a>
      <a href="manage_blood_stock.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'manage_blood_stock.php' ? 'active' : '' ?>">المخزون المتاح</a>
      <a href="notifications_hospital.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'notifications_hospital.php' ? 'active' : '' ?>">الإشعارات</a>
      <a href="order_tracking.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'order_tracking.php' ? 'active' : '' ?>">متابعة الطلبات</a>
      <a href="hospital_profile.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'hospital_profile.php' ? 'active' : '' ?>">الصفحة الشخصية للمستشفى</a>
      <a href="hospital_documents.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'hospital_documents.php' ? 'active' : '' ?>">الاوراق الشخصية للمستشفى </a>
      <a href="patient_registration.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'patient_registration.php' ? 'active' : '' ?>">تسجيل بيانات المرضى</a>
      <a href="contact_support.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) === 'contact_support.php' ? 'active' : '' ?>">التواصل مع الدعم الفني</a>
      <a href="logout.php" class="list-group-item list-group-item-action">تسجيل الخروج</a>
    </div>
  </div>

  <!-- ✅ المحتوى الرئيسي -->
  <div id="page-content-wrapper" class="w-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
      <div class="container-fluid">
        <span class="navbar-brand">مرحباً <?= htmlspecialchars($user_name) ?> 👋</span>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link position-relative notification-bell" href="notifications_hospital.php">
              <i class="fas fa-bell fa-lg"></i>
              <?php if ($notification_count > 0): ?>
                <span class="badge bg-danger rounded-circle"><?= $notification_count ?></span>
              <?php endif; ?>
            </a>
          </li>
        </ul>

      </div>
    </nav>

    <div class="container-fluid px-4 py-4">
