<?php
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

$db = new Database();
$conn = $db->connect();
$campaigns = new DonationCampaigns($conn);

$success = '';
$error = '';

if (!isset($_SESSION['user_id'])) {
    die("المستخدم غير مسجل الدخول.");
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ?");
$stmt->execute([$user_id]);
$staff = $stmt->fetch();

if (!$staff) {
    die("لم يتم العثور على بيانات الموظف.");
}

$staff_id = $staff['staff_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name  = $_POST['campaign_name'];
    $campaign_date  = $_POST['campaign_date'];
    $location       = $_POST['location'];
    $description    = $_POST['description'];
    $target_units   = $_POST['target_units'];
    $latitude       = $_POST['latitude'];
    $longitude      = $_POST['longitude'];

    if ($campaigns->createCampaign($staff_id, $campaign_name, $campaign_date, $location, $description, $target_units, $latitude, $longitude, null)) {
        $success = "✅ تم إنشاء الحملة بنجاح.";
    } else {
        $error = "❌ حدث خطأ أثناء إنشاء الحملة.";
    }
}

$stmt = $conn->prepare("SELECT * FROM donation_campaigns WHERE staff_id = ? ORDER BY campaign_date DESC");
$stmt->execute([$staff_id]);
$my_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ✅ تصميم الخريطة -->
<style>
  #map {
    width: 100%;
    height: 400px;
    border: 1px solid #ccc;
    border-radius: 10px;
    margin-top: 10px;
  }
</style>

<div class="container-fluid px-4 py-4" id="page-content-wrapper">
    <h2 class="text-center text-danger mb-4">إضافة حملة تطوعية</h2>

    <div class="text-center mb-3">
        <a href="view_all_campaigns.php" class="btn btn-outline-danger">عرض جميع الحملات</a>
        <a href="MyCampaigns.php" class="btn btn-outline-secondary">عرض حملاتي التطوعية</a>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success text-center"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3 bg-white p-4 shadow rounded">
        <div class="col-md-6">
            <label class="form-label">اسم الحملة:</label>
            <input type="text" name="campaign_name" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">تاريخ الحملة:</label>
            <input type="date" name="campaign_date" class="form-control" required>
        </div>

        <div class="col-md-12">
            <label class="form-label">الموقع (اسم المكان):</label>
            <input type="text" name="location" class="form-control" required>
        </div>

        <div class="col-md-12">
            <label class="form-label">الوصف:</label>
            <textarea name="description" class="form-control" rows="3" required></textarea>
        </div>

     <div class="col-md-6">
    <label class="form-label">عدد الوحدات المستهدفة:</label>
    <input type="number" name="target_units" class="form-control" min="1" required>
</div>

        <div class="col-md-12">
            <label class="form-label">تحديد الموقع على الخريطة:</label>
            <div id="map"></div>
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
        </div>

        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-danger w-50">إنشاء الحملة</button>
        </div>
    </form>
</div>

<!-- ✅ مكتبة Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const map = L.map('map').setView([32.8872, 13.1913], 13); // طرابلس مبدئياً

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(map);

    let marker;

    map.on('click', function (e) {
      const lat = e.latlng.lat.toFixed(6);
      const lng = e.latlng.lng.toFixed(6);

      document.getElementById('latitude').value = lat;
      document.getElementById('longitude').value = lng;

      if (marker) {
        map.removeLayer(marker);
      }

      marker = L.marker([lat, lng]).addTo(map).bindPopup("📍 تم اختيار هذا الموقع").openPopup();
    });

    // التأكد من ظهور الخريطة داخل التصميم
    setTimeout(function () {
      map.invalidateSize();
    }, 300);
  });
</script>
