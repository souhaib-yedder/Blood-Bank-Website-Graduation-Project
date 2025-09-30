<?php
session_start();
require_once 'db.php';
require_once 'class_Patient.php';
require_once 'hospital_layout.php';

// تحقق صلاحية المستشفى
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    die("🚫 صلاحية الدخول غير متوفرة.");
}

$db = new Database();
$conn = $db->connect();
$patient = new Patient($conn);

// جلب hospital_id حسب user_id
$stmt = $conn->prepare("SELECT hospitals_id FROM hospitals WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hospital_id = $stmt->fetchColumn();

if (!$hospital_id) {
    die("المستشفى غير موجود.");
}

// جلب id المريض من الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("معرف المريض غير موجود.");
}

$patient_id = intval($_GET['id']);

// جلب بيانات المريض
$current_patient = $patient->getById($patient_id);
if (!$current_patient) {
    die("المريض غير موجود.");
}

if ($current_patient['hospitals_id'] != $hospital_id) {
    die("لا يمكنك تعديل بيانات مريض لمستشفى آخر.");
}

// معالجة تعديل بيانات المريض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'patient_name' => trim($_POST['patient_name']),
        'blood_type' => trim($_POST['blood_type']),
        'urgency_level' => trim($_POST['urgency_level']),
        'needed_units' => intval($_POST['needed_units'])
    ];

    // التحقق من رفع ملف جديد
    if (isset($_FILES['condition_file']) && $_FILES['condition_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // مسح الملف القديم إذا موجود
        if (!empty($current_patient['condition_description']) && file_exists($uploadDir . $current_patient['condition_description'])) {
            unlink($uploadDir . $current_patient['condition_description']);
        }

        $file_name = time() . "_" . basename($_FILES['condition_file']['name']);
        move_uploaded_file($_FILES['condition_file']['tmp_name'], $uploadDir . $file_name);
        $data['condition_description'] = $file_name;
    }

    $success = $patient->update($patient_id, $data);
    if ($success) {
        echo "<div class='alert alert-success'>✅ تم تحديث بيانات المريض بنجاح.</div>";
        $current_patient = $patient->getById($patient_id);
    } else {
        echo "<div class='alert alert-danger'>❌ فشل في تحديث بيانات المريض.</div>";
    }
}
?>

<div class="container mt-4">
    <h3>تعديل بيانات المريض</h3>
    <form method="POST" class="mb-4" enctype="multipart/form-data">
      <div class="mb-3">
    <label>اسم المريض:</label>
    <input type="text" name="patient_name" class="form-control" 
           value="<?= htmlspecialchars($current_patient['patient_name']) ?>" required
           pattern="^[A-Za-z\u0600-\u06FF\s]+$"
           title="الاسم يجب أن يحتوي على حروف فقط (عربية أو إنجليزية)"
           oninput="this.value = this.value.replace(/[^A-Za-z\u0600-\u06FF\s]/g, '')">
</div>

<div class="mb-3">
    <label>فصيلة الدم:</label>
    <select name="blood_type" class="form-control" required>
        <option value="">-- اختر فصيلة الدم --</option>
        <option value="A+" <?= ($current_patient['blood_type'] == 'A+') ? 'selected' : '' ?>>A+</option>
        <option value="A-" <?= ($current_patient['blood_type'] == 'A-') ? 'selected' : '' ?>>A-</option>
        <option value="B+" <?= ($current_patient['blood_type'] == 'B+') ? 'selected' : '' ?>>B+</option>
        <option value="B-" <?= ($current_patient['blood_type'] == 'B-') ? 'selected' : '' ?>>B-</option>
        <option value="AB+" <?= ($current_patient['blood_type'] == 'AB+') ? 'selected' : '' ?>>AB+</option>
        <option value="AB-" <?= ($current_patient['blood_type'] == 'AB-') ? 'selected' : '' ?>>AB-</option>
        <option value="O+" <?= ($current_patient['blood_type'] == 'O+') ? 'selected' : '' ?>>O+</option>
        <option value="O-" <?= ($current_patient['blood_type'] == 'O-') ? 'selected' : '' ?>>O-</option>
    </select>
</div>


        <div class="mb-3">
            <label>مستوى الاستعجال:</label>
            <select name="urgency_level" class="form-control" required>
                <option value="">اختر المستوى</option>
                <option value="عاجلة جداً" <?= $current_patient['urgency_level'] === 'عاجلة جداً' ? 'selected' : '' ?>>عاجلة جداً</option>
                <option value="عاجلة" <?= $current_patient['urgency_level'] === 'عاجلة' ? 'selected' : '' ?>>عاجلة</option>
                <option value="متوسطة" <?= $current_patient['urgency_level'] === 'متوسطة' ? 'selected' : '' ?>>متوسطة</option>
                <option value="منخفضة" <?= $current_patient['urgency_level'] === 'منخفضة' ? 'selected' : '' ?>>منخفضة</option>
            </select>
        </div>

        <div class="mb-3">
            <label>ملف الحالة الحالي:</label><br>
            <?php if (!empty($current_patient['condition_description'])): ?>
                <button type="button" class="btn btn-info" onclick="window.open('uploads/<?= htmlspecialchars($current_patient['condition_description']) ?>','_blank')">
                    📄 عرض الحالة
                </button>
            <?php else: ?>
                لا يوجد ملف
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label>تغيير ملف الحالة:</label>
            <input type="file" name="condition_file" class="form-control" required>
            <small class="text-muted">رفع ملف جديد سيستبدل الملف القديم.</small>
        </div>

        <div class="mb-3">
            <label>عدد الوحدات المطلوبة:</label>
            <input type="number" name="needed_units" class="form-control" min="1" value="<?= htmlspecialchars($current_patient['needed_units']) ?>" required>
        </div>

        <button type="submit" class="btn btn-success">تحديث البيانات</button>
        <a href="patient_registration.php" class="btn btn-secondary">رجوع</a>
    </form>
</div>
