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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $bloodTest->updateBloodTest($data);

    echo "<script>window.location.href='blood_tests_list.php';</script>";
    exit;
}

if (!isset($_GET['blood_tests_id'])) {
    echo "<script>window.location.href='blood_tests_list.php';</script>";
    exit;
}

$test = $bloodTest->getTestById($_GET['blood_tests_id']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل تحليل دم</title>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">تعديل تحليل دم</h2>

    <form method="POST">
        <input type="hidden" name="blood_tests_id" value="<?= htmlspecialchars($test['blood_tests_id']) ?>">

        <label>تاريخ التحليل:</label>
        <input type="date" name="test_date" value="<?= htmlspecialchars($test['test_date']) ?>" class="form-control" required><br>

        <label>RH Factor:</label>
        <select name="rh_factor" class="form-control">
            <option value="Positive" <?= ($test['rh_factor'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['rh_factor'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>نتيجة المطابقة (Crossmatch Result):</label>
        <select name="crossmatch_result" class="form-control">
            <option value="Compatible" <?= ($test['crossmatch_result'] === 'Compatible') ? 'selected' : '' ?>>Compatible</option>
            <option value="Incompatible" <?= ($test['crossmatch_result'] === 'Incompatible') ? 'selected' : '' ?>>Incompatible</option>
        </select><br>

        <label>HIV:</label>
        <select name="hiv" class="form-control">
            <option value="Positive" <?= ($test['hiv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['hiv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>HBV:</label>
        <select name="hbv" class="form-control">
            <option value="Positive" <?= ($test['hbv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['hbv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>HCV:</label>
        <select name="hcv" class="form-control">
            <option value="Positive" <?= ($test['hcv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['hcv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>Syphilis:</label>
        <select name="syphilis" class="form-control">
            <option value="Positive" <?= ($test['syphilis'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['syphilis'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>HTLV:</label>
        <select name="htlv" class="form-control">
            <option value="Positive" <?= ($test['htlv'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['htlv'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>Antibody Screening:</label>
        <select name="antibody_screening" class="form-control">
            <option value="Positive" <?= ($test['antibody_screening'] === 'Positive') ? 'selected' : '' ?>>Positive</option>
            <option value="Negative" <?= ($test['antibody_screening'] === 'Negative') ? 'selected' : '' ?>>Negative</option>
        </select><br>

        <label>RBC Count:</label>
        <input type="number" step="0.1" name="rbc_count" value="<?= htmlspecialchars($test['rbc_count']) ?>" class="form-control"><br>

        <label>WBC Count:</label>
        <input type="number" step="0.1" name="wbc_count" value="<?= htmlspecialchars($test['wbc_count']) ?>" class="form-control"><br>

        <label>Platelet Count:</label>
        <input type="number" step="0.1" name="platelet_count" value="<?= htmlspecialchars($test['platelet_count']) ?>" class="form-control"><br>

        <label>Hemoglobin Level:</label>
        <input type="number" step="0.1" name="hemoglobin_level" value="<?= htmlspecialchars($test['hemoglobin_level']) ?>" class="form-control"><br>

        <label>حالة الدم:</label>
        <select name="blood_condition" class="form-control">
            <option value="Clean" <?= ($test['blood_condition'] === 'Clean') ? 'selected' : '' ?>>Clean</option>
            <option value="Contaminated" <?= ($test['blood_condition'] === 'Contaminated') ? 'selected' : '' ?>>Contaminated</option>
        </select><br>

        <button type="submit" class="btn btn-success">حفظ التعديلات</button>
    </form>
</div>

</body>
</html>
