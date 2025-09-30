<?php
session_start();
require_once 'staff_layout.php';
require_once 'db.php';
require_once 'class_Users.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

if (!isset($_SESSION['staff_id'])) {
    // تأكد من وجود staff_id في الجلسة (يمكنك تعديل هذا حسب نظام الجلسات لديك)
    die("خطأ: بيانات الموظف غير متوفرة.");
}

$staff_id = $_SESSION['staff_id'];

if (!isset($_GET['id'])) {
    die("معرف الرسالة غير موجود.");
}

$message_id = $_GET['id'];

$db = new Database();
$conn = $db->connect();
$users = new Users($conn);

$message = $users->supportMessageGetById($message_id);

if (!$message) {
    die("الرسالة غير موجودة.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply_text = trim($_POST['reply'] ?? '');
    if ($reply_text !== '') {
        $success = $users->supportMessageReply($message_id, $reply_text, $staff_id);
        if ($success) {
      
             echo "<script>window.location.href='staff_contact_messages.php';</script>";

    exit;
        } else {
            $error = "حدث خطأ أثناء إرسال الرد.";
        }
    } else {
        $error = "يرجى كتابة الرد قبل الإرسال.";
    }
}
?>

<div class="container mt-4">
    <h3 class="text-center text-primary mb-4">رد على رسالة دعم فني</h3>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label"><strong>عنوان الرسالة:</strong></label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($message['subject']) ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>تاريخ الإرسال:</strong></label>
            <input type="text" class="form-control" value="<?= $message['sent_at'] ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>نص الرسالة المرسلة:</strong></label>
            <textarea class="form-control" rows="5" readonly><?= htmlspecialchars($message['message']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label"><strong>الرد:</strong></label>
            <textarea name="reply" class="form-control" rows="5" <?= (!empty($message['reply'])) ? 'readonly' : '' ?>><?= htmlspecialchars($message['reply'] ?? '') ?></textarea>
        </div>

        <?php if (empty($message['reply'])): ?>
            <button type="submit" class="btn btn-success">إرسال الرد</button>
        <?php else: ?>
            <a href="staff_contact_messages.php" class="btn btn-secondary">عودة للرسائل</a>
        <?php endif; ?>
    </form>
</div>
