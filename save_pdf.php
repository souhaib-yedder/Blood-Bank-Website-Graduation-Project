<?php
ob_start();
session_start();
require_once 'db.php';
require_once 'tcpdf/tcpdf.php'; // تأكد من صحة المسار
require_once 'class_BloodTest.php';

header('Content-Type: application/json');

// التحقق من وجود المستخدم المتبرع
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// تحقق من وجود البيانات
if (!isset($_POST['html'], $_POST['donor'], $_POST['date'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// استلام البيانات
$html = $_POST['html'];
$donorName = preg_replace('/[^a-zA-Z0-9_\x{0600}-\x{06FF}]/u', '_', $_POST['donor']); // إزالة الرموز غير الآمنة
$testDate = preg_replace('/[^0-9\-]/', '', $_POST['date']);

// توليد اسم الملف
$filename = 'test_result_' . $donorName . '_' . $testDate . '_' . time() . '.pdf';
$savePath = __DIR__ . '/test_result/' . $filename; // مجلد الحفظ

// إنشاء مجلد إذا لم يكن موجودًا
if (!file_exists(__DIR__ . '/test_result')) {
    mkdir(__DIR__ . '/test_result', 0777, true);
}

// إنشاء PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetTitle('نتيجة تحليل الدم');
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 12);
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output($savePath, 'F'); // حفظ على السيرفر

// تحديث قاعدة البيانات
try {
    $db = new Database();
    $conn = $db->connect();

    $bloodTest = new BloodTest($conn);

    // استخراج donors_id
    $stmt = $conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $donorRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$donorRow) throw new Exception("Donor not found.");

    // تحديث الحقل test_file في جدول blood_tests
    $stmt = $conn->prepare("UPDATE blood_tests SET test_file = ? WHERE donors_id = ? AND test_date = ?");
    $stmt->execute([$filename, $donorRow['donors_id'], $testDate]);

    echo json_encode([
        'success' => true,
        'file' => 'test_result/' . $filename
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
