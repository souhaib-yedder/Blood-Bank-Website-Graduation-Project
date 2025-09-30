<?php
session_start();
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_Users.php';

// التحقق من الصلاحية
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$users = new Users($conn);

$messages = $users->supportMessageGetAll();
?>

<div class="container mt-4">
    <h3 class="text-center text-danger mb-4">📨 رسائل الدعم الفني</h3>

    <!-- ✅ حقل البحث -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث عن مستشفى، العنوان، أو محتوى الرسالة...">
    </div>

         <table class="table table-bordered table-striped" id="requestsTable">
    <thead class="table-danger">
            <tr>
                <th>اسم المستشفى</th>
                <th>العنوان</th>
                <th>الرسالة</th>
                <th>تاريخ الإرسال</th>
                <th>تاريخ الرد</th>
                <th>المجيب</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
                <tr>
                    <td><?= htmlspecialchars($msg['hospital_name']) ?></td>
                    <td><?= htmlspecialchars($msg['subject']) ?></td>
                    <td><?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 50, '...')) ?></td>
                    <td><?= $msg['sent_at'] ?></td>
                    <td><?= $msg['replied_at'] ?? 'لم يتم الرد بعد' ?></td>
                    <td><?= $msg['staff_name'] ?? 'لا يوجد مجيب' ?></td>
                    <td>
                        <?php if (empty($msg['reply'])): ?>
                            <a href="staff_contact_message_reply.php?id=<?= $msg['contact_messages_id'] ?>" class="btn btn-sm btn-danger">رد على الرسالة</a>
                        <?php else: ?>
                            <a href="staff_contact_message_reply.php?id=<?= $msg['contact_messages_id'] ?>" class="btn btn-sm btn-info">عرض التفاصيل</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ✅ JavaScript للبحث -->
<script>
    document.getElementById('searchInput').addEventListener('keyup', function () {
        var filter = this.value.toLowerCase();
        var rows = document.querySelectorAll("#supportMessagesTable tbody tr");

        rows.forEach(function (row) {
            var text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>
