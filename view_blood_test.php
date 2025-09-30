<?php
session_start();
require_once 'db.php';
require_once 'class_BloodTest.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    die("🚫 لا تملك صلاحية الوصول.");
}
if (!isset($_GET['id'])) {
    die("❌ لم يتم تحديد التحليل.");
}

$db = new Database();
$conn = $db->connect();
$bloodTest = new BloodTest($conn);

$stmt = $conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$donorRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$donorRow) die("❌ لم يتم العثور على بيانات المتبرع.");
$donors_id = $donorRow['donors_id'];

$blood_tests_id = intval($_GET['id']);
$test = $bloodTest->getTestById($blood_tests_id);
if (!$test || $test['donors_id'] != $donors_id) die("🚫 لا تملك صلاحية عرض هذا التحليل.");

$stmt = $conn->prepare("SELECT u.name, d.birth_date FROM donors d JOIN users u ON d.user_id = u.user_id WHERE d.donors_id = ?");
$stmt->execute([$donors_id]);
$donorInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$birthDate = new DateTime($donorInfo['birth_date']);
$age = $birthDate->diff(new DateTime())->y;

function checkRange($value, $min, $max) {
    if ($value === null || $value === '') return ['-', 'gray'];
    if ($value > $max) return ['مرتفع', 'red'];
    if ($value < $min) return ['منخفض', 'red'];
    return ['طبيعي', 'green'];
}
function statusColor($value) {
    $v = strtolower(trim($value));
    if (in_array($v, ['positive', 'clean', 'compatible'])) return ['إيجابي', 'green'];
    if (in_array($v, ['negative', 'contaminated', 'incompatible'])) return ['سلبي', 'red'];
    return [$value, 'gray'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<title>تقرير تحليل الدم</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: #f0f4f8; color: #333; }
    .container { max-width: 1000px; margin: 30px auto; background: #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border-radius: 12px; padding: 30px; }
    h1,h2 { text-align: center; color: #004d7a; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th { background-color: #004d7a; color: white; padding: 10px; }
    td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
    .green { color: green; font-weight: bold; }
    .red { color: red; font-weight: bold; }
    .gray { color: gray; font-weight: bold; }
    .btn-group { margin-top: 20px; text-align: center; }
    button { background-color: #004d7a; border: none; color: white; padding: 12px 20px; margin: 5px; font-size: 16px; border-radius: 8px; cursor: pointer; transition: background-color 0.3s ease; }
    button:hover { background-color: #003455; }
</style>
</head>
<body>

<div class="container" id="report-content">

    <h1>مصرف الدم الليبي</h1>
    <h2>Blood Bank Libya</h2>

    <table>
        <tr>
            <td>الاسم: <?= htmlspecialchars($donorInfo['name']) ?></td>
            <td>العمر: <?= $age ?> سنة</td>
            <td>تاريخ التحليل: <?= $test['test_date'] ?></td>
        </tr>
    </table>

    <h2>اختبارات التوافق (Blood Compatibility)</h2>
    <table>
        <tr><th>الاختبار</th><th>النتيجة</th><th>الحالة</th></tr>
        <tr>
            <td>RH (Rh Factor)</td>
            <td><?= htmlspecialchars($test['rh_factor']) ?></td>
            <?php list($res, $col) = statusColor($test['rh_factor']); ?>
            <td class="<?= $col ?>"><?= $res ?></td>
        </tr>
        <tr>
            <td>Crossmatch Result</td>
            <td><?= htmlspecialchars($test['crossmatch_result']) ?></td>
            <?php list($res, $col) = statusColor($test['crossmatch_result']); ?>
            <td class="<?= $col ?>"><?= $res ?></td>
        </tr>
    </table>

    <h2>عداد الدم وجودته (Blood Count)</h2>
    <table>
        <tr><th>العنصر</th><th>القراءة</th><th>النطاق المرجعي</th><th>الحالة</th></tr>
        <?php
        $bloodTests = [
            ['label'=>'RBC (كريات الدم الحمراء)', 'value'=>$test['rbc_count'], 'min'=>4.7, 'max'=>6.0, 'unit'=>'مليون/μL'],
            ['label'=>'WBC (كريات الدم البيضاء)', 'value'=>$test['wbc_count'], 'min'=>4000, 'max'=>11000, 'unit'=>'/μL'],
            ['label'=>'Platelets (الصفائح الدموية)', 'value'=>$test['platelet_count'], 'min'=>150000, 'max'=>450000, 'unit'=>'/μL'],
            ['label'=>'Hemoglobin (الهيموغلوبين)', 'value'=>$test['hemoglobin_level'], 'min'=>13.5, 'max'=>17.5, 'unit'=>'g/dL'],
        ];
        foreach ($bloodTests as $b) {
            list($status, $col) = checkRange($b['value'], $b['min'], $b['max']);
            echo "<tr>
                    <td>{$b['label']}</td>
                    <td>{$b['value']}</td>
                    <td>{$b['min']} – {$b['max']} {$b['unit']}</td>
                    <td class='$col'>$status</td>
                </tr>";
        }
        ?>
    </table>

    <h2>فحوصات الأمراض المعدية</h2>
    <table>
        <tr><th>الفحص</th><th>النتيجة</th><th>الحالة</th></tr>
        <?php
        $infectiousTests = [
            ['label'=>'HIV', 'value'=>$test['hiv']],
            ['label'=>'HBV', 'value'=>$test['hbv']],
            ['label'=>'HCV', 'value'=>$test['hcv']],
            ['label'=>'Syphilis', 'value'=>$test['syphilis']],
            ['label'=>'HTLV', 'value'=>$test['htlv']],
            ['label'=>'Antibody Screening', 'value'=>$test['antibody_screening']],
        ];
        foreach ($infectiousTests as $inf) {
            list($status, $col) = statusColor($inf['value']);
            echo "<tr>
                    <td>{$inf['label']}</td>
                    <td>{$inf['value']}</td>
                    <td class='$col'>$status</td>
                </tr>";
        }
        ?>
    </table>

    <h2 class="center">حالة الدم</h2>
    <?php list($status, $col) = statusColor($test['blood_condition']); ?>
    <p class="<?= $col ?>" style="font-size: 1.3em; font-weight:bold; text-align:center;">
        <?= htmlspecialchars($test['blood_condition']) ?> (<?= $status ?>)
    </p>

</div>

<div class="btn-group">
    <button onclick="window.print()">🖨️ طباعة التقرير</button>
</div>

<script>
function savePdf() {
    let reportHtml = document.getElementById('report-content').innerHTML;

    fetch('save_pdf.php?id=<?= $blood_tests_id ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'html=' + encodeURIComponent(reportHtml) +
              '&donor=<?= urlencode($donorInfo['name']) ?>' +
              '&date=<?= urlencode($test['test_date']) ?>'
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('✅ تم حفظ التقرير بنجاح!\n📄 رابط: ' + data.file);
            window.open(data.file, '_blank');
        } else {
            alert('❌ حدث خطأ أثناء حفظ التقرير.');
        }
    })
    .catch(err => {
        alert('⚠️ خطأ في الاتصال بالسيرفر.');
        console.error(err);
    });
}

</script>

</body>
</html>
