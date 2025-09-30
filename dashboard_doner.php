<?php
require_once 'donor_layout.php'; // يحتوي على الهيدر والسايدبار
require_once 'db.php';
require_once 'Statistics.php';

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? 0;

// إنشاء كائن من كلاس الإحصائيات
$stats = new Statistics($conn);

// جلب donor_id من جدول donors
$stmt = $conn->prepare("SELECT donors_id  FROM donors WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

$donors_id  = $donor ? $donor['donors_id'] : 0;

// جلب البيانات باستخدام الدوال
$donation_count = $stats->countDonationsByDonor($donors_id);
$last_donation = $stats->getLastDonationDate($donors_id);
$last_result = $stats->getLatestBloodTestResult($donors_id);

//$requests_to_me = $stats->countRequestsToDonor($user_id);
$requests_to_me = $stats->countRequestsToDonor($donors_id);

$unread_notifications = $stats->countUnreadNotificationsForUser($user_id);
$nearby_campaigns = $stats->countNearbyCampaigns($user_id); // استخدام الدالة الصحيحة

// جلب بيانات الجدول والخريطة
$latest_tests = $stats->getLatestBloodTestsForDonor($donors_id);
$donation_locations = $stats->getDonationLocationsForDonor($donors_id);
$locations_json = json_encode($donation_locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

?>


<!-- تضمين مكتبة الخرائط Leaflet.js وملفات التنسيق الخاصة بها -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    /* تصميم البطاقات الإحصائية */
    .stat-card {
        background-color: #fff;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08 );
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .stat-card .card-body {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .stat-card .stat-icon {
        font-size: 3rem;
        opacity: 0.6;
    }
    /* تصميم الخريطة والجدول */
    #map {
        height: 400px;
        width: 100%;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .card-table {
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
</style>

<h2 class="mb-4">لوحة تحكم المتبرع</h2>

<!-- صف البطاقات الإحصائية -->
<div class="row g-4 mb-4">
    <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="card-body"><div><h5 class="card-title">عدد التبرعات</h5><h2 class="fw-bold"><?= htmlspecialchars($donation_count) ?></h2></div><div class="stat-icon text-danger">🩸</div></div></div></div>
    <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="card-body"><div><h5 class="card-title">آخر تبرع</h5><p class="fs-5 fw-bold mb-0"><?= htmlspecialchars($last_donation) ?></p></div><div class="stat-icon text-primary">🗓️</div></div></div></div>
    <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="card-body"><div><h5 class="card-title">آخر تحليل</h5><p class="fs-5 fw-bold mb-0"><?= htmlspecialchars($last_result) ?></p></div><div class="stat-icon text-success">🔬</div></div></div></div>
    <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="card-body"><div><h5 class="card-title">طلبات موجهة لي</h5><h2 class="fw-bold"><?= htmlspecialchars($requests_to_me) ?></h2></div><div class="stat-icon text-info">📨</div></div></div></div>
    <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="card-body"><div><h5 class="card-title">الإشعارات</h5><h2 class="fw-bold"><?= htmlspecialchars($unread_notifications) ?></h2></div><div class="stat-icon text-warning">🔔</div></div></div></div>
    <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="card-body"><div><h5 class="card-title">حملات قريبة</h5><h2 class="fw-bold"><?= htmlspecialchars($nearby_campaigns) ?></h2></div><div class="stat-icon text-success">📍</div></div></div></div>
</div>

<!-- قسم الجدول والخريطة -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-table h-100">
            <div class="card-header"><h5 class="mb-0">آخر فحوصات الدم</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>تاريخ الفحص</th><th>الهيموجلوبين</th><th>صفائح</th><th>الحالة</th></tr></thead>
                    <tbody>
                        <?php if (empty($latest_tests)): ?>
                            <tr><td colspan="4" class="text-center py-4">لا توجد بيانات فحوصات لعرضها.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latest_tests as $test): ?>
                                <tr>
                                    <td><?= htmlspecialchars($test['test_date']) ?></td>
                                    <td><?= htmlspecialchars($test['hemoglobin_level'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($test['platelet_count'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($test['blood_condition']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-table h-100">
            <div class="card-header"><h5 class="mb-0">خريطة حملات التبرع التي شاركت بها</h5></div>
            <div class="card-body"><div id="map"></div></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // التأكد من أن عنصر الخريطة موجود قبل تهيئتها
    if (document.getElementById('map')) {
        const map = L.map('map').setView([26.3351, 17.2283], 5); // مركز على ليبيا
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        } ).addTo(map);

        const locations = <?= $locations_json ?>;

        if (locations && locations.length > 0) {
            const bounds = [];
            locations.forEach(loc => {
                if (loc.latitude && loc.longitude) {
                    const lat = parseFloat(loc.latitude);
                    const lon = parseFloat(loc.longitude);
                    if (!isNaN(lat) && !isNaN(lon)) {
                        const marker = L.marker([lat, lon]).addTo(map)
                            .bindPopup(`<b>${loc.campaign_name}</b>  
${loc.location}`);
                        bounds.push([lat, lon]);
                    }
                }
            });
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        } else {
            const center = map.getCenter();
            L.popup().setLatLng(center).setContent('لم تشارك في أي حملات مسجلة بعد.').openOn(map);
        }
    }
});
</script>

<?php
?>