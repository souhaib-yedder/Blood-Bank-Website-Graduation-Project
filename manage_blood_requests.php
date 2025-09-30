<?php
session_start();

require_once 'db.php';
require_once 'class_Hospital.php';
require_once 'class_BloodStock.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$hospitalObj = new Hospital($conn);

// جلب الطلبات
$hospitals_with_pending_requests = $hospitalObj->getPendingBloodRequestsHospitals();
$blood_requests = [];
foreach ($hospitals_with_pending_requests as $hospital) {
    $hospitals_id = $hospital['hospitals_id'];
    $blood_requests = array_merge($blood_requests, $hospitalObj->getBloodRequestsByHospital($hospitals_id));
}

// المعالجة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;

    if ($request_id) {
        if (isset($_POST['accept'])) {
            if ($hospitalObj->acceptBloodRequest($request_id)) {
                echo "<script>alert('✅ تم قبول الطلب بنجاح وتم تحديث المخزون.');</script>";
            }
        }

        if (isset($_POST['reject'])) {
            if ($hospitalObj->rejectBloodRequest($request_id)) {
                echo "<script>alert('✅ تم رفض الطلب.');</script>";
            } else {
                echo "<script>alert('❌ حدث خطأ أثناء الرفض.');</script>";
            }
        }
    } else {
        echo "<script>alert('❌ رقم الطلب غير موجود.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <!-- ✅ مهم لتجاوب الصفحة -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة طلبات الدم - موظف</title>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">إدارة طلبات الدم للمستشفى</h2>

    <!-- ✅ حقل البحث -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث عن مستشفى أو فصيلة دم أو حالة الطلب...">
    </div>

    <!-- ✅ تغليف الجدول لجعله قابل للتمرير -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-danger">
                <tr>
                    <th>رقم الطلب</th>
                    <th>اسم المستشفى</th>
                    <th>فصيلة الدم</th>
                    <th>نوع الدم</th>
                    <th>الكمية المطلوبة</th>
                    <th>تاريخ الطلب</th>
                    <th>حالة الطلب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blood_requests as $request): ?>
                    <tr>
                        <td><?= $request['blood_requests_id'] ?></td>
                        <td><?= $request['hospital_name'] ?></td>
                        <td><?= $request['blood_type'] ?></td>
                        <td><?= $request['blood_component'] ?></td>
                        <td><?= $request['units_needed'] ?></td>
                        <td><?= $request['request_date'] ?></td>
                        <td><?= $request['status'] ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $request['blood_requests_id'] ?>">
                                <button type="submit" name="accept" class="btn btn-success btn-sm">قبول</button>
                                <button type="submit" name="reject" class="btn btn-danger btn-sm">رفض</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ✅ سكربت البحث -->
<script>
    document.getElementById("searchInput").addEventListener("keyup", function () {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll("table tbody tr");

        rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
</script>

</body>
</html>
