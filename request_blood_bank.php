<?php
session_start();
require_once 'db.php';
require_once 'class_Donor.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$donor = new Donor($conn);

// ✅ استخراج donors_id و last_donation_date باستخدام user_id من الجلسة
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT donors_id, last_donation_date FROM donors WHERE user_id = ?");
$stmt->execute([$user_id]);
$donorRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donorRow) {
    die("❌ لم يتم العثور على بيانات المتبرع.");
}

$donors_id = $donorRow['donors_id'];
$last_donation_date = $donorRow['last_donation_date'];

$message = '';

// ✅ عند إرسال الفورم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_type = $_POST['blood_type'];
    $blood_component = $_POST['blood_component'];
    $message = $donor->requestFromBloodBank($donors_id, $blood_type, $blood_component);
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طلب دم من بنك الدم</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">

  <script>
    function checkLastDonation() {
      const lastDate = new Date('<?php echo $last_donation_date ?? "1900-01-01"; ?>');
      const now = new Date();
      const months = (now.getFullYear() - lastDate.getFullYear()) * 12 + now.getMonth() - lastDate.getMonth();
      if (months < 4) {
        alert("⚠️ لا يمكنك طلب دم لأن آخر تبرع كان منذ أقل من 4 أشهر.");
        return false;
      }
      return true;
    }
  </script>
</head>
<body>

<?php include 'donor_layout.php'; ?>

<div class="container py-4">
  <h2 class="text-center text-danger mb-4">طلب دم من بنك الدم</h2>

  <?php if (!empty($message)): ?>
    <div class="alert alert-info text-center"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST" onsubmit="return checkLastDonation();">
    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label fw-bold">فصيلة الدم:</label>
        <select name="blood_type" class="form-select" required>
          <option value="">-- اختر فصيلة الدم --</option>
          <?php
          $types = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
          foreach ($types as $type) {
              echo "<option value='$type'>$type</option>";
          }
          ?>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-bold">مكون الدم:</label>
        <select name="blood_component" class="form-select" required>
          <option value="">-- اختر المكون --</option>
          <option value="Plasma">Plasma</option>
          <option value="Red Blood Cells">Red Blood Cells</option>
          <option value="Platelets">Platelets</option>
          <option value="Whole Blood">Whole Blood</option>
        </select>
      </div>

      <div class="col-md-12 d-flex justify-content-center mt-4">
        <button type="submit" class="btn btn-danger px-5">إرسال الطلب</button>
      </div>
    </div>
  </form>
</div>
</body>
</html>
