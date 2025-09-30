<?php
require_once 'donor_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

// التحقق من صلاحية المتبرع
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$campaignObj = new DonationCampaigns($conn);

$user_id = $_SESSION['user_id'];

// ✅ جلب donor_id من جدول donors
$stmt = $conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
$stmt->execute([$user_id]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    die("لم يتم العثور على بيانات المتبرع.");
}

$donor_id = $donor['donors_id'];

// ✅ تنفيذ عملية إلغاء الانضمام عند الطلب
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $stmt = $conn->prepare("DELETE FROM donations WHERE donor_id = ? AND donation_campaign_id = ?");
    $stmt->execute([$donor_id, $_GET['cancel']]);

   echo "<script>window.location.href='JoinedCampaigns.php';</script>";

    exit;
}

// ✅ جلب الحملات التي انضم إليها المتبرع
$joined = $campaignObj->getJoinedCampaigns($donor_id);
?>

<h2 class="text-center text-danger mb-4">الحملات التي انضممت إليها</h2>

<?php if (empty($joined)): ?>
  <div class="alert alert-warning text-center">لم تنضم إلى أي حملة بعد.</div>
<?php else: ?>
  <div class="row">
    <?php foreach ($joined as $c): ?>
      <div class="col-md-6">
        <div class="card mb-4 shadow-sm">
          <div class="card-body">
            <h5 class="card-title text-danger"><?= htmlspecialchars($c['campaign_name']) ?></h5>
            <p><strong>المكان:</strong> <?= htmlspecialchars($c['location']) ?></p>
            <p><strong>الوصف:</strong> <?= htmlspecialchars($c['description']) ?></p>
            <p><strong>التاريخ:</strong> <?= htmlspecialchars($c['campaign_date']) ?></p>
            <div id="map_joined_<?= $c['donation_campaigns_id'] ?>" style="height: 200px; margin-bottom: 10px;"></div>
            <a href="?cancel=<?= $c['donation_campaigns_id'] ?>" 
               onclick="return confirm('هل أنت متأكد أنك تريد إلغاء الانضمام؟');" 
               class="btn btn-outline-danger w-100">
               إلغاء الانضمام
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
<?php foreach ($joined as $c): ?>
  const mapJ<?= $c['donation_campaigns_id'] ?> = L.map("map_joined_<?= $c['donation_campaigns_id'] ?>").setView([<?= $c['latitude'] ?>, <?= $c['longitude'] ?>], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
  }).addTo(mapJ<?= $c['donation_campaigns_id'] ?>);
  L.marker([<?= $c['latitude'] ?>, <?= $c['longitude'] ?>]).addTo(mapJ<?= $c['donation_campaigns_id'] ?>).bindPopup("<?= htmlspecialchars($c['location']) ?>");
<?php endforeach; ?>
</script>
