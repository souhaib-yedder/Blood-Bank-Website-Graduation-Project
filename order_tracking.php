<?php
session_start();
require_once 'hospital_layout.php';
require_once 'db.php';
require_once 'class_Hospital.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$hospital = new Hospital($conn);

$user_id = $_SESSION['user_id'];
$hospitalData = $hospital->getHospitalByUserId($user_id);
$hospitals_id = $hospitalData['hospitals_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request_id'])) {
    $delete_id = intval($_POST['delete_request_id']);
    $hospital->deleteBloodRequest($delete_id);
    header("Location: order_tracking.php");
    exit();
}

$requests = $hospital->getAllBloodRequestsByHospital($hospitals_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>متابعة طلبات الدم</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css"> <!-- تأكد من مسار Bootstrap -->
    <style>
        body {
            font-family: "Cairo", sans-serif;
            background-color: #f8f9fa;
        }
        input#searchInput {
            max-width: 350px;
            margin: 20px auto;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4 text-center text-danger">متابعة طلبات الدم للمستشفى</h2>

    <!-- ✅ مربع البحث -->
    <?php if (count($requests) > 0): ?>
        <input type="text" id="searchInput" class="form-control text-center" placeholder="🔍 ابحث في الطلبات...">

            <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
                <tr>
                    <th>فصيلة الدم</th>
                    <th>مكون الدم</th>
                    <th>الكمية المطلوبة</th>
                    <th>تاريخ الطلب</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['blood_type']) ?></td>
                    <td><?= htmlspecialchars($req['blood_component']) ?></td>
                    <td><?= htmlspecialchars($req['units_needed']) ?></td>
                    <td><?= date('Y-m-d', strtotime($req['request_date'])) ?></td>
                    <td><?= htmlspecialchars($req['status']) ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب؟');" style="display:inline;">
                            <input type="hidden" name="delete_request_id" value="<?= $req['blood_requests_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">لا توجد طلبات دم حالياً.</p>
    <?php endif; ?>
</div>

<!-- ✅ سكربت البحث -->
<script>
document.getElementById("searchInput").addEventListener("keyup", function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#requestsTable tbody tr");

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
