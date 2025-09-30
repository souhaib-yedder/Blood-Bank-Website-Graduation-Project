<?php
//session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'db.php';
$user_id = $_SESSION['user_id'] ?? 0;
$user_name = $_SESSION['name'] ?? 'أدمن';

$conn = (new Database())->connect();

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM notifications n
    INNER JOIN users u ON n.user_id = u.user_id
    WHERE u.role = 'admin' 
      AND n.is_read = 0
");
$stmt->execute();
$notification_count = $stmt->fetchColumn();



// آخر 5 إشعارات
$stmt = $conn->prepare("SELECT * FROM notifications WHERE recipient_role IN ('admin','staff') ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>لوحة تحكم الأدمن</title>

  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">


  
  <style>
    body { font-family: 'Segoe UI', sans-serif; background-color: #f9f9f9; }
    .sidebar { height: 100vh; background-color: #c82333; padding-top: 20px; position: fixed; top: 0; right: 0; width: 220px; }
    .sidebar a { display: block; color: #fff; padding: 12px 20px; text-decoration: none; }
    .sidebar a:hover, .sidebar a.active { background-color: #c82333; }
    .page-content { margin-right: 220px; padding: 20px; }
    .notification-bell { position: relative; }
    .notification-bell .badge { position: absolute; top: -5px; left: -8px; }
    .dropdown-menu-notifications { max-height: 400px; overflow-y: auto; width: 320px; }



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

  </style>
</head>
<body>

<div class="sidebar">
  
  <h5 class="text-center text-white mb-4">🛡️ الأدمن</h5>
  <a href="dashboard_admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> لوحة التحكم</a>
  <a href="manage_donors.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_donors.php' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> إدارة المتبرعين</a>
  <a href="manage_hospitals.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_hospitals.php' ? 'active' : '' ?>"><i class="bi bi-hospital"></i> إدارة المستشفيات</a>
  <a href="manage_blood_stock.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_blood_stock.php' ? 'active' : '' ?>"><i class="bi bi-droplet-half"></i> إدارة مخزون الدم</a>
  <a href="manage_staff.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_staff.php' ? 'active' : '' ?>"><i class="bi bi-person-badge-fill"></i> إدارة الموظفين</a>
  <a href="add_staff.php" class="<?= basename($_SERVER['PHP_SELF']) == 'add_staff.php' ? 'active' : '' ?>"><i class="bi bi-plus-circle-fill"></i> إضافة موظف</a>
  <a href="manage_donation_campaigns.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_donation_campaigns.php' ? 'active' : '' ?>"><i class="bi bi-megaphone-fill"></i> الحملات التطوعية</a>
  <a href="manage_tests.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_tests.php' ? 'active' : '' ?>"><i class="bi bi-clipboard-pulse"></i> إدارة التحاليل</a>
  <a href="report.php" class="<?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>"><i class="bi bi-file-earmark-bar-graph"></i> التقارير</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a>
</div>

<div class="page-content">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid justify-content-between">
      <span class="navbar-brand">مرحباً <?= htmlspecialchars($user_name) ?> 👋</span>
      <ul class="navbar-nav flex-row">
        <li class="nav-item dropdown">
          <a class="nav-link position-relative notification-bell" href="notifications_admin.php" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-bell fs-4"></i>
            <?php if ($notification_count > 0): ?>
              <span class="badge bg-danger rounded-circle"><?= $notification_count ?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-notifications p-2">
            <li class="dropdown-header">أحدث الإشعارات</li>
            <?php if (count($notifications) > 0): ?>
              <?php foreach ($notifications as $note): ?>
                <li>
                  <a href="notifications_admin.php" class="dropdown-item small">
                    <?= htmlspecialchars($note['message']) ?><br>
                    <small class="text-muted"><?= $note['created_at'] ?></small>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><span class="dropdown-item text-muted small">لا توجد إشعارات</span></li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
