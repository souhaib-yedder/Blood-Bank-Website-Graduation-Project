<?php
session_start();
require_once 'db.php';
require_once 'class_BloodTest.php';
require_once 'donor_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}



$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$donorRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donorRow) {
    die("❌ لم يتم العثور على بيانات المتبرع.");
}

$donors_id = $donorRow['donors_id'];
$bloodTest = new BloodTest($conn);

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $bloodTest->deleteBloodTest($delete_id);


   
   echo "<script>window.location.href='analysis_results.php';</script>";

    exit;
}

$sql = "SELECT * FROM blood_tests WHERE donors_id = ? ORDER BY test_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$donors_id]);
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>نتائج تحاليل الدم</title>
    <style>
        #welcome-message {
            display: none !important;
        }
        #searchInput {
            max-width: 300px;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function filterTable() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#bloodTestsTable tbody tr");

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(input) ? "" : "none";
            });
        }
    </script>
</head>
<body>

<div class="container my-5">
    <h2 class="text-center mb-4 text-primary fw-bold">نتائج تحاليل الدم الخاصة بك</h2>

    <?php if (count($tests) === 0): ?>
        <div class="alert alert-warning text-center fs-5">لا توجد تحاليل حتى الآن.</div>
    <?php else: ?>
        <!-- 🔍 حقل البحث -->
        <input type="text" id="searchInput" onkeyup="filterTable()" class="form-control mx-auto" placeholder="ابحث في النتائج...">

        <div class="table-responsive shadow rounded mt-3">
           <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
                    <tr>
                        <th>تاريخ التحليل</th>
                        <th>عامل RH</th>
                        <th>نتيجة التوافق</th>
                        <th>HIV</th>
                        <th>HBV</th>
                        <th>HCV</th>
                        <th>Syphilis</th>
                        <th>HTLV</th>
                        <th>فحص الأجسام المضادة</th>
                        <th>كريات الدم الحمراء</th>
                        <th>كريات الدم البيضاء</th>
                        <th>الصفائح الدموية</th>
                        <th>الهيموغلوبين</th>
                        <th>حالة الدم</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): ?>
                    <tr>
                        <td><?= htmlspecialchars($test['test_date']) ?></td>
                        <td><?= htmlspecialchars($test['rh_factor']) ?></td>
                        <td><?= htmlspecialchars($test['crossmatch_result']) ?></td>
                        <td><?= htmlspecialchars($test['hiv']) ?></td>
                        <td><?= htmlspecialchars($test['hbv']) ?></td>
                        <td><?= htmlspecialchars($test['hcv']) ?></td>
                        <td><?= htmlspecialchars($test['syphilis']) ?></td>
                        <td><?= htmlspecialchars($test['htlv']) ?></td>
                        <td><?= htmlspecialchars($test['antibody_screening']) ?></td>
                        <td><?= htmlspecialchars($test['rbc_count']) ?></td>
                        <td><?= htmlspecialchars($test['wbc_count']) ?></td>
                        <td><?= htmlspecialchars($test['platelet_count']) ?></td>
                        <td><?= htmlspecialchars($test['hemoglobin_level']) ?></td>
                        <td><?= htmlspecialchars($test['blood_condition']) ?></td>
                        <td>
                            <a href="analysis_results.php?delete_id=<?= $test['blood_tests_id'] ?>" class="btn btn-sm btn-danger mb-1 px-3" onclick="return confirm('هل أنت متأكد من حذف هذا التحليل؟');">حذف</a>
                            <a href="view_blood_test.php?id=<?= $test['blood_tests_id'] ?>" class="btn btn-sm btn-info mb-1 px-3">عرض</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
