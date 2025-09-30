<?php
session_start();
require_once 'db.php';
require_once 'class_report.php';
require_once 'admin_layout.php'; // تصميم صفحة الادمن

// تحقق من صلاحية الدخول: فقط admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("🚫 لا تملك صلاحية الوصول.");
}

// تحقق من وجود ID في الرابط
if (!isset($_GET['id'])) {
    die("🚫 لم يتم تحديد التقرير.");
}

$report_id = intval($_GET['id']);

$db = new Database();
$conn = $db->connect();
$report = new Report($conn);

// جلب بيانات التقرير
$report_data = $report->getReportByIdForAdmin($report_id);
if (!$report_data) {
    die("🚫 التقرير غير موجود.");
}

$message = '';

// معالجة التحديث عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'];
    $report_title = $_POST['report_title'];
    $report_body = $_POST['report_body'];
    $priority = $_POST['priority'];

    // الملف القديم
    $attachment_file = $report_data['attachment_file'];

    // تحقق هل تم رفع ملف جديد
    if (!empty($_FILES['attachment_file']['name'])) {
        $upload_dir = 'uploads/reports/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // أضف حماية من نوع الملف هنا إذا تريد (مثلاً jpg, pdf, docx فقط)
        $attachment_file = time() . '_' . basename($_FILES['attachment_file']['name']);
        move_uploaded_file($_FILES['attachment_file']['tmp_name'], $upload_dir . $attachment_file);
    }

    // تحديث التقرير
    if ($report->updateReportForAdmin($report_id, $report_type, $report_title, $report_body, $priority, $attachment_file)) {
        $message = '<div class="alert alert-success" role="alert">✅ تم تحديث التقرير بنجاح.</div>';
        // تحديث البيانات للعرض بعد التعديل
        $report_data = $report->getReportByIdForAdmin($report_id);
    } else {
        $message = '<div class="alert alert-danger" role="alert">❌ حدث خطأ أثناء التحديث.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>تعديل تقرير</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="container py-5" style="max-width: 800px;">
    <h1 class="text-center mb-4">تعديل التقرير</h1>

    <?= $message ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
            <label for="report_type" class="form-label fw-bold">نوع التقرير:</label>
            <input type="text" id="report_type" name="report_type" 
                   value="<?= htmlspecialchars($report_data['report_type']) ?>" 
                   class="form-control" required />
        </div>

        <div class="mb-3">
            <label for="report_title" class="form-label fw-bold">عنوان التقرير:</label>
            <input type="text" id="report_title" name="report_title" 
                   value="<?= htmlspecialchars($report_data['report_title']) ?>" 
                   class="form-control" required />
        </div>

        <div class="mb-3">
            <label for="report_body" class="form-label fw-bold">محتوى التقرير:</label>
            <textarea id="report_body" name="report_body" rows="6" class="form-control" required><?= htmlspecialchars($report_data['report_body']) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="priority" class="form-label fw-bold">درجة الأولوية:</label>
            <select id="priority" name="priority" class="form-select" required>
                <option value="عالية" <?= $report_data['priority'] === 'عالية' ? 'selected' : '' ?>>عالية</option>
                <option value="متوسطة" <?= $report_data['priority'] === 'متوسطة' ? 'selected' : '' ?>>متوسطة</option>
                <option value="منخفضة" <?= $report_data['priority'] === 'منخفضة' ? 'selected' : '' ?>>منخفضة</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">الملف الحالي:</label><br />
            <?php if ($report_data['attachment_file']): ?>
                <a href="uploads/reports/<?= htmlspecialchars($report_data['attachment_file']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">📎 عرض الملف</a>
            <?php else: ?>
                <span class="text-muted">لا يوجد ملف مرفق</span>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label for="attachment_file" class="form-label fw-bold">رفع ملف جديد (اختياري):</label>
            <input type="file" id="attachment_file" name="attachment_file" class="form-control" />
        </div>

        <button type="submit" class="btn btn-primary w-100">💾 تحديث التقرير</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
