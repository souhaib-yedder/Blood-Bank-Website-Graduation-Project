<?php
session_start();
require_once 'db.php';
require_once 'class_BloodTest.php';
require_once 'class_Donor.php';
require_once 'class_Users.php';
require_once 'admin_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$bloodTest = new BloodTest($conn);
$donorObj = new Donor($conn);
$userObj = new Users($conn);

if (isset($_GET['delete_id'])) {
    $bloodTest->deleteBloodTest($_GET['delete_id']);
    echo "<script>window.location.href='manage_tests.php';</script>";
    exit;
}

$tests = $bloodTest->getAllBloodTests();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة تحاليل الدم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #searchInput {
            max-width: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">إدارة تحاليل الدم</h2>

    <!-- حقل البحث -->
    <input type="text" id="searchInput" class="form-control mx-auto" placeholder="ابحث باسم المتبرع أو النتيجة أو التاريخ..." onkeyup="filterTable()">

    <table class="table table-bordered table-striped" id="requestsTable">
        <thead class="table-danger">
            <tr>
                <th>اسم المريض</th>
                <th>تاريخ التحليل</th>
                <th>RH</th>
                <th>Crossmatch</th>
                <th>HIV</th>
                <th>HBV</th>
                <th>HCV</th>
                <th>Syphilis</th>
                <th>HTLV</th>
                <th>Antibody</th>
                <th>RBC</th>
                <th>WBC</th>
                <th>Platelet</th>
                <th>Hemoglobin</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php foreach ($tests as $test): ?>
                <?php
                    $donor = $donorObj->getDonorById($test['donors_id']);
                    $user = $userObj->getUserById($donor['user_id']);
                    $donor_name = $user ? $user['name'] : 'غير معروف';
                ?>
                <tr>
                    <td><?= htmlspecialchars($donor_name) ?></td>
                    <td><?= $test['test_date'] ?></td>
                    <td><?= $test['rh_factor'] ?></td>
                    <td><?= $test['crossmatch_result'] ?></td>
                    <td><?= $test['hiv'] ?></td>
                    <td><?= $test['hbv'] ?></td>
                    <td><?= $test['hcv'] ?></td>
                    <td><?= $test['syphilis'] ?></td>
                    <td><?= $test['htlv'] ?></td>
                    <td><?= $test['antibody_screening'] ?></td>
                    <td><?= $test['rbc_count'] ?></td>
                    <td><?= $test['wbc_count'] ?></td>
                    <td><?= $test['platelet_count'] ?></td>
                    <td><?= $test['hemoglobin_level'] ?></td>
                    <td><?= $test['blood_condition'] ?></td>
                    <td>
                        <a href="edit_blood_test.php?id=<?= htmlspecialchars($test['blood_tests_id']) ?>" class="btn btn-sm btn-primary">تعديل</a>
                        <a href="manage_tests.php?delete_id=<?= htmlspecialchars($test['blood_tests_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- سكربت البحث -->
<script>
function filterTable() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#requestsTable tbody tr"); // تصحيح الـ id

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
    });
}
</script>
</body>
</html>
