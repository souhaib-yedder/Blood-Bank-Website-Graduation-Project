<?php
session_start();
require_once 'db.php';
require_once 'class_report.php';
require_once 'admin_layout.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("🚫 لا تملك صلاحية الوصول.");
}

$db = new Database();
$conn = $db->connect();
$report = new Report($conn);

$message = '';

// حذف التقرير
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $report_id = intval($_GET['id']);
    if ($report_id > 0) {
        $deleted = $report->deleteReportByAdmin($report_id);
        $message = $deleted
            ? "<div class='alert alert-success'>✅ تم حذف التقرير بنجاح.</div>"
            : "<div class='alert alert-danger'>❌ فشل الحذف. تحقق من وجود التقرير.</div>";
    } else {
        $message = "<div class='alert alert-danger'>⚠️ معرف التقرير غير صالح.</div>";
    }
}

$reports = $report->getAllReportsWithStaffName();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>جميع التقارير - الإدارة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        table th, table td {
            vertical-align: middle;
            text-align: center;
            word-break: break-word;
        }
        #searchInput {
            max-width: 300px;
            margin: 20px auto;
        }
    </style>
    <script>
        function confirmDelete(id) {
            if (confirm("❗ هل أنت متأكد أنك تريد حذف هذا التقرير؟")) {
                window.location.href = "?action=delete&id=" + id;
            }
        }

        function filterReports() {
            const input = document.getElementById("searchInput").value.toLowerCase();
            const rows = document.querySelectorAll("#requestsTable tbody tr"); // تصحيح الـ id

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(input) ? "" : "none";
            });
        }
    </script>
</head>
<body>

<div class="container">
    <h1 class="mb-4 text-center">📋 جميع التقارير</h1>
    <?= $message ?>

    <!-- 🔍 حقل البحث -->
    <input type="text" id="searchInput" class="form-control" placeholder="ابحث حسب العنوان أو الموظف أو النوع..." onkeyup="filterReports()">

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="requestsTable">
            <thead class="table-danger">
                <tr>
                    <th>نوع التقرير</th>
                    <th>العنوان</th>
                    <th>المحتوى</th>
                    <th>درجة الأولوية</th>
                    <th>الموظف</th>
                    <th>الملف المرفق</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reports): ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['report_type']) ?></td>
                            <td><?= htmlspecialchars($r['report_title']) ?></td>
                            <td style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($r['report_body'])) ?></td>
                            <td><?= htmlspecialchars($r['priority']) ?></td>
                            <td><?= htmlspecialchars($r['staff_name'] ?? 'غير معروف') ?></td>
                            <td>
                                <?php if (!empty($r['attachment_file'])): ?>
                                    <a href="uploads/reports/<?= htmlspecialchars($r['attachment_file']) ?>" target="_blank" class="btn btn-sm btn-info">📎 فتح الملف</a>
                                <?php else: ?>
                                    <span class="text-muted">لا يوجد</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['created_at']) ?></td>
                            <td>
                                <a href="edit_report.php?id=<?= $r['report_id'] ?>" class="btn btn-sm btn-success mb-1">✏️ تعديل</a>
                                <button onclick="confirmDelete(<?= $r['report_id'] ?>)" class="btn btn-sm btn-danger">🗑 حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted">لا توجد تقارير حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
