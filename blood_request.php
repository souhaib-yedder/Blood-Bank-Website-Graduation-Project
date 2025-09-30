<?php
// بدء الجلسة والتحقق من صلاحية المستشفى
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: unauthorized.php");  // إذا لم يكن المستخدم مستشفى، يتم توجيهه إلى صفحة غير مصرح بها
    exit();
}

// استخراج المستشفى المسجل دخوله
$hospital_id = $_SESSION['hospital_id']; 

// الاتصال بقاعدة البيانات
require_once 'db.php';
require_once 'class_Hospital.php';

// الاتصال بـ hospital_layout.php لإضافة الهيكل العام
require_once 'hospital_layout.php';

$db = new Database();
$conn = $db->connect();
$hospitalObj = new Hospital($conn);

$success = '';
$error = '';

// معالجة إضافة طلب دم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_request'])) {
    $blood_type = $_POST['blood_type'];
    $blood_component = $_POST['blood_component'];
    $units_needed = $_POST['units_needed'];

    // إضافة طلب دم جديد للمستشفى
    if ($hospitalObj->addBloodRequest($blood_type, $blood_component, $units_needed, $hospital_id)) {
        $success = "✅ تم إضافة طلب الدم بنجاح.";
    } else {
        $error = "❌ حدث خطأ أثناء إضافة طلب الدم.";
    }
}
?>

<!-- محتوى الصفحة -->
<div class="container"> <!-- إزالة المسافة العلوية باستخدام mt-0 -->
    <h2 class="text-center">إضافة طلب دم جديد</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- فورم إضافة طلب دم جديد -->
    <form method="POST">
        <div class="mb-3">
            <label for="blood_type" class="form-label">فصيلة الدم:</label>
            <select name="blood_type" id="blood_type" class="form-control" required>
                <?php foreach ($hospitalObj->getBloodTypes() as $type): ?>
                    <option value="<?= $type ?>"><?= $type ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="blood_component" class="form-label">نوع الدم:</label>
            <select name="blood_component" id="blood_component" class="form-control" required>
                <?php foreach ($hospitalObj->getBloodComponents() as $component): ?>
                    <option value="<?= $component ?>"><?= $component ?></option>
                <?php endforeach; ?>
            </select>
        </div>

      <div class="mb-3">
    <label for="units_needed" class="form-label">الكمية المطلوبة:</label>
    <input type="number" name="units_needed" id="units_needed" 
           class="form-control" required min="1">
</div>


        <button type="submit" name="add_request" class="btn btn-success w-100">إضافة طلب الدم</button>
    </form>
</div>
