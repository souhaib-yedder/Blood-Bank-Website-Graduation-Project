<?php
session_start();
require_once 'db.php';
require_once 'class_Patient.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$conn = (new Database())->connect();
$patient = new Patient($conn);
$patients = $patient->getAllWithHospital();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة المرضى - موظف</title>
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <style>
        body {
            font-family: "Cairo", sans-serif;
            background-color: #f8f9fa;
        }
        #searchInput {
            max-width: 400px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4 text-center text-danger">📋 قائمة جميع المرضى</h3>

    <!-- ✅ حقل البحث -->
    <div class="mb-3 text-center">
        <input type="text" id="searchInput" class="form-control mx-auto" placeholder="🔍 ابحث عن اسم مريض، فصيلة، مستشفى، أو حالة...">
    </div>

    <table class="table table-bordered table-striped" id="patientsTable">
        <thead class="table-danger">
            <tr>
                <th>اسم المريض</th>
                <th>فصيلة الدم</th>
                <th>مستوى الاستعجال</th>
                <th>ملف الحالة</th>
                <th>عدد الوحدات</th>
                <th>اسم المستشفى</th>
                <th>تاريخ التسجيل</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($patients)): ?>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['patient_name']) ?></td>
                        <td><?= htmlspecialchars($p['blood_type']) ?></td>
                        <td><?= htmlspecialchars($p['urgency_level']) ?></td>
                        <td>
                            <?php if (!empty($p['condition_description'])): ?>
                                <button type="button" class="btn btn-info btn-sm"
                                        onclick="window.open('uploads/<?= htmlspecialchars($p['condition_description']) ?>','_blank')">
                                    📄 عرض الوصفة
                                </button>
                            <?php else: ?>
                                لا يوجد ملف
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['needed_units']) ?></td>
                        <td><?= htmlspecialchars($p['hospital_name']) ?></td>
                        <td><?= htmlspecialchars($p['registered_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">🚫 لا توجد بيانات لعرضها</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ✅ JavaScript للبحث -->
<script>
    document.getElementById("searchInput").addEventListener("keyup", function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#patientsTable tbody tr");

        rows.forEach(function (row) {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
</script>

</body>
</html>
