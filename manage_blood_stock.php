<?php
session_start();
require_once 'db.php';
require_once 'class_BloodStock.php';

// التحقق من الصلاحية وتحديد التخطيط المناسب
if (!isset($_SESSION['role'])) {
    header("Location: unauthorized.php");
    exit();
} elseif ($_SESSION['role'] === 'admin') {
    require_once 'admin_layout.php';
} elseif ($_SESSION['role'] === 'hospital') {
    require_once 'hospital_layout.php';
} else {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$stock = new BloodStock($conn);

// جلب بيانات المخزون
$rows = $stock->getBloodStocks();

// تجهيز مصفوفة فارغة لجميع أنواع الدم والمكونات
$bloodTypes = ['A+', 'A-', 'O+', 'O-', 'B+', 'B-', 'AB+', 'AB-'];
$components = ['Plasma', 'Platelets', 'Whole Blood', 'Red Blood Cells'];

// مصفوفة لتجميع الكميات
$data = [];
foreach ($bloodTypes as $type) {
    foreach ($components as $comp) {
        $data[$type][$comp] = 0;
    }
}

// ملء البيانات من قاعدة البيانات
foreach ($rows as $row) {
    $type = $row['blood_type'];
    $component = $row['blood_component'];
    $quantity = $row['quantity'];

    if (isset($data[$type][$component])) {
        $data[$type][$component] = $quantity;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة مخزون الدم</title>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">مخزون الدم المتاح</h2>

          <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
            <tr>
                <th>فصيلة الدم</th>
                <?php foreach ($components as $comp): ?>
                    <th><?= htmlspecialchars($comp) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bloodTypes as $type): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($type) ?></strong></td>
                    <?php foreach ($components as $comp): ?>
                        <td><?= intval($data[$type][$comp]) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
