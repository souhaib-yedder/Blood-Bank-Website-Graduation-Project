<?php
session_start();

require_once 'db.php';
require_once 'class_Donor.php';
require_once 'class_BloodTest.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$conn = (new Database())->connect();
$donorObj = new Donor($conn);
$bloodTest = new BloodTest($conn);

$request_id = $_GET['request_id'] ?? null;
if (!$request_id) {
    echo "<h3>❌ لا يوجد طلب محدد</h3>";
    exit();
}

$request = $donorObj->getRequestWithDonorById($request_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['donors_id'] = $request['donors_id'];
    $data['test_date'] = date('Y-m-d');

    $infections = [
        $data['hiv'], $data['hbv'], $data['hcv'],
        $data['syphilis'], $data['antibody_screening']
    ];
    $data['blood_condition'] = (in_array('Positive', $infections)) ? 'Contaminated' : 'Clean';

    // إدخال التحليل
    $bloodTest->insertBloodTest($data);

    // تنفيذ تحديث المخزون
    $result = $bloodTest->updateBloodStockBasedOnRequest($request_id);

    if ($result === 'insufficient') {
        echo "<div class='alert alert-danger text-center'>⚠️ المخزون غير كافٍ، لا يمكن أخذ الدم من بنك الدم.</div>";
    } else {
        echo "<div class='alert alert-success text-center'>✔️ تم إدخال التحليل وتحديث المخزون.</div>";
    }
}
?>

<div class="container mt-5">
    <h3 class="text-center mb-4">تحليل دم للمتبرع: <?= htmlspecialchars($request['donor_name']) ?></h3>

    <form method="POST" class="border p-4 rounded bg-light">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label>RH Factor</label>
                <select name="rh_factor" class="form-control" required>
                    <option value="Positive">Positive</option>
                    <option value="Negative">Negative</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Crossmatch Result</label>
                <select name="crossmatch_result" class="form-control" required>
                    <option value="Compatible">Compatible</option>
                    <option value="Incompatible">Incompatible</option>
                </select>
            </div>
        </div>

        <div class="row">
            <?php foreach (['hiv', 'hbv', 'hcv', 'syphilis', 'htlv', 'antibody_screening'] as $disease): ?>
                <div class="col-md-4 mb-3">
                    <label><?= strtoupper($disease) ?></label>
                    <select name="<?= $disease ?>" class="form-control" required>
                        <option value="Negative">Negative</option>
                        <option value="Positive">Positive</option>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row">
            <div class="col-md-3 mb-3">
                <label>RBC Count</label>
                <input type="number" step="any" name="rbc_count" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>WBC Count</label>
                <input type="number" step="any" name="wbc_count" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Platelet Count</label>
                <input type="number" step="any" name="platelet_count" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Hemoglobin Level</label>
                <input type="number" step="any" name="hemoglobin_level" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-success">🔬 إنشاء تحليل</button>
    </form>
</div>
