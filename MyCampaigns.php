<?php
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

$db = new Database();
$conn = $db->connect();
$campaigns = new DonationCampaigns($conn);

$success = '';

// التحقق من الدخول
if (!isset($_SESSION['user_id'])) {
    die("المستخدم غير مسجل الدخول.");
}

$user_id = $_SESSION['user_id'];

// جلب staff_id المرتبط بـ user_id
$stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
$stmt->execute([$user_id]);
$staff = $stmt->fetch();

if (!$staff) {
    die("لم يتم العثور على بيانات الموظف.");
}

$staff_id = $staff['staff_id'];

// جلب الحملات التي أنشأها الموظف
$stmt = $conn->prepare("SELECT * FROM donation_campaigns WHERE staff_id = ? ORDER BY campaign_date DESC");
$stmt->execute([$staff_id]);
$my_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// حذف حملة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $campaign_id = $_GET['delete'];
    $campaigns->deleteCampaign($campaign_id);
    $success = "تم حذف الحملة بنجاح.";
}

?>

<div class="container-fluid px-4 py-4">
  <h2 class="text-center text-danger mb-4">حملاتي التطوعية</h2>

  <?php if ($success): ?>
    <div class="alert alert-success text-center"><?= $success ?></div>
  <?php endif; ?>

  <?php if (empty($my_campaigns)): ?>
    <div class="alert alert-warning text-center">لا توجد حملات تم إنشاؤها بعد.</div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($my_campaigns as $c): ?>
        <div class="col-md-6">
          <div class="card mb-4 shadow-sm">
            <div class="card-body">
              <h5 class="card-title text-danger"><?= htmlspecialchars($c['campaign_name']) ?></h5>
              <p><strong>المكان:</strong> <?= htmlspecialchars($c['location']) ?></p>
              <p><strong>الوصف:</strong> <?= htmlspecialchars($c['description']) ?></p>
              <p><strong>التاريخ:</strong> <?= htmlspecialchars($c['campaign_date']) ?></p>
              <a href="edit_campaign.php?campaign_id=<?= $c['donation_campaigns_id'] ?>" class="btn btn-warning">تعديل</a>
              <a href="?delete=<?= $c['donation_campaigns_id'] ?>" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه الحملة؟')">حذف</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
