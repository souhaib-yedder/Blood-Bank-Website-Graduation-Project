<?php
session_start();

require_once 'db.php';
require_once 'class_Donor.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$donor = new Donor($conn);

$donors = $donor->getEligibleDonorsForBankRequest();

// المعالجة عند الضغط على زر "طلب"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donors_id = $_POST['donors_id'];
    $blood_type = $_POST['blood_type'];
    $last_donation_date = $_POST['last_donation_date'];
    $blood_component = $_POST['blood_component'];

    $lastDate = new DateTime($last_donation_date);
    $now = new DateTime();
    $interval = $now->diff($lastDate)->m + ($now->diff($lastDate)->y * 12);

    if ($interval < 4) {
        $_SESSION['message'] = "❌ لا يمكن التبرع. آخر تبرع كان منذ أقل من 4 أشهر.";
    } else {
        $donor->makeBankRequest($donors_id, $blood_type, $blood_component);
        $_SESSION['message'] = "✅ تم إرسال طلب التبرع.";
    }

  

    
   echo "<script>window.location.href='staff_request_from_donors.php';</script>";

    exit;


}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طلب متبرعين - بنك الدم</title>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: "Cairo", sans-serif;
    }
    h2 {
      letter-spacing: 1px;
    }
    .table td, .table th {
      vertical-align: middle;
    }
  </style>
</head>
<body>

<?php if (isset($_SESSION['message'])): ?>
  <script>alert("<?= $_SESSION['message'] ?>");</script>
  <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<div class="container mt-5">
  <h2 class="text-center text-danger fw-bold mb-4">قائمة المتبرعين لطلب الدم</h2>

  <!-- حقل البحث -->
  <div class="mb-3">
    <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث عن متبرع بالاسم أو فصيلة الدم أو رقم الهاتف...">
  </div>

  <!-- جدول المتبرعين -->
  <div class="table-responsive shadow rounded-4 border">
    <table class="table table-bordered table-hover align-middle mb-0" id="donorsTable">
      <thead class="table-danger text-center">
        <tr>
          <th>👤 الاسم</th>
          <th>🩸 فصيلة الدم</th>
          <th>📞 رقم الهاتف</th>
          <th>📅 آخر تبرع</th>
          <th>🔬 نوع الدم المطلوب</th>
          <th>📨 إجراء الطلب</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php foreach ($donors as $row): ?>
          <tr>
            <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
            <td><span class="badge bg-danger fs-6 px-3 py-2"><?= htmlspecialchars($row['blood_type']) ?></span></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['last_donation_date']) ?></td>
            <td>
              <form method="POST" class="d-flex flex-row justify-content-center gap-2 align-items-center">
                <input type="hidden" name="donors_id" value="<?= $row['donors_id'] ?>">
                <input type="hidden" name="blood_type" value="<?= $row['blood_type'] ?>">
                <input type="hidden" name="last_donation_date" value="<?= $row['last_donation_date'] ?>">

                <select name="blood_component" class="form-select form-select-sm w-auto" required>
                  <option value="">اختر</option>
                  <option value="Red Blood Cells">Red Blood Cells</option>
                  <option value="Plasma">Plasma</option>
                  <option value="Whole Blood">Whole Blood</option>
                  <option value="Platelets">Platelets</option>
                </select>
            </td>
            <td>
                <button type="submit" class="btn btn-outline-danger btn-sm">طلب</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- JavaScript للبحث -->
<script>
  document.getElementById('searchInput').addEventListener('keyup', function () {
    var filter = this.value.toLowerCase();
    var rows = document.querySelectorAll("#donorsTable tbody tr");

    rows.forEach(function (row) {
      var text = row.textContent.toLowerCase();
      row.style.display = text.includes(filter) ? '' : 'none';
    });
  });
</script>

</body>
</html>
