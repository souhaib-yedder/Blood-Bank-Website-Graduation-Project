<?php
session_start();
require_once 'db.php';
require_once 'class_Donor.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

require_once 'staff_layout.php';


$db = new Database();
$conn = $db->connect();
$donor = new Donor($conn);

// ✅ تحديث حالة الطلب عند الضغط على زر قبول أو رفض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;

    if ($request_id) {
        if (isset($_POST['accept'])) {
            $donor->updateBankRequestStatus($request_id, 'completed');
            $_SESSION['message'] = "✅ تم قبول الطلب.";
        } elseif (isset($_POST['reject'])) {
            $donor->updateBankRequestStatus($request_id, 'cancelled');
            $_SESSION['message'] = "❌ تم رفض الطلب.";
        }
     
   }
}

// ✅ جلب الطلبات حسب الحالة
$pendingRequests   = $donor->getBloodBankRequestsWithDonorName('donor', 'pending');
$completedRequests = $donor->getBloodBankRequestsWithDonorName('donor', 'completed');
$cancelledRequests = $donor->getBloodBankRequestsWithDonorName('donor', 'cancelled');
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طلبات بنك الدم من المتبرعين</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: "Cairo", sans-serif;
    }
    .table td, .table th {
      vertical-align: middle;
    }
    input.search-input {
      max-width: 350px;
      margin: 10px auto;
    }
  </style>
</head>
<body>

<?php if (isset($_SESSION['message'])): ?>
  <script>alert("<?= $_SESSION['message'] ?>");</script>
  <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="container mt-5">
  <h2 class="text-center text-danger mb-4">الطلبات الواردة من المتبرعين لبنك الدم</h2>

  <!-- ✅ الطلبات المعلقة -->
  <div class="mb-5">
    <h5 class="text-danger">الطلبات المعلقة</h5>
    <input type="text" id="searchPending" class="form-control search-input text-center" placeholder="🔍 ابحث في الطلبات المعلقة...">
    <div class="table-responsive border rounded">
      <table class="table table-bordered table-hover align-middle" id="pendingTable">
        <thead class="table-danger text-center">
          <tr>
            <th>📅 تاريخ الطلب</th>
            <th>🧑‍🩺 اسم المتبرع</th>
            <th>🩸 فصيلة الدم</th>
            <th>🔬 نوع الدم</th>
            <th>📌 الحالة</th>
            <th>📨 إجراء</th>
          </tr>
        </thead>
        <tbody class="text-center">
          <?php foreach ($pendingRequests as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['request_date']) ?></td>
              <td><?= htmlspecialchars($row['donor_name']) ?></td>
              <td><span class="badge bg-danger fs-6 px-3 py-2"><?= htmlspecialchars($row['blood_type']) ?></span></td>
              <td><?= htmlspecialchars($row['blood_component']) ?></td>
              <td><span class="badge bg-warning text-dark">معلق</span></td>
              <td>
                <form method="POST" class="d-flex justify-content-center gap-2">
                  <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                  <button type="submit" name="accept" class="btn btn-sm btn-success">قبول</button>
                  <button type="submit" name="reject" class="btn btn-sm btn-danger">رفض</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ✅ الطلبات المقبولة -->
  <div class="mb-5">
    <h5 class="text-success">الطلبات المقبولة</h5>
    <input type="text" id="searchAccepted" class="form-control search-input text-center" placeholder="🔍 ابحث في الطلبات المقبولة...">
    <div class="table-responsive border rounded">
      <table class="table table-bordered table-hover align-middle" id="acceptedTable">
        <thead class="table-success text-center">
          <tr>
            <th>📅 تاريخ الطلب</th>
            <th>🧑‍🩺 اسم المتبرع</th>
            <th>🩸 فصيلة الدم</th>
            <th>🔬 نوع الدم</th>
            <th>📌 الحالة</th>
          </tr>
        </thead>
        <tbody class="text-center">
          <?php foreach ($completedRequests as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['request_date']) ?></td>
              <td><?= htmlspecialchars($row['donor_name']) ?></td>
              <td><span class="badge bg-danger fs-6 px-3 py-2"><?= htmlspecialchars($row['blood_type']) ?></span></td>
              <td><?= htmlspecialchars($row['blood_component']) ?></td>
              <td><span class="badge bg-success">تم القبول</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ✅ الطلبات المرفوضة -->
  <div class="mb-5">
    <h5 class="text-secondary">الطلبات المرفوضة</h5>
    <input type="text" id="searchRejected" class="form-control search-input text-center" placeholder="🔍 ابحث في الطلبات المرفوضة...">
    <div class="table-responsive border rounded">
      <table class="table table-bordered table-hover align-middle" id="rejectedTable">
        <thead class="table-secondary text-center">
          <tr>
            <th>📅 تاريخ الطلب</th>
            <th>🧑‍🩺 اسم المتبرع</th>
            <th>🩸 فصيلة الدم</th>
            <th>🔬 نوع الدم</th>
            <th>📌 الحالة</th>
          </tr>
        </thead>
        <tbody class="text-center">
          <?php foreach ($cancelledRequests as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['request_date']) ?></td>
              <td><?= htmlspecialchars($row['donor_name']) ?></td>
              <td><span class="badge bg-danger fs-6 px-3 py-2"><?= htmlspecialchars($row['blood_type']) ?></span></td>
              <td><?= htmlspecialchars($row['blood_component']) ?></td>
              <td><span class="badge bg-dark">مرفوض</span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ✅ سكربت البحث لكل جدول -->
<script>
function setupSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  input.addEventListener("keyup", function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);

    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(filter) ? "" : "none";
    });
  });
}

// تطبيق البحث على كل جدول
setupSearch("searchPending", "pendingTable");
setupSearch("searchAccepted", "acceptedTable");
setupSearch("searchRejected", "rejectedTable");
</script>

</body>
</html>
