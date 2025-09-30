<?php
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

// إنشاء الاتصال مع قاعدة البيانات
$db = new Database();
$conn = $db->connect();
$campaignObj = new DonationCampaigns($conn);

// التحقق من دخول الموظف
if (!isset($_SESSION['user_id'])) {
    die("المستخدم غير مسجل الدخول.");
}

$user_id = $_SESSION['user_id'];

// جلب جميع الحملات التي أنشأها الموظفون
$campaigns = $campaignObj->getAllCampaigns();

// عرض الحملات على الخريطة
?>

<div class="container-fluid px-4 py-4">
    <h2 class="text-center text-danger mb-4">عرض جميع الحملات التطوعية</h2>

    <?php if (empty($campaigns)): ?>
        <div class="alert alert-warning text-center">لا توجد حملات حاليًا.</div>
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
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Leaflet JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // تمكين الخريطة لكل حملة
    <?php foreach ($campaigns as $c): ?>
        const map<?= $c['donation_campaigns_id'] ?> = L.map("map_<?= $c['donation_campaigns_id'] ?>").setView([<?= $c['latitude'] ?>, <?= $c['longitude'] ?>], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map<?= $c['donation_campaigns_id'] ?>);

        L.marker([<?= $c['latitude'] ?>, <?= $c['longitude'] ?>])
            .addTo(map<?= $c['donation_campaigns_id'] ?>)
            .bindPopup("<?= htmlspecialchars($c['location']) ?>");
    <?php endforeach; ?>
</script>
