<?php
session_start();
require_once 'db.php';
require_once 'class_Users.php';
require_once 'class_FileUploader.php';

$db = new Database();
$conn = $db->connect();
$user = new Users($conn);
$uploader = new FileUploader();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']);
    $email        = trim($_POST['email']);
    $password     = $_POST['password'];

    $hospitalName = trim($_POST['hospital_name']);
    $phone        = trim($_POST['phone']);
    $location     = trim($_POST['location']);
    $latitude     = $_POST['latitude'];
    $longitude    = $_POST['longitude'];

    // ✅ تحقق من الإيميل
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('البريد الإلكتروني غير صالح'); history.back();</script>");
    }

    // ✅ تحقق من أن البريد الإلكتروني غير مكرر
    $checkEmail = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetchColumn() > 0) {
        die("<script>alert('هذا البريد الإلكتروني مستخدم بالفعل، الرجاء اختيار بريد آخر.'); history.back();</script>");
    }

    // ✅ تحقق من رقم الهاتف (10 أرقام)
    if (!preg_match("/^\d{10}$/", $phone)) {
        die("<script>alert('رقم الهاتف يجب أن يكون مكون من 10 أرقام'); history.back();</script>");
    }

    // ✅ تحقق من كلمة المرور
    $passwordPattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/";
    if (!preg_match($passwordPattern, $password)) {
        die("<script>alert('كلمة المرور يجب أن تحتوي على: \\n- حرف صغير \\n- حرف كبير \\n- رقم \\n- رمز خاص \\n- طول 12 أو أكثر'); history.back();</script>");
    }

    // ✅ تحقق من الإحداثيات (latitude & longitude)
    if (empty($latitude) || empty($longitude) || !is_numeric($latitude) || !is_numeric($longitude)) {
        die("<script>alert('الرجاء تحديد موقع المستشفى على الخريطة.'); history.back();</script>");
    }

    // ✅ رفع الملفات
    $letterFile   = $uploader->upload($_FILES['official_letter'], 'letter_');
    $licenseFile  = $uploader->upload($_FILES['license'], 'license_');
    $taxFile      = $uploader->upload($_FILES['tax_id'], 'tax_');
    $idFile       = $uploader->upload($_FILES['id_card'], 'id_');

    if ($letterFile && $licenseFile && $taxFile && $idFile) {
        $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'hospital', 0)");
        $stmt_user->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $user_id = $conn->lastInsertId();

        $stmt_hospital = $conn->prepare("INSERT INTO hospitals (user_id, hospital_name, phone, location, latitude, longitude, letter_file, license_file, tax_file, id_file, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_hospital->execute([
            $user_id, $hospitalName, $phone, $location, $latitude, $longitude,
            $letterFile, $licenseFile, $taxFile, $idFile
        ]);

        echo "<script>alert('تم تسجيل المستشفى بنجاح. بانتظار موافقة الإدارة.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء رفع الملفات.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل مستشفى</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f0f2f5; padding: 40px 0; }
        .container { max-width: 950px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
        h2 { font-weight: bold; margin-bottom: 30px; }
        .form-label { font-weight: 600; }
        .file-input { padding: 10px; }
        #map { height: 350px; border-radius: 8px; }
        .btn-primary { transition: 0.3s; }
        .btn-primary:hover { background-color: #0056b3; }
        .form-control:focus { box-shadow: 0 0 5px #ff4d4d; border-color: #ff4d4d; }
        .card-header { cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center text-danger">تسجيل مستشفى</h2>

    <form method="POST" enctype="multipart/form-data">
        <!-- بيانات المستشفى -->
        <div class="card mb-3">
            <div class="card-header bg-danger text-white" data-bs-toggle="collapse" href="#hospitalInfo">
                <i class="bi bi-building"></i> بيانات المستشفى
            </div>
            <div class="collapse show" id="hospitalInfo">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-hospital"></i> اسم المستشفى:</label>
                            <input type="text" name="hospital_name" class="form-control" placeholder="أدخل اسم المستشفى" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-telephone"></i> رقم الهاتف:</label>
                            <input type="text" name="phone" class="form-control" placeholder="أدخل رقم الهاتف" 
                                   required pattern="^\d{10}$" 
                                   title="الرجاء إدخال رقم مكون من 10 أرقام">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-geo-alt"></i> وصف الموقع (اختياري):</label>
                        <input type="text" name="location" class="form-control" placeholder="مثال: طرابلس - ليبيا">
                    </div>
                </div>
            </div>
        </div>

        <!-- بيانات المسؤول -->
        <div class="card mb-3">
            <div class="card-header bg-danger text-white" data-bs-toggle="collapse" href="#adminInfo">
                <i class="bi bi-person-circle"></i> بيانات المسؤول
            </div>
            <div class="collapse show" id="adminInfo">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-person"></i> اسم المسؤول:</label>
                            <input type="text" name="name" class="form-control" placeholder="أدخل اسمك" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-envelope"></i> البريد الإلكتروني:</label>
                            <input type="email" name="email" class="form-control" placeholder="أدخل البريد الإلكتروني" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-lock"></i> كلمة المرور:</label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="أدخل كلمة المرور" 
                               required minlength="12"
                               title="كلمة المرور يجب أن تكون 12 خانة أو أكثر">
                    </div>
                </div>
            </div>
        </div>

        <!-- خريطة -->
        <div class="card mb-3">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-map"></i> تحديد موقع المستشفى
            </div>
            <div class="card-body">
                <div id="map"></div>
                <input type="hidden" name="latitude" id="latitude" required>
                <input type="hidden" name="longitude" id="longitude" required>
            </div>
        </div>

        <!-- رفع الملفات -->
        <div class="card mb-3">
            <div class="card-header bg-danger text-white" data-bs-toggle="collapse" href="#filesInfo">
                <i class="bi bi-file-earmark-arrow-up"></i> رفع الملفات
            </div>
            <div class="collapse show" id="filesInfo">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">📄 كتاب رسمي موجه لإدارة الموقع:</label>
                        <input type="file" name="official_letter" class="form-control file-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">📄 رخصة تشغيل المستشفى:</label>
                        <input type="file" name="license" class="form-control file-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">📄 البطاقة الضريبية أو السجل التجاري:</label>
                        <input type="file" name="tax_id" class="form-control file-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">📄 بطاقة هوية الممثل الرسمي:</label>
                        <input type="file" name="id_card" class="form-control file-input" required>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">تسجيل المستشفى</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const map = L.map('map').setView([32.8872, 13.1913], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([32.8872, 13.1913], { draggable: true }).addTo(map)
        .bindPopup('اسحب لتحديد موقع المستشفى').openPopup();

    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        document.getElementById('latitude').value = pos.lat;
        document.getElementById('longitude').value = pos.lng;
    });
</script>

</body>
</html>
