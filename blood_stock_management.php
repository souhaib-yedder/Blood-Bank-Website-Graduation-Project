<?php
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_BloodStock.php';

$db = new Database();
$conn = $db->connect();
$bloodStockObj = new BloodStock($conn);

$success = '';
$error = '';

// معالجة إضافة وحدة دم جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $blood_type = $_POST['blood_type'];
    $quantity = $_POST['quantity'];
    $receipt_date = $_POST['receipt_date'];
    $expiration_date = $_POST['expiration_date'];
    $blood_condition = $_POST['blood_condition'];
    $source = $_POST['source'];
    $notes = $_POST['notes'];
    $blood_component = $_POST['blood_component'];

    $result = $bloodStockObj->addBloodUnit($blood_type, $quantity, $receipt_date, $expiration_date, $blood_condition, $source, $notes, $blood_component);

    if ($result === true) {
        $success = "✅ تم إضافة وحدة الدم بنجاح.";
    } else {
        $error = $result;
    }
}

// حذف وحدة دم
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($bloodStockObj->deleteBloodUnit($id)) {
    

          echo "<script>window.location.href='blood_stock_management.php';</script>";

    exit;
    } else {
        $error = "❌ فشل الحذف.";
    }
}

$allUnits = $bloodStockObj->getAllBloodUnits();
?>

<div class="container-fluid px-4 py-4">
    <h2 class="text-center text-danger mb-4">إدارة مخزون الدم</h2>

    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <!-- إضافة وحدة دم جديدة -->
    <form method="POST" class="row g-3 bg-white p-4 shadow rounded">
        <h5>إضافة وحدة دم جديدة</h5>
        <div class="col-md-6">
            <label class="form-label">نوع الدم:</label>
            <select name="blood_type" class="form-control" required>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">نوع الدم (مكون الدم):</label>
            <select name="blood_component" class="form-control" required>
                <option value="Red Blood Cells">خلايا دم حمراء</option>
                <option value="Platelets">صفائح دموية</option>
                <option value="Plasma">بلازما</option>
                <option value="Whole Blood">دم كامل</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">تاريخ الاستلام:</label>
            <input type="date" name="receipt_date" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">تاريخ انتهاء الصلاحية:</label>
            <input type="date" name="expiration_date" id="expiration_date" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">حالة الدم:</label>
            <select name="blood_condition" class="form-control" required>
                <option value="Valid">سليم</option>
                <option value="Invalid">ملوث</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">المصدر:</label>
            <select name="source" class="form-control" required>
                <option value="Local">محلي</option>
                <option value="External">خارجي</option>
            </select>
        </div>

        <div class="col-md-12">
            <label class="form-label">الملاحظات:</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>
<div class="col-md-6">
    <label class="form-label">الكمية:</label>
    <input type="number" name="quantity" class="form-control" min="1" required>
</div>


        <div class="col-md-12 text-center">
            <button type="submit" name="add" class="btn btn-success w-50">إضافة</button>
        </div>
    </form>

    <!-- 🔍 حقل البحث -->
    <div class="mt-5 mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="ابحث بفصيلة الدم أو المكون...">
    </div>

    <!-- 🩸 جدول المخزون -->
    <h5 class="mt-3">المخزون الحالي</h5>
    <table class="table table-bordered table-striped" id="bloodTable">
        <thead class="table-danger">
            <tr>
                <th>فصيلة الدم</th>
                <th>مكون الدم</th>
                <th>الكمية</th>
                <th>تاريخ الاستلام</th>
                <th>تاريخ الانتهاء</th>
                <th>المصدر</th>
                <th>حالة الدم</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allUnits as $unit): ?>
                <tr>
                    <td><?= htmlspecialchars($unit['blood_type']) ?></td>
                    <td><?= htmlspecialchars($unit['blood_component']) ?></td>
                    <td><?= htmlspecialchars($unit['quantity']) ?></td>
                    <td><?= htmlspecialchars($unit['receipt_date']) ?></td>
                    <td><?= htmlspecialchars($unit['expiration_date']) ?></td>
                    <td><?= htmlspecialchars($unit['source']) ?></td>
                    <td><?= htmlspecialchars($unit['blood_condition']) ?></td>
                    <td>
                        <a href="edit_blood_stock.php?id=<?= $unit['blood_stock_id'] ?>" class="btn btn-warning btn-sm">تعديل</a>
                        <a href="?delete=<?= $unit['blood_stock_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من الحذف؟')">حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const bloodComponentSelect = document.querySelector('select[name="blood_component"]');
    const expirationDateInput = document.querySelector('#expiration_date');

    bloodComponentSelect.addEventListener('change', function () {
        let expirationDate = new Date();
        switch (this.value) {
            case 'Red Blood Cells': expirationDate.setDate(expirationDate.getDate() + 42); break;
            case 'Platelets': expirationDate.setDate(expirationDate.getDate() + 5); break;
            case 'Plasma': expirationDate.setFullYear(expirationDate.getFullYear() + 1); break;
            case 'Whole Blood': expirationDate.setDate(expirationDate.getDate() + 35); break;
        }
        expirationDateInput.value = expirationDate.toISOString().split('T')[0];
    });

    // ✅ فلترة جدول البحث
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll("#bloodTable tbody tr");

    searchInput.addEventListener("keyup", function () {
        const value = this.value.toLowerCase();
        tableRows.forEach(row => {
            const rowText = row.innerText.toLowerCase();
            row.style.display = rowText.includes(value) ? "" : "none";
        });
    });
});
</script>
