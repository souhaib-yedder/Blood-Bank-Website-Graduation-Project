<?php
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_BloodStock.php';

$db = new Database();
$conn = $db->connect();
$bloodStock = new BloodStock($conn);

if (!isset($_GET['id'])) {
    die("❌ لم يتم تحديد وحدة الدم.");
}

$id = intval($_GET['id']);
$unit = $bloodStock->getBloodUnitById($id);
if (!$unit) {
    die("❌ وحدة الدم غير موجودة.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_type = $_POST['blood_type'];
    $blood_component = $_POST['blood_component'];
    $quantity = $_POST['quantity'];
    $receipt_date = $_POST['receipt_date'];
    $expiration_date = $_POST['expiration_date'];
    $blood_condition = $_POST['blood_condition'];
    $source = $_POST['source'];
    $notes = $_POST['notes'];

    if ($bloodStock->updateBloodUnit($id, $blood_type, $blood_component, $quantity, $receipt_date, $expiration_date, $blood_condition, $source, $notes)) {
        $success = "✅ تم التحديث بنجاح.";
        $unit = $bloodStock->getBloodUnitById($id); // تحديث البيانات المعروضة
    } else {
        $error = "❌ فشل التحديث.";
    }
}
?>

<div class="container mt-4">
    <h4 class="text-center text-primary mb-4">تعديل وحدة دم</h4>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3 bg-white p-4 shadow rounded">
   <div class="col-md-6">
    <label class="form-label">فصيلة الدم:</label>
    <select name="blood_type" class="form-control" required>
        <option value="">اختر الفصيلة</option>
        <option value="A+" <?= ($unit['blood_type'] == 'A+') ? 'selected' : '' ?>>A+</option>
        <option value="A-" <?= ($unit['blood_type'] == 'A-') ? 'selected' : '' ?>>A-</option>
        <option value="B+" <?= ($unit['blood_type'] == 'B+') ? 'selected' : '' ?>>B+</option>
        <option value="B-" <?= ($unit['blood_type'] == 'B-') ? 'selected' : '' ?>>B-</option>
        <option value="O+" <?= ($unit['blood_type'] == 'O+') ? 'selected' : '' ?>>O+</option>
        <option value="O-" <?= ($unit['blood_type'] == 'O-') ? 'selected' : '' ?>>O-</option>
        <option value="AB+" <?= ($unit['blood_type'] == 'AB+') ? 'selected' : '' ?>>AB+</option>
        <option value="AB-" <?= ($unit['blood_type'] == 'AB-') ? 'selected' : '' ?>>AB-</option>
    </select>
</div>


 <div class="col-md-6">
    <label class="form-label">مكون الدم:</label>
    <select name="blood_component" class="form-control" required>
        <option value="">Select Component</option>
        <option value="Red Blood Cells" <?= ($unit['blood_component'] == 'Red Blood Cells') ? 'selected' : '' ?>>Red Blood Cells</option>
        <option value="Plasma" <?= ($unit['blood_component'] == 'Plasma') ? 'selected' : '' ?>>Plasma</option>
        <option value="Whole Blood" <?= ($unit['blood_component'] == 'Whole Blood') ? 'selected' : '' ?>>Whole Blood</option>
        <option value="Platelets" <?= ($unit['blood_component'] == 'Platelets') ? 'selected' : '' ?>>Platelets</option>
    </select>
</div>

<div class="col-md-6">
    <label class="form-label">الكمية:</label>
    <input type="number" name="quantity" class="form-control" 
           value="<?= $unit['quantity'] ?>" 
           min="1" required>
</div>


        <div class="col-md-6">
            <label class="form-label">تاريخ الاستلام:</label>
            <input type="date" name="receipt_date" class="form-control" value="<?= $unit['receipt_date'] ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">تاريخ الانتهاء:</label>
            <input type="date" name="expiration_date" class="form-control" value="<?= $unit['expiration_date'] ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">الحالة:</label>
            <select name="blood_condition" class="form-control" required>
                <option value="Valid" <?= $unit['blood_condition'] == 'Valid' ? 'selected' : '' ?>>سليم</option>
                <option value="Invalid" <?= $unit['blood_condition'] == 'Invalid' ? 'selected' : '' ?>>ملوث</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">المصدر:</label>
            <select name="source" class="form-control" required>
                <option value="Local" <?= $unit['source'] == 'Local' ? 'selected' : '' ?>>محلي</option>
                <option value="External" <?= $unit['source'] == 'External' ? 'selected' : '' ?>>خارجي</option>
            </select>
        </div>

        <div class="col-md-12">
            <label class="form-label">ملاحظات:</label>
            <textarea name="notes" class="form-control"><?= htmlspecialchars($unit['notes']) ?></textarea>
        </div>

        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary">تحديث</button>
            <a href="blood_stock_management.php" class="btn btn-secondary">العودة</a>
        </div>
    </form>
</div>
