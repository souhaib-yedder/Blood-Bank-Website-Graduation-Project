<?php
require_once 'donor_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

// ✅ التحقق من صلاحية المتبرع
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$campaignObj = new DonationCampaigns($conn);

$user_id = $_SESSION['user_id'];

// ✅ تنفيذ الانضمام إن وُجد الطلب (قبل أي عرض للبيانات)
if (isset($_GET['join']) && is_numeric($_GET['join'])) {
    $campaignObj->joinCampaign($_GET['join'], $user_id);
    // ✅ إعادة التوجيه لتفادي تكرار التنفيذ عند إعادة تحميل الصفحة
  
   echo "<script>window.location.href='DonationCampaigns.php';</script>";

    exit;


}

// ✅ جلب إحداثيات المتبرع من جدول donors
$stmt = $conn->prepare("SELECT latitude, longitude FROM donors WHERE user_id = ?");
$stmt->execute([$user_id]);
$coords = $stmt->fetch(PDO::FETCH_ASSOC);
$latitude = $coords['latitude'] ?? 0;
$longitude = $coords['longitude'] ?? 0;

// ✅ جلب الحملات حسب قرب الموقع
$campaigns = $campaignObj->getNearbyCampaigns($latitude, $longitude, $user_id);

// ✅ عرض جميع الحملات
if (isset($_GET['all'])) {
    $campaigns = $campaignObj->getAllCampaignsExcludingUser($user_id);
}
?>

<h2 class="text-danger text-center mb-4">الحملات التطوعية القريبة منك</h2>

<div class="text-center mb-3">
    <a href="DonationCampaigns.php?all=1" class="btn btn-outline-danger">عرض جميع الحملات</a>
    <a href="JoinedCampaigns.php" class="btn btn-outline-secondary">عرض الحملات المنضم إليها</a>
</div>

<?php if (empty($campaigns)): ?>
  <div class="alert alert-warning text-center">لا توجد حملات حالياً ضمن نطاق 10 كم.</div>
<?php else: ?>
  <div class="row">
    <?php foreach ($campaigns as $c): ?>
      <div class="col-md-6">
        <div class="card mb-4 shadow-sm">
          <div class="card-body">
            <h5 class="card-title text-danger"><?= htmlspecialchars($c['campaign_name']) ?></h5>
            <p><strong>المكان:</strong> <?= htmlspecialchars($c['location']) ?></p>
            <p><strong>الوصف:</strong> <?= htmlspecialchars($c['description']) ?></p>
            <p><strong>التاريخ:</strong> <?= htmlspecialchars($c['campaign_date']) ?></p>
            <div id="map_<?= $c['donation_campaigns_id'] ?>" style="height: 200px; margin-bottom: 10px;"></div>
            <a href="?join=<?= $c['donation_campaigns_id'] ?>" class="btn btn-danger w-100" onclick="return confirm('هل تريد الانضمام إلى هذه الحملة؟');">انضمام</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ✅ Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
<?php foreach ($campaigns as $c): ?>
  const map<?= $c['donation_campaigns_id'] ?> = L.map("map_<?= $c['donation_campaigns_id'] ?>").setView([<?= $c['latitude'] ?>, <?= $c['longitude'] ?>], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map<?= $c['donation_campaigns_id'] ?>);
  L.marker([<?= $c['latitude'] ?>, <?= $c['longitude'] ?>]).addTo(map<?= $c['donation_campaigns_id'] ?>).bindPopup("<?= htmlspecialchars($c['location']) ?>");
<?php endforeach; ?>
</script>
