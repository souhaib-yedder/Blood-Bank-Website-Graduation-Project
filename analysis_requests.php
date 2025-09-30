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
$donorObj = new Donor($conn);

$completed_requests = $donorObj->getBloodBankRequestsCompletedAllTypes();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طلبات تحليل الدم المكتملة</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: "Cairo", sans-serif;
        }
        #searchInput {
            max-width: 400px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center text-danger mb-4">طلبات تحليل الدم المكتملة</h2>

    <!-- ✅ حقل البحث -->
    <div class="mb-3 text-center">
        <input type="text" id="searchInput" class="form-control mx-auto" placeholder="🔍 ابحث باسم المتبرع أو فصيلة الدم أو نوع الطلب...">
    </div>

    <table class="table table-bordered table-striped mt-4" id="requestsTable">
        <thead class="table-danger text-center">
            <tr>
                <th>اسم المتبرع</th>
                <th>فصيلة الدم</th>
                <th>نوع الدم</th>
                <th>مقدم الطلب </th>
                <th>تاريخ الطلب</th>
                <th>إجراء</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php if (count($completed_requests) > 0): ?>
                <?php foreach ($completed_requests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['donor_name']) ?></td>
                        <td><?= htmlspecialchars($request['blood_type']) ?></td>
                        <td><?= htmlspecialchars($request['blood_component']) ?></td>
                        <td><?= htmlspecialchars($request['request_type']) ?></td>
                        <td><?= htmlspecialchars($request['request_date']) ?></td>
                        <td>
                            <a href="test_blood.php?request_id=<?= urlencode($request['request_id']) ?>" class="btn btn-sm btn-primary">عمل تحليل</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">🚫 لا توجد طلبات تحليل مكتملة حالياً.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ✅ JavaScript للبحث -->
<script>
    document.getElementById("searchInput").addEventListener("keyup", function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#requestsTable tbody tr");

        rows.forEach(function (row) {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
</script>

</body>
</html>
