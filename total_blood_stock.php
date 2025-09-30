<?php
require_once 'db.php';
require_once 'class_BloodStock.php';
require_once  'staff_layout.php';


$db = new Database();
$conn = $db->connect();
$bloodStock = new BloodStock($conn);

// حدث وحدات الدم المنتهية الصلاحية أولاً
$bloodStock->invalidateExpiredBloodUnits();

// التعريف بفصائل الدم والمكونات
$bloodTypes = ['A+', 'A-', 'O+', 'O-', 'B+', 'B-', 'AB+', 'AB-'];
$bloodComponents = ['Whole Blood', 'Plasma', 'Red Blood Cells', 'Platelets'];

// دالة لجلب المخزون حسب الحالة
function getBloodStockSummary($conn, $condition) {
    $stmt = $conn->prepare("SELECT blood_type, blood_component, SUM(quantity) AS total_quantity
                            FROM blood_stock
                            WHERE blood_condition = ?
                            GROUP BY blood_type, blood_component");
    $stmt->execute([$condition]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [];
    foreach ($results as $row) {
        $summary[$row['blood_type']][$row['blood_component']] = $row['total_quantity'];
    }
    return $summary;
}

// جلب بيانات الدم السليم والمنتهي
$validStock = getBloodStockSummary($conn, 'Valid');
$invalidStock = getBloodStockSummary($conn, 'Invalid');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مخزون الدم الكلي</title>
</head>


<div class="container">

    <!-- ✅ جدول الدم السليم -->
    <h2 class="text-success mb-4">🟢 جدول مخزون الدم السليم (Valid)</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center">
            <thead class="table-success">
                <tr>
                    <th>فصيلة الدم</th>
                    <?php foreach ($bloodComponents as $component): ?>
                        <th><?= $component ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bloodTypes as $type): ?>
                    <tr>
                        <td><strong><?= $type ?></strong></td>
                        <?php foreach ($bloodComponents as $component): ?>
                            <td><?= $validStock[$type][$component] ?? 0 ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ⚠️ جدول الدم المنتهي -->
    <h2 class="text-danger mt-5 mb-4">🔴 جدول مخزون الدم المنتهي الصلاحية (Invalid)</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center">
            <thead class="table-danger">
                <tr>
                    <th>فصيلة الدم</th>
                    <?php foreach ($bloodComponents as $component): ?>
                        <th><?= $component ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bloodTypes as $type): ?>
                    <tr>
                        <td><strong><?= $type ?></strong></td>
                        <?php foreach ($bloodComponents as $component): ?>
                            <td><?= $invalidStock[$type][$component] ?? 0 ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
