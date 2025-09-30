<?php
session_start();
require_once 'db.php';
require_once 'class_report.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("🚫 لا تملك صلاحية الوصول.");
}

$db = new Database();
$conn = $db->connect();
$report = new Report($conn);

$message = '';

// ✅ حذف التقرير عبر POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $report_id = intval($_POST['delete_id']);
    $deleted = $report->deleteReport($report_id, $_SESSION['user_id']);
    if ($deleted) {
        $message = "<div class='alert alert-success'>✅ تم حذف التقرير بنجاح.</div>";
    } else {
        $message = "<div class='alert alert-danger'>❌ فشل في حذف التقرير.</div>";
    }
}

// ✅ إرسال تقرير جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_type'])) {
    $staff_id = $_SESSION['user_id'];
    $report_type = $_POST['report_type'];
    $report_title = $_POST['report_title'];
    $report_body = $_POST['report_body'];
    $priority = $_POST['priority'];

    $attachment_file = $report->uploadAttachmentFile($_FILES['attachment_file']);

    if ($report->createReport($staff_id, $report_type, $report_title, $report_body, $priority, $attachment_file)) {
        $message = "<div class='alert alert-success'>✅ تم إضافة التقرير بنجاح.</div>";
    } else {
        $message = "<div class='alert alert-danger'>❌ حدث خطأ أثناء إضافة التقرير.</div>";
    }
}

// ✅ جلب تقارير الموظف
$reports = $report->getReportsByStaffId($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>إنشاء تقرير - الموظف</title>

</head>
<body>

<div class="container">

    <h1 class="mb-4 text-center">إنشاء تقرير جديد</h1>

    <?= $message ?>

    <div class="form-section">
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label for="report_type" class="form-label">نوع التقرير:</label>
                <input type="text" id="report_type" name="report_type" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="report_title" class="form-label">عنوان التقرير:</label>
                <input type="text" id="report_title" name="report_title" class="form-control" required>
            </div>
            <div class="col-12">
                <label for="report_body" class="form-label">محتوى التقرير:</label>
                <textarea id="report_body" name="report_body" class="form-control" rows="5" required></textarea>
            </div>
            <div class="col-md-4">
                <label for="priority" class="form-label">درجة الأولوية:</label>
                <select id="priority" name="priority" class="form-select" required>
                    <option value="عالية">عالية</option>
                    <option value="متوسطة">متوسطة</option>
                    <option value="منخفضة">منخفضة</option>
                </select>
            </div>
            <div class="col-md-8">
                <label for="attachment_file" class="form-label">إرفاق ملف (اختياري):</label>
                <input type="file" id="attachment_file" name="attachment_file" class="form-control">
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary btn-lg mt-3">إرسال التقرير</button>
            </div>
        </form>
    </div>

    <h2 class="mb-3">تقاريرك السابقة</h2>

    <!-- حقل البحث -->
    <div class="mb-3">
        <input type="text" id="searchReportsInput" class="form-control" placeholder="🔍 ابحث في التقارير السابقة (نوع، عنوان، محتوى، ...)">
    </div>

    <div class="table-responsive">
               <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
                <tr>
                    <th>م</th>
                    <th>نوع التقرير</th>
                    <th>العنوان</th>
                    <th>المحتوى</th>
                    <th>الأولوية</th>
                    <th>الموظف</th>
                    <th>المرفق</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reports): $i = 1;
                foreach ($reports as $r): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($r['report_type']) ?></td>
                        <td><?= htmlspecialchars($r['report_title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($r['report_body'])) ?></td>
                        <td><?= htmlspecialchars($r['priority']) ?></td>
                        <td><?= htmlspecialchars($r['staff_name']) ?></td>
                        <td>
                            <?php if ($r['attachment_file']): ?>
                                <a href="uploads/reports/<?= htmlspecialchars($r['attachment_file']) ?>" target="_blank" class="btn btn-sm btn-info">عرض</a>
                            <?php else: ?>
                                <span class="text-muted">لا يوجد</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $r['created_at'] ?></td>
                        <td>
                            <a href="staff_edit_report.php?id=<?= $r['report_id'] ?>" class="btn btn-sm btn-success mb-1">تعديل</a>
                            <form method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟');" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $r['report_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="9" class="text-center">لا توجد تقارير بعد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    document.getElementById('searchReportsInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#reportsTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

</body>
</html>
