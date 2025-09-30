<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_BloodTest.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$bloodTest = new BloodTest($conn);

// جلب id التحليل
if (!isset($_GET['id'])) {
    echo "<script>window.location.href='manage_tests.php';</script>";
    exit;
}

$blood_tests_id = intval($_GET['id']); 

// عند إرسال النموذج (تحديث البيانات)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_date = $_POST['test_date'];
    $rh_factor = $_POST['rh_factor'];
    $crossmatch_result = $_POST['crossmatch_result'];
    $hiv = $_POST['hiv'];
    $hbv = $_POST['hbv'];
    $hcv = $_POST['hcv'];
    $syphilis = $_POST['syphilis'];
    $htlv = $_POST['htlv'];
    $antibody_screening = $_POST['antibody_screening'];
    $rbc_count = $_POST['rbc_count'];
    $wbc_count = $_POST['wbc_count'];
    $platelet_count = $_POST['platelet_count'];
    $hemoglobin_level = $_POST['hemoglobin_level'];
    $blood_condition = $_POST['blood_condition'];

    $bloodTest->updateBloodTestByBloodTestsId(
        $blood_tests_id,
        $test_date,
        $rh_factor,
        $crossmatch_result,
        $hiv,
        $hbv,
        $hcv,
        $syphilis,
        $htlv,
        $antibody_screening,
        $rbc_count,
        $wbc_count,
        $platelet_count,
        $hemoglobin_level,
        $blood_condition
    );

    echo "<script>window.location.href='manage_tests.php';</script>";
    exit;
}

// جلب بيانات التحليل لعرضها في النموذج
$test = $bloodTest->getBloodTestById($blood_tests_id);

if (!$test) {
    echo "التحليل غير موجود";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل تحليل الدم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">تعديل تحليل الدم</h2>

    <form method="POST" action="">
        <div class="mb-3">
            <label>تاريخ التحليل</label>
            <input type="date" name="test_date" class="form-control" value="<?= htmlspecialchars($test['test_date']) ?>" required>
        </div>

        <div class="mb-3">
            <label>RH Factor</label>
            <select name="rh_factor" class="form-control">
                <option value="Positive" <?= ($test['rh_factor'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['rh_factor'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Crossmatch Result</label>
            <select name="crossmatch_result" class="form-control">
                <option value="Compatible" <?= ($test['crossmatch_result'] === 'Compatible') ? 'selected' : '' ?>>Compatible</option>
                <option value="Incompatible" <?= ($test['crossmatch_result'] === 'Incompatible') ? 'selected' : '' ?>>Incompatible</option>
            </select>
        </div>

        <div class="mb-3">
            <label>HIV</label>
            <select name="hiv" class="form-control">
                <option value="Positive" <?= ($test['hiv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['hiv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>HBV</label>
            <select name="hbv" class="form-control">
                <option value="Positive" <?= ($test['hbv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['hbv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>HCV</label>
            <select name="hcv" class="form-control">
                <option value="Positive" <?= ($test['hcv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['hcv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Syphilis</label>
            <select name="syphilis" class="form-control">
                <option value="Positive" <?= ($test['syphilis'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['syphilis'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>HTLV</label>
            <select name="htlv" class="form-control">
                <option value="Positive" <?= ($test['htlv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['htlv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Antibody Screening</label>
            <select name="antibody_screening" class="form-control">
                <option value="Positive" <?= ($test['antibody_screening'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
                <option value="Negative" <?= ($test['antibody_screening'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
            </select>
        </div>

        <div class="mb-3">
            <label>RBC Count</label>
            <input type="number" step="any" name="rbc_count" class="form-control" value="<?= htmlspecialchars($test['rbc_count']) ?>">
        </div>

        <div class="mb-3">
            <label>WBC Count</label>
            <input type="number" step="any" name="wbc_count" class="form-control" value="<?= htmlspecialchars($test['wbc_count']) ?>">
        </div>

        <div class="mb-3">
            <label>Platelet Count</label>
            <input type="number" step="any" name="platelet_count" class="form-control" value="<?= htmlspecialchars($test['platelet_count']) ?>">
        </div>

        <div class="mb-3">
            <label>Hemoglobin Level</label>
            <input type="number" step="any" name="hemoglobin_level" class="form-control" value="<?= htmlspecialchars($test['hemoglobin_level']) ?>">
        </div>

        <div class="mb-3">
            <label>Blood Condition</label>
            <select name="blood_condition" class="form-control">
                <option value="Clean" <?= ($test['blood_condition'] === 'Clean') ? 'selected' : '' ?>>Clean</option>
                <option value="Contaminated" <?= ($test['blood_condition'] === 'Contaminated') ? 'selected' : '' ?>>Contaminated</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">تحديث</button>
        <a href="manage_tests.php" class="btn btn-secondary">إلغاء</a>
    </form>
</div>
</body>
</html>
