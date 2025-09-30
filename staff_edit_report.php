<?php
session_start();
require_once 'db.php';
require_once 'class_report.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("🚫 لا تملك صلاحية الوصول.");
}

if (!isset($_GET['id'])) {
    die("🚫 لم يتم تحديد التقرير للتعديل.");
}

$db = new Database();
$conn = $db->connect();
$report = new Report($conn);

$staff_id = $_SESSION['user_id'];
$report_id = intval($_GET['id']);

$report_data = $report->getReportById($report_id, $staff_id);
if (!$report_data) {
    die("🚫 التقرير غير موجود أو لا تملك صلاحية التعديل.");
}

$message = '';

// معالجة تحديث التقرير
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'];
    $report_title = $_POST['report_title'];
    $report_body = $_POST['report_body'];
    $priority = $_POST['priority'];

    $attachment_file = $report_data['attachment_file']; // الملف القديم

    // تحقق هل تم رفع ملف جديد
    if (!empty($_FILES['attachment_file']['name'])) {
        $upload_dir = 'uploads/reports/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $attachment_file = time() . '_' . basename($_FILES['attachment_file']['name']);
        move_uploaded_file($_FILES['attachment_file']['tmp_name'], $upload_dir . $attachment_file);
    }

    if ($report->updateReport($report_id, $staff_id, $report_type, $report_title, $report_body, $priority, $attachment_file)) {
        $message = "<p style='color: green;'>✅ تم تحديث التقرير بنجاح.</p>";
        // تحديث البيانات للعرض
        $report_data = $report->getReportById($report_id, $staff_id);
    } else {
        $message = "<p style='color: red;'>❌ حدث خطأ أثناء تحديث التقرير.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>تعديل تقرير</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: auto; padding: 20px; }
        form { background: #f9f9f9; padding: 25px; border-radius: 8px; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type=text], textarea, select { width: 100%; padding: 10px; margin-top: 5px; font-size: 1rem; }
        textarea { resize: vertical; min-height: 120px; }
        button { margin-top: 20px; padding: 12px 20px; background: #007bff; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 1.1rem; }
        .file-link { margin-top: 10px; display: inline-block; }
    </style>
</head>
<body>

<h1>تعديل التقرير</h1>

<?= $message ?>

<form method="POST" enctype="multipart/form-data">
    <label for="report_type">نوع التقرير:</label>
    <input type="text" id="report_type" name="report_type" value="<?= htmlspecialchars($report_data['report_type']) ?>" required>

    <label for="report_title">عنوان التقرير:</label>
    <input type="text" id="report_title" name="report_title" value="<?= htmlspecialchars($report_data['report_title']) ?>" required>

    <label for="report_body">محتوى التقرير:</label>
    <textarea id="report_body" name="report_body" required><?= htmlspecialchars($report_data['report_body']) ?></textarea>

    <label for="priority">درجة الأولوية:</label>
    <select id="priority" name="priority" required>
        <option value="عالية" <?= $report_data['priority'] === 'عالية' ? 'selected' : '' ?>>عالية</option>
        <option value="متوسطة" <?= $report_data['priority'] === 'متوسطة' ? 'selected' : '' ?>>متوسطة</option>
        <option value="منخفضة" <?= $report_data['priority'] === 'منخفضة' ? 'selected' : '' ?>>منخفضة</option>
    </select>

    <label>الملف المرفق الحالي:</label>
    <?php if ($report_data['attachment_file']): ?>
        <a class="file-link" href="uploads/reports/<?= htmlspecialchars($report_data['attachment_file']) ?>" target="_blank">فتح الملف</a>
    <?php else: ?>
        <span>لا يوجد ملف مرفق</span>
    <?php endif; ?>

    <label for="attachment_file">رفع ملف جديد (اختياري):</label>
    <input type="file" id="attachment_file" name="attachment_file">

    <button type="submit">تحديث التقرير</button>
</form>

</body>
</html>
