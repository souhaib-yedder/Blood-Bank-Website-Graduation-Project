<?php
session_start();
require_once 'db.php';
require_once 'class_BloodTest.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$bloodTest = new BloodTest($conn);
$tests = $bloodTest->getAllTestsWithDonorInfo();

// معالجة الحذف بعد عرض الجدول
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $bloodTest->deleteBloodTest($delete_id);

       echo "<script>window.location.href='blood_tests_list.php';</script>";

    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة تحاليل الدم</title>
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
    <h2 class="text-center text-danger mb-4">🧪 قائمة تحاليل الدم</h2>

    <!-- ✅ حقل البحث -->
    <div class="mb-3 text-center">
        <input type="text" id="searchInput" class="form-control mx-auto" placeholder="🔍 ابحث باسم المتبرع أو RH أو فصيلة الدم...">
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="bloodTestsTable">
            <thead class="table-danger text-center">
                <tr>
                    <th>اسم المتبرع</th>
                    <th>فصيلة الدم</th>
                    <th>تاريخ التحليل</th>
                    <th>RH</th>
                    <th>نتيجة المطابقة</th>
                    <th>HIV</th>
                    <th>HBV</th>
                    <th>HCV</th>
                    <th>Syphilis</th>
                    <th>HTLV</th>
                    <th>RBC</th>
                    <th>WBC</th>
                    <th>Platelets</th>
                    <th>Hemoglobin</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?= htmlspecialchars($test['donor_name']) ?></td>
                        <td><?= htmlspecialchars($test['blood_type']) ?></td>
                        <td><?= htmlspecialchars($test['test_date']) ?></td>
                        <td><?= htmlspecialchars($test['rh_factor']) ?></td>
                        <td><?= htmlspecialchars($test['crossmatch_result']) ?></td>
                        <td><?= htmlspecialchars($test['hiv']) ?></td>
                        <td><?= htmlspecialchars($test['hbv']) ?></td>
                        <td><?= htmlspecialchars($test['hcv']) ?></td>
                        <td><?= htmlspecialchars($test['syphilis']) ?></td>
                        <td><?= htmlspecialchars($test['htlv']) ?></td>
                        <td><?= htmlspecialchars($test['rbc_count']) ?></td>
                        <td><?= htmlspecialchars($test['wbc_count']) ?></td>
                        <td><?= htmlspecialchars($test['platelet_count']) ?></td>
                        <td><?= htmlspecialchars($test['hemoglobin_level']) ?></td>
                        <td><?= htmlspecialchars($test['blood_condition']) ?></td>
                        <td>
                            <a href="edit_test.php?blood_tests_id=<?= $test['blood_tests_id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                            <a href="blood_tests_list.php?delete_id=<?= $test['blood_tests_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟');">حذف</a>
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
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#bloodTestsTable tbody tr");

        rows.forEach(function (row) {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
</script>

</body>
</html>
