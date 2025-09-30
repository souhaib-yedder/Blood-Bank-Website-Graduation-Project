<?php
session_start();
require_once 'db.php';
require_once 'class_Users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = new Users($conn);
    $result = $users->registerDonor($_POST);

    if ($result === true) {
        echo "<script>alert('تم التسجيل بنجاح'); window.location.href='login.php';</script>";
        exit;
    } else {
        $errorMsg = "";
        switch ($result) {
            case "invalid_email":
                $errorMsg = "البريد الإلكتروني غير صالح";
                break;
            case "duplicate_email":
                $errorMsg = "هذا البريد الإلكتروني مسجل مسبقًا";
                break;
            case "invalid_phone":
                $errorMsg = "رقم الهاتف يجب أن يكون 10 أرقام فقط";
                break;
            case "weak_password":
                $errorMsg = "كلمة المرور ضعيفة: يجب أن تحتوي على حرف صغير، حرف كبير، رقم، رمز خاص، وطول 12 أو أكثر";
                break;
            default:
                $errorMsg = "حدث خطأ أثناء التسجيل، حاول مرة أخرى";
        }
        echo "<script>alert('$errorMsg');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>تسجيل متبرع</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    body { font-family: 'Cairo', sans-serif; background-color: #f0f2f5; padding: 40px 0; }
    .container { max-width: 950px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
    h2 { font-weight: bold; margin-bottom: 30px; }
    .form-label { font-weight: 600; }
    #map { height: 350px; border-radius: 8px; }
    .btn-danger { transition: 0.3s; }
    .btn-danger:hover { background-color: #c82333; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 5px #ff4d4d; border-color: #ff4d4d; }
    .card-header { cursor: pointer; }
    small.text-muted { font-size: 12px; }
  </style>
</head>
<body>

<div class="container">
  <h2 class="text-center text-danger">تسجيل متبرع</h2>

  <form method="POST">
    <!-- بيانات شخصية -->
    <div class="card mb-3">
      <div class="card-header bg-danger text-white" data-bs-toggle="collapse" href="#personalInfo">
        <i class="bi bi-person-circle"></i> البيانات الشخصية
      </div>
      <div class="collapse show" id="personalInfo">
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-person"></i> الاسم الكامل:</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-envelope"></i> البريد الإلكتروني:</label>
              <input type="email" name="email" class="form-control" required>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-lock"></i> كلمة المرور:</label>
              <input type="password" name="password" class="form-control" required>
              <small class="text-muted">يجب أن تحتوي على: حرف صغير، حرف كبير، رقم، رمز خاص، والطول 12 أو أكثر</small>
            </div>
            <div class="col-md-6">
              <label class="form-label"><i class="bi bi-telephone"></i> رقم الهاتف:</label>
              <input type="text" name="phone" class="form-control" required>
              <small class="text-muted">10 أرقام فقط</small>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label"><i class="bi bi-calendar"></i> تاريخ الميلاد:</label>
              <input type="date" name="birth_date" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><i class="bi bi-gender-ambiguous"></i> الجنس:</label>
              <select name="gender" class="form-select" required>
                <option value="">-- اختر --</option>
                <option value="ذكر">ذكر</option>
                <option value="أنثى">أنثى</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><i class="bi bi-droplet-half"></i> فصيلة الدم:</label>
              <select name="blood_type" class="form-select" required>
                <option value="">-- اختر --</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-geo-alt"></i> العنوان:</label>
            <input type="text" name="address" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label"><i class="bi bi-heart-pulse"></i> تاريخ آخر تبرع بالدم:</label>
            <input type="date" name="last_donation_date" class="form-control" required>
          </div>
        </div>
      </div>
    </div>

    <!-- تحديد الموقع -->
    <div class="card mb-3">
      <div class="card-header bg-danger text-white">
        <i class="bi bi-map"></i> تحديد الموقع
      </div>
      <div class="card-body">
        <div id="map"></div>
        <input type="hidden" name="latitude" id="latitude" required>
        <input type="hidden" name="longitude" id="longitude" required>
      </div>
    </div>

    <button type="submit" class="btn btn-danger w-100 py-2">تسجيل</button>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const map = L.map('map').setView([32.8872, 13.1913], 10);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const marker = L.marker([32.8872, 13.1913], { draggable: true }).addTo(map)
    .bindPopup('اسحب لتحديد موقعك').openPopup();

  marker.on('dragend', function(e) {
    const pos = marker.getLatLng();
    document.getElementById('latitude').value = pos.lat;
    document.getElementById('longitude').value = pos.lng;
  });
</script>
</body>
</html>
