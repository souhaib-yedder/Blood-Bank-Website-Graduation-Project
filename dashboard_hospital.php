<?php
session_start();
require_once 'hospital_layout.php';
require_once 'db.php';

// ✅ تحقق من صلاحية المستخدم
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: unauthorized.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect();

    // ✅ جلب hospital_id و hospital_name من الجلسة أو من قاعدة البيانات
    if (!isset($_SESSION['hospital_id']) || !isset($_SESSION['hospital_name'])) {
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT hospitals_id, hospital_name FROM hospitals WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        if ($row) {
            $_SESSION['hospital_id'] = $row['hospitals_id'];
            $_SESSION['hospital_name'] = $row['hospital_name'];
        } else {
            throw new Exception("❌ لا يوجد مستشفى مرتبط بهذا المستخدم.");
        }
    }

    $hospital_id = $_SESSION['hospital_id'];
    $hospital_name = $_SESSION['hospital_name'];

    // ✅ استعلام: عدد طلبات الدم
    $stmt = $conn->prepare("SELECT COUNT(*) FROM blood_requests WHERE hospitals_id = ?");
    $stmt->execute([$hospital_id]);
    $total_blood_requests = $stmt->fetchColumn();

    // ✅ استعلام: عدد المرضى
    $stmt = $conn->prepare("SELECT COUNT(*) FROM patients WHERE hospitals_id = ?");
    $stmt->execute([$hospital_id]);
    $total_patients = $stmt->fetchColumn();


    // ✅ استعلام: عدد الرسائل
    $stmt = $conn->prepare("SELECT COUNT(*) FROM contact_messages WHERE hospital_id = ?");
    $stmt->execute([$hospital_id]);
    $total_blood_stock = $stmt->fetchColumn();


} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
} catch (Exception $ex) {
    die("Error: " . $ex->getMessage());
}
?>

<h2 class="mb-4 text-center text-danger">لوحة تحكم المستشفى: <?= htmlspecialchars($hospital_name) ?></h2>

<div class="row g-4">
  <div class="col-md-12 mb-3 text-center">
    <h4 class="text-muted">عدد طلبات الدم في المستشفى: <?= $total_blood_requests ?></h4>
  </div>

  <div class="col-md-4">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title text-danger">طلبات الدم</h5>
        <p class="card-text fs-3 fw-bold"><?= $total_blood_requests ?></p>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title text-danger">رسائل من دعم الفني</h5>
        <p class="card-text fs-3 fw-bold"><?= $total_blood_stock ?> <span class="text-muted fs-6">(رسالة)</span></p>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card text-center shadow-sm border-0">
      <div class="card-body">
        <h5 class="card-title text-danger">عدد المرضى</h5>
        <p class="card-text fs-3 fw-bold"><?= $total_patients ?></p>
      </div>
    </div>
  </div>
</div>

</div> <!-- إغلاق container -->
</div> <!-- إغلاق page-content -->
</div> <!-- إغلاق wrapper -->
</body>
</html>
