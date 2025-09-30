<?php
session_start();
require_once 'hospital_layout.php';
require_once 'db.php';
require_once 'class_Hospital.php';

// تحقق من صلاحية المستشفى
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$hospital = new Hospital($conn);

$user_id = $_SESSION['user_id'];
$hospitalData = $hospital->getHospitalByUserId($user_id);

// استعلام بيانات المستخدم
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// تنفيذ التحديث عند الضغط على الزر
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $lng = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;

    $hospital->updateHospitalProfile(
        $hospitalData['hospitals_id'],
        $_POST['hospital_name'],
        $_POST['phone'],
        $_POST['location'],


        $lat,
        $lng
    );

    echo "<script>alert('✅ تم تحديث البيانات بنجاح');window.location.href='hospital_profile.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>ملف المستشفى</title>

    <!-- مكتبة Leaflet للخرائط بدون integrity -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

    <style>
        #map {
            height: 400px;
            border-radius: 8px;
            margin-top: 8px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">الملف الشخصي للمستشفى</h2>
    <form method="POST" id="hospitalForm">
        <div class="row">
            <div class="col-md-6">
                <label>اسم المستخدم:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($userData['name']) ?>" name="name" readonly>
            </div>
            <div class="col-md-6">
                <label>البريد الإلكتروني:</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" readonly>
            </div>
            <div class="col-md-6 mt-3">
                <label>تاريخ التسجيل:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($userData['created_at']) ?>" readonly>
            </div>
            <div class="col-md-6 mt-3">
                <label>اسم المستشفى:</label>
                <input type="text" class="form-control" name="hospital_name" value="<?= htmlspecialchars($hospitalData['hospital_name']) ?>" required>
            </div>
<div class="col-md-6 mt-3">
    <label>رقم الهاتف:</label>
    <input type="text" class="form-control" name="phone" 
           value="<?= htmlspecialchars($hospitalData['phone']) ?>" required
           pattern="^[0-9]{10}$"
           minlength="10" maxlength="10"
           title="رقم الهاتف يجب أن يحتوي على 10 أرقام فقط"
           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
</div>

            <div class="col-md-6 mt-3">
                <label>العنوان:</label>
                <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($hospitalData['location']) ?>" required>
            </div>

            <div class="col-12 mt-3">
                <label>موقع المستشفى على الخريطة (اسحب المؤشر لتعديل الموقع):</label>
                <div id="map"></div>

                <!-- حقول مخفية لإرسال الإحداثيات -->
                <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($hospitalData['latitude'] ?? '') ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($hospitalData['longitude'] ?? '') ?>">
            </div>
        </div>

        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary">تحديث البيانات</button>
        </div>
    </form>
</div>

<!-- JavaScript لتفعيل الخريطة -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let lat = <?= isset($hospitalData['latitude']) && is_numeric($hospitalData['latitude']) ? $hospitalData['latitude'] : 30.033333 ?>;
        let lng = <?= isset($hospitalData['longitude']) && is_numeric($hospitalData['longitude']) ? $hospitalData['longitude'] : 31.233334 ?>;

        const map = L.map('map').setView([lat, lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        marker.on('dragend', function(e) {
            const pos = marker.getLatLng();
            document.getElementById('latitude').value = pos.lat.toFixed(7);
            document.getElementById('longitude').value = pos.lng.toFixed(7);
        });
    });
</script>
</body>
</html>
