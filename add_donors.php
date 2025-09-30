<?php
session_start();
require_once 'db.php';
require_once 'class_Donor.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$conn = (new Database())->connect();
$donorObj = new Donor($conn);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    // التحقق من الاسم (العربية والانجليزية فقط)
    if (empty($_POST['name']) || !preg_match("/^[\p{Arabic}A-Za-z\s]+$/u", $_POST['name'])) {
        $errors[] = "الاسم يجب أن يحتوي على حروف فقط (عربية أو إنجليزية).";
    }

    // التحقق من البريد الإلكتروني
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email=?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "البريد الإلكتروني مسجل مسبقًا.";
        }
    }

    // التحقق من رقم الهاتف
    if (!preg_match('/^\d{10}$/', $_POST['phone'])) {
        $errors[] = "رقم الهاتف يجب أن يكون 10 أرقام فقط.";
    }

    // التحقق من كلمة المرور
    $password = $_POST['password'];
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $password)) {
        $errors[] = "كلمة المرور ضعيفة، يجب أن تحتوي على 12 حرفًا على الأقل مع حرف كبير وصغير ورقم ورمز.";
    }

    // التحقق من باقي الحقول المطلوبة
    $requiredFields = ['birth_date','gender','blood_type','address','last_donation_date','latitude','longitude'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "يرجى ملء جميع الحقول المطلوبة.";
            break;
        }
    }

    // إذا لم توجد أخطاء → تسجيل باستخدام الدالة
    if (empty($errors)) {
        $result = $donorObj->registerDonor($_POST);
        if ($result['success'] === true) {
            $message = "<div class='alert alert-success'>تم التسجيل بنجاح.</div>";
        } else {
            // عرض رسالة الخطأ من الدالة
            $message = "<div class='alert alert-danger'><ul>";
            if (isset($result['message'])) {
                $message .= "<li>{$result['message']}</li>";
            } elseif (isset($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $message .= "<li>{$err}</li>";
                }
            } else {
                $message .= "<li>حدث خطأ أثناء التسجيل.</li>";
            }
            $message .= "</ul></div>";
        }
    } else {
        // عرض الأخطاء من الفاليديشـن بالصفحة
        $message = "<div class='alert alert-danger'><ul>";
        foreach ($errors as $err) {
            $message .= "<li>{$err}</li>";
        }
        $message .= "</ul></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<title>إضافة متبرع جديد</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
<div class="container py-5">
<h2 class="text-center text-danger mb-4">إضافة متبرع جديد</h2>

<?= $message ?>

<form method="POST" novalidate>
  <div class="row mb-3">
    <div class="col-md-6">
      <label class="form-label">الاسم الكامل:</label>
      <input type="text" name="name" class="form-control" required
             pattern="^[\p{Arabic}A-Za-z\s]+$"
             title="الاسم يجب أن يحتوي على حروف فقط (عربية أو إنجليزية)">
    </div>

    <div class="col-md-6">
      <label>البريد الإلكتروني:</label>
      <input type="email" name="email" class="form-control" required
             pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
             title="يرجى إدخال بريد إلكتروني صالح">
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-6">
      <label>كلمة المرور:</label>
      <input type="password" name="password" class="form-control" required
             pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$"
             title="12 حرفًا على الأقل، تحتوي على حرف كبير وصغير ورقم ورمز">
    </div>
    <div class="col-md-6">
      <label>رقم الهاتف:</label>
      <input type="text" name="phone" class="form-control" required
             pattern="\d{10}"
             title="رقم الهاتف يجب أن يحتوي على 10 أرقام">
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-4">
      <label>تاريخ الميلاد:</label>
      <input type="date" name="birth_date" class="form-control" required />
    </div>
    <div class="col-md-4">
      <label>الجنس:</label>
      <select name="gender" class="form-select" required>
        <option value="">-- اختر --</option>
        <option value="ذكر">ذكر</option>
        <option value="أنثى">أنثى</option>
      </select>
    </div>
    <div class="col-md-4">
      <label>فصيلة الدم:</label>
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
    <label>العنوان:</label>
    <input type="text" name="address" class="form-control" required />
  </div>

  <div class="mb-3">
    <label>تاريخ آخر تبرع بالدم:</label>
    <input type="date" name="last_donation_date" class="form-control" required />
  </div>

  <div class="mb-3">
    <label>اختر موقعك على الخريطة:</label>
    <div id="map" style="height: 400px; border-radius: 8px;"></div>
    <input type="hidden" name="latitude" id="latitude" required />
    <input type="hidden" name="longitude" id="longitude" required />
  </div>

  <button type="submit" class="btn btn-danger w-100">تسجيل</button>
</form>
</div>

<script>
const initialLat = 32.8872;
const initialLng = 13.1913;
const map = L.map('map').setView([initialLat, initialLng], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map)
  .bindPopup('اسحب لتحديد موقعك').openPopup();

marker.on('dragend', function(e) {
  const pos = marker.getLatLng();
  document.getElementById('latitude').value = pos.lat;
  document.getElementById('longitude').value = pos.lng;
});

// تعبئة الموقع الافتراضي
document.getElementById('latitude').value = initialLat;
document.getElementById('longitude').value = initialLng;
</script>
</body>
</html>
