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

$user_id = $_SESSION['user_id'];
$requests = $donor->getDonationRequestsByUser($user_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>سجل التبرعات</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" />
  <link rel="stylesheet" href="../css/donor_dashboard.css" />
  <style>
    #searchInput {
      max-width: 350px;
      margin-bottom: 15px;
    }
  </style>
  <script>
    function filterTable() {
      const input = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#requestsTable tbody tr');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
      });
    }
  </script>
</head>
<body>

<?php include 'donor_layout.php'; ?>

<div class="container-fluid px-4 py-4">
  <h2 class="text-center text-danger mb-4">سجل التبرعات</h2>

  <!-- مربع البحث -->
  <input type="text" id="searchInput" class="form-control mx-auto" placeholder="ابحث في سجل التبرعات..." onkeyup="filterTable()">

  <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
      <tr>
        <th>اسم المريض</th>
        <th>فصيلة الدم</th>
        <th>عدد الوحدات</th>
        <th>تاريخ الطلب</th>
        <th>مستعجل؟</th>
        <th>تاريخ العملية</th>
        <th>الحالة</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="7" class="text-center">لا توجد طلبات مسجلة.</td></tr>
      <?php else: ?>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['patient_name']) ?></td>
            <td><?= htmlspecialchars($r['blood_type_needed']) ?></td>
            <td><?= htmlspecialchars($r['units_needed']) ?></td>
            <td><?= htmlspecialchars($r['request_date']) ?></td>
            <td><?= $r['urgent_request'] == 'yes' ? 'نعم' : 'لا' ?></td>
            <td><?= $r['operation_date'] ?? 'غير محدد' ?></td>
            <td>
              <?php
                if ($r['status'] == 'pending') echo '<span class="text-warning">معلق</span>';
                elseif ($r['status'] == 'approved') echo '<span class="text-success">مقبول</span>';
                elseif ($r['status'] == 'rejected') echo '<span class="text-danger">مرفوض</span>';
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
