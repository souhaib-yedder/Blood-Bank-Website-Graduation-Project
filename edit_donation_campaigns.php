<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$campaign = new DonationCampaigns($conn);

if (!isset($_GET['id'])) {

     echo "<script>window.location.href='manage_donation_campaigns.php';</script>";

    exit;

}

$id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_name = $_POST['campaign_name'];
    $campaign_date = $_POST['campaign_date'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $target_units = $_POST['target_units'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $staff_id = 1;

    $campaign->updateCampaign($id, $staff_id, $campaign_name, $campaign_date, $location, $description, $target_units, $latitude, $longitude, null);

  

       echo "<script>window.location.href='manage_donation_campaigns.php';</script>";

    exit;
}

$stmt = $conn->prepare("SELECT * FROM donation_campaigns WHERE donation_campaigns_id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "الحملة غير موجودة.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل الحملة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 400px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">تعديل بيانات الحملة</h2>

    <form method="POST">
        <div class="mb-3">
            <label>اسم الحملة</label>
            <input type="text" name="campaign_name" class="form-control" value="<?= htmlspecialchars($data['campaign_name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>تاريخ الحملة</label>
            <input type="date" name="campaign_date" class="form-control" value="<?= htmlspecialchars($data['campaign_date']) ?>" required>
        </div>
        <div class="mb-3">
            <label>الموقع</label>
            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($data['location']) ?>" required>
        </div>
        <div class="mb-3">
            <label>الوصف</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($data['description']) ?></textarea>
        </div>
     <div class="mb-3">
    <label>عدد الوحدات المستهدفة</label>
    <input type="number" name="target_units" class="form-control" 
           value="<?= htmlspecialchars($data['target_units']) ?>" required min="1">
</div>


        <div class="mb-3">
            <label>تحديد الموقع على الخريطة</label>
            <div id="map"></div>
        </div>

        <!-- حقول إحداثيات مخفية -->
        <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($data['latitude']) ?>">
        <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($data['longitude']) ?>">

        <button type="submit" class="btn btn-success mt-3">تحديث</button>
        <a href="manage_donation_campaigns.php" class="btn btn-secondary mt-3">إلغاء</a>
    </form>
</div>

<!-- Leaflet Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const lat = <?= floatval($data['latitude'] ?? 24.7136) ?>;
    const lng = <?= floatval($data['longitude'] ?? 46.6753) ?>;

    const map = L.map('map').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'OpenStreetMap contributors'
    }).addTo(map);

    let marker = L.marker([lat, lng], {draggable: true}).addTo(map);

    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        document.getElementById('latitude').value = pos.lat;
        document.getElementById('longitude').value = pos.lng;
    });

    // حل مشكلة عدم ظهور الخريطة أحيانًا
    setTimeout(() => {
        map.invalidateSize();
    }, 300);
</script>
</body>
</html>
