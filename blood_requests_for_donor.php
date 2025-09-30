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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action     = $_POST['action'];
    $request_type = $_POST['request_type'] ?? 'donor';

    if ($request_type === 'donor' && in_array($action, ['accept', 'reject'])) {
        $status = ($action === 'accept') ? 'approved' : 'rejected';
        $donor->updateRequestStatus($request_id, $status);
    } elseif ($request_type === 'bank' && in_array($action, ['accept', 'reject'])) {
        $status = ($action === 'accept') ? 'completed' : 'cancelled';
        $donor->updateIncomingBankRequestStatus($request_id, $status);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$requests = $donor->getAllRequests($user_id);



// جلب donors_id للمتبرع الحالي
$stmt = $conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
$stmt->execute([$user_id]);
$donorRow = $stmt->fetch(PDO::FETCH_ASSOC);
$donors_id = $donorRow['donors_id'] ?? null;

// جلب الطلبات الخاصة بالمتبرع الحالي فقط
$incomingBankRequests  = $donor->getIncomingBankRequests($donors_id);
$processedBankRequests = $donor->getProcessedBankRequests($donors_id);



?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <title>إدارة طلبات التبرع</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" />
  <link rel="stylesheet" href="../css/donor_dashboard.css" />
  <style>
    .search-input {
      max-width: 350px;
      margin-bottom: 10px;
    }
  </style>
  <script>
    function filterTable(inputId, tableId) {
      const input = document.getElementById(inputId).value.toLowerCase();
      const rows = document.querySelectorAll('#' + tableId + ' tbody tr');

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

  <h2 class="text-center text-danger mb-4">طلبات التبرع بالدم الخاصة بك</h2>

  <!-- بحث جدول الطلبات -->
  <input type="text" id="searchRequests" class="form-control search-input mx-auto" placeholder="ابحث في طلباتك..." onkeyup="filterTable('searchRequests', 'requestsTable')">

  <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
      <tr>
        <th>اسم المريض</th>
        <th>فصيلة الدم</th>
        <th>الوحدات</th>
        <th>التاريخ</th>
        <th>مستعجل؟</th>
        <th>تاريخ العملية</th>
        <th>الحالة</th>
        <th>الإجراء</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($requests)): ?>
        <tr><td colspan="8" class="text-center">لا توجد طلبات حالياً.</td></tr>
      <?php else: ?>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['patient_name']) ?></td>
            <td><?= htmlspecialchars($r['blood_type_needed']) ?></td>
            <td><?= htmlspecialchars($r['units_needed']) ?></td>
            <td><?= htmlspecialchars($r['request_date']) ?></td>
            <td><?= $r['urgent_request'] === 'yes' ? 'نعم' : 'لا' ?></td>
            <td><?= $r['operation_date'] ?? 'غير محدد' ?></td>
            <td>
              <?php
                $status = $r['status'];
                if ($status === 'pending') echo '<span class="text-warning">معلق</span>';
                elseif ($status === 'approved') echo '<span class="text-success">مقبول</span>';
                elseif ($status === 'rejected') echo '<span class="text-danger">مرفوض</span>';
                else echo htmlspecialchars($status);
              ?>
            </td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="request_id" value="<?= $r['blood_donor_requests_id'] ?>" />
                  <input type="hidden" name="action" value="accept" />
                  <input type="hidden" name="request_type" value="donor" />
                  <button class="btn btn-sm btn-success">قبول</button>
                </form>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="request_id" value="<?= $r['blood_donor_requests_id'] ?>" />
                  <input type="hidden" name="action" value="reject" />
                  <input type="hidden" name="request_type" value="donor" />
                  <button class="btn btn-sm btn-danger">رفض</button>
                </form>
              <?php else: ?>
                <span class="text-muted">لا يوجد إجراء</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- طلبات بنك الدم الواردة -->
  <h3 class="text-center text-primary mt-5 mb-3">طلبات بنك الدم (الواردة)</h3>

  <input type="text" id="searchIncomingBank" class="form-control search-input mx-auto" placeholder="ابحث في الطلبات الواردة..." onkeyup="filterTable('searchIncomingBank', 'incomingBankTable')">

  <table class="table table-bordered table-striped" id="incomingBankTable">
    <thead class="table-primary">
      <tr>
        <th>تاريخ الطلب</th>
        <th>فصيلة الدم</th>
        <th>نوع الدم</th>
        <th>الإجراء</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($incomingBankRequests)): ?>
        <tr><td colspan="4" class="text-center">لا توجد طلبات واردة حالياً.</td></tr>
      <?php else: ?>
        <?php foreach ($incomingBankRequests as $req): ?>
          <tr>
            <td><?= htmlspecialchars($req['request_date']) ?></td>
            <td><?= htmlspecialchars($req['blood_type']) ?></td>
            <td><?= htmlspecialchars($req['blood_component']) ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>" />
                <input type="hidden" name="action" value="accept" />
                <input type="hidden" name="request_type" value="bank" />
                <button class="btn btn-sm btn-success">قبول</button>
              </form>
              <form method="POST" class="d-inline">
                <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>" />
                <input type="hidden" name="action" value="reject" />
                <input type="hidden" name="request_type" value="bank" />
                <button class="btn btn-sm btn-danger">رفض</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- الطلبات المعالجة -->
  <h3 class="text-center text-secondary mt-5 mb-3">الطلبات المعالجة من بنك الدم</h3>

  <input type="text" id="searchProcessedBank" class="form-control search-input mx-auto" placeholder="ابحث في الطلبات المعالجة..." onkeyup="filterTable('searchProcessedBank', 'processedBankTable')">

  <table class="table table-bordered table-striped" id="processedBankTable">
    <thead class="table-secondary">
      <tr>
        <th>تاريخ الطلب</th>
        <th>فصيلة الدم</th>
        <th>نوع الدم</th>
        <th>الحالة</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($processedBankRequests)): ?>
        <tr><td colspan="4" class="text-center">لا توجد طلبات مقبولة أو مرفوضة.</td></tr>
      <?php else: ?>
        <?php foreach ($processedBankRequests as $req): ?>
          <tr>
            <td><?= htmlspecialchars($req['request_date']) ?></td>
            <td><?= htmlspecialchars($req['blood_type']) ?></td>
            <td><?= htmlspecialchars($req['blood_component']) ?></td>
            <td>
              <?= $req['status'] === 'completed' ? '<span class="text-success">مقبول</span>' : '<span class="text-danger">مرفوض</span>' ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

</div>

</body>
</html>
