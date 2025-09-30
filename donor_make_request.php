<?php
require_once 'db.php';
require_once 'class_Donor.php';
session_start();

// تحقق من الدخول وصلاحية المتبرع
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$donor = new Donor($conn);
$user_id = $_SESSION['user_id'];

// ✅ تخزين بيانات الشخص المطلوب التبرع منه في الجلسة
if (isset($_GET['target_id'])) {
    $target_id = $_GET['target_id'];
    $stmt = $conn->prepare("SELECT u.name, u.email FROM users u WHERE u.user_id = ?");
    $stmt->execute([$target_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($target_user) {
        $_SESSION['target_user'] = $target_user;
    }
}

// ✅ جلب donors_id للشخص المستهدف (المطلوب منه الدم)
$target_id = $_GET['target_id'] ?? null;
$target_donor_id = null;

if ($target_id) {
    $stmt = $conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
    $stmt->execute([$target_id]);
    $targetData = $stmt->fetch(PDO::FETCH_ASSOC);
    $target_donor_id = $targetData['donors_id'] ?? null;
}

if (!$target_donor_id) {
    die("❌ لا يمكن تحديد المتبرع المطلوب منه الدم.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_type      = $_POST['blood_type'];
    $units           = $_POST['units_needed'];
    $patient_name    = $_POST['patient_name'];
    $hospital_name   = $_POST['hospital_name'];
    $urgent          = $_POST['urgent_request'];
    $operation_date  = !empty($_POST['operation_date']) ? $_POST['operation_date'] : null;
    $diagnosis       = $_POST['diagnosis'];
    $blood_component = $_POST['blood_component'];


    try {
        $donor->makeBloodRequest($target_donor_id, $blood_type, $units, $patient_name, $hospital_name, $urgent, $operation_date, $diagnosis, $blood_component);

        // إرسال البريد الإلكتروني إلى المتبرع الهدف
        if (isset($_SESSION['target_user'])) {
            $target = $_SESSION['target_user'];
            $donor->sendEmailToDonor($target['email'], $target['name'], [
                'patient_name'    => $patient_name,
                'hospital_name'   => $hospital_name,
                'blood_type'      => $blood_type,
                'units_needed'    => $units,
                'urgent_request'  => $urgent,
                'operation_date'  => $operation_date,
                'diagnosis'       => $diagnosis,
                'blood_component' => $blood_component
            ]);
            unset($_SESSION['target_user']);
        }

        $success = "✅ تم إرسال الطلب بنجاح. تم إخبار المتبرع عبر البريد الإلكتروني.";
    } catch (Exception $e) {
        $error = "❌ خطأ في إرسال الطلب: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>تقديم طلب تبرع</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="../css/donor_dashboard.css"> <!-- تأكد من صحة المسار -->
</head>
<body>

<?php include 'donor_layout.php'; ?>

<div class="container-fluid px-4 py-4">
  <h2 class="text-center text-danger mb-4">تقديم طلب تبرع بالدم</h2>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST">
<div class="mb-3">
  <label class="form-label">اسم المريض الثلاثي:</label>
  <input type="text" name="patient_name" class="form-control" required
         pattern="^[A-Za-z\u0600-\u06FF\s]+$"
         title="الاسم يجب أن يحتوي على حروف فقط (عربية أو إنجليزية)"
         oninput="this.value = this.value.replace(/[^A-Za-z\u0600-\u06FF\s]/g, '')">
</div>

    <div class="mb-3">
      <label class="form-label">اسم المستشفى:</label>
      <input type="text" name="hospital_name" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">فصيلة الدم:</label>
      <select name="blood_type" class="form-select" required>
        <option value="">-- اختر --</option>
        <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type): ?>
          <option value="<?= $type ?>"><?= $type ?></option>
        <?php endforeach; ?>
      </select>
    </div>

   <div class="mb-3">
  <label class="form-label">عدد الوحدات المطلوبة:</label>
  <input type="number" name="units_needed" class="form-control" required min="1">
</div>


    <div class="mb-3">
      <label class="form-label">هل هو طلب مستعجل؟</label>
      <select name="urgent_request" class="form-select">
        <option value="no">لا</option>
        <option value="yes">نعم</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">تاريخ العملية (اختياري):</label>
      <input type="date" name="operation_date" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">تشخيص الحالة:</label>
      <textarea name="diagnosis" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">نوع مكون الدم المطلوب:</label>
      <select name="blood_component" class="form-select" required>
        <option value="">-- اختر --</option>
        <option value="خلايا دم حمراء">خلايا دم حمراء</option>
        <option value="صفائح دموية">صفائح دموية</option>
        <option value="بلازما">بلازما</option>
        <option value="دم كامل">دم كامل</option>
      </select>
    </div>

    <button type="submit" class="btn btn-danger w-100">إرسال الطلب</button>
  </form>
</div>

</body>
</html>
