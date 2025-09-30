<?php
session_start();
require_once 'db.php';
require_once 'class_Users.php';
require_once 'hospital_layout.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hospital') {
    die("🚫 صلاحية الدخول غير متوفرة.");
}

$user_id = $_SESSION['user_id'];

$db = new Database();
$conn = $db->connect();
$user = new Users($conn);

// جلب hospital_id من جدول hospitals
$stmt = $conn->prepare("SELECT hospitals_id FROM hospitals WHERE user_id = ?");
$stmt->execute([$user_id]);
$hospital_id = $stmt->fetchColumn();

if (!$hospital_id) {
    die("المستشفى غير موجود.");
}

// إرسال الرسالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $user->sendHospitalSupportMessage($hospital_id, $subject, $message);
 
   echo "<script>window.location.href='contact_support.php';</script>";

    exit;

}

// جلب الرسائل
$messages = $user->getHospitalSupportMessages($hospital_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الدعم الفني</title>
</head>
<body class>
    <h2 class="text-danger mb-4">💬 تواصل مع الدعم الفني</h2>

    <form method="POST" class="card shadow p-4 mb-5">
        <div class="mb-3">
            <label class="form-label">عنوان الرسالة</label>
            <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">نص الرسالة</label>
            <textarea name="message" rows="4" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">إرسال</button>
    </form>

    <h4 class="mb-3">📋 الرسائل السابقة</h4>

    <!-- مربع البحث -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث في الرسائل السابقة...">
    </div>

    <table class="table table-bordered text-center" id="messagesTable">
        <thead class="table-danger">
            <tr>
                <th>عنوان الرسالة</th>
                <th>تاريخ الإرسال</th>
                <th>تاريخ الرد</th>
                <th>تفاصيل</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
                <tr>
                    <td><?= htmlspecialchars($msg['subject']) ?></td>
                    <td><?= htmlspecialchars($msg['sent_at']) ?></td>
                    <td><?= $msg['reply'] ? htmlspecialchars($msg['replied_at']) : 'لم يتم الرد' ?></td>
                    <td>
                        <?php if ($msg['reply']): ?>
                            <a href="message_details.php?id=<?= $msg['contact_messages_id'] ?>" class="btn btn-sm btn-outline-info">عرض</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-secondary" disabled>بانتظار الرد</button>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll('#messagesTable tbody tr');

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
    </script>
</body>
</html>
