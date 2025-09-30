<?php
session_start();
require_once 'db.php';
require_once 'class_Users.php';
require_once 'class_Donor.php';




if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$donor = new Donor($conn);
$data = $donor->getDonorProfile($user_id);

if (!$data) {
    echo "<p style='color:red; text-align:center;'>لم يتم العثور على بيانات المتبرع.</p>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $updateData = [
        'user_id' => $user_id,
        'phone' => $_POST['phone'],
        'blood_type' => $_POST['blood_type'],
        'birth_date' => $_POST['birth_date'],
        'address' => $_POST['address'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude']
    ];

    if ($donor->updateDonorProfile($updateData)) {
        echo "<script>alert('تم تحديث البيانات بنجاح');</script>";
        $data = $donor->getDonorProfile($user_id); // إعادة التحديث بعد التعديل
    } else {
        echo "<script>alert('حدث خطأ أثناء التحديث');</script>";
    }
}

$user_name = $_SESSION['name'];
?>

<?php include 'donor_layout.php'; ?>

<!-- ✅ محتوى الصفحة يبدأ هنا -->
<div class="container py-4">
    <div class="profile-container bg-white p-4 rounded shadow-sm">
        <h2 class="text-danger text-center mb-4">الملف الشخصي</h2>
        <div class="row mb-3">
            <div class="col-md-6">
                <label>الاسم الكامل:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['name']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label>البريد الإلكتروني:</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>" disabled>
            </div>
        </div>

        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>رقم الهاتف:</label>
<input 
    type="text" 
    name="phone" 
    class="form-control" 
    value="<?= htmlspecialchars($data['phone']) ?>" 
    required 
    pattern="\d{10}" 
    maxlength="10" 
    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
    title="يجب إدخال 10 أرقام فقط">
                </div>
                <div class="col-md-6">
                    <label>فصيلة الدم:</label>
                    <select name="blood_type" class="form-control" required>
                        <?php
                        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                        foreach ($bloodTypes as $type) {
                            $selected = ($type === $data['blood_type']) ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>تاريخ الميلاد:</label>
                    <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($data['birth_date']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label>آخر تبرع:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($data['last_donation_date'] ?? 'غير متوفر') ?>" disabled>
                </div>
            </div>

            <div class="mb-3">
                <label>العنوان:</label>
                <textarea name="address" class="form-control" required><?= htmlspecialchars($data['address']) ?></textarea>
            </div>

            <div class="mb-4">
                <label>الموقع الجغرافي:</label>
                <div id="map" style="height: 400px; border-radius: 10px;"></div>
                <input type="hidden" name="latitude" id="latitude" value="<?= $data['latitude'] ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= $data['longitude'] ?>">
            </div>

            <button type="submit" name="update" class="btn btn-danger w-100">تحديث البيانات</button>
        </form>
    </div>
</div>

<!-- ✅ مكتبات الخريطة -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  const lat = <?= $data['latitude'] ?? 32.8872 ?>;
  const lng = <?= $data['longitude'] ?? 13.1913 ?>;
  const map = L.map('map').setView([lat, lng], 10);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const marker = L.marker([lat, lng], { draggable: true }).addTo(map)
    .bindPopup('اسحب لتحديد موقعك').openPopup();

  marker.on('dragend', function (e) {
    const pos = marker.getLatLng();
    document.getElementById('latitude').value = pos.lat;
    document.getElementById('longitude').value = pos.lng;
  });

  // تثبيت حجم الخريطة إذا لم تظهر بشكل صحيح
  window.addEventListener('load', function () {
    setTimeout(() => {
      map.invalidateSize();
    }, 500);
  });
</script>
