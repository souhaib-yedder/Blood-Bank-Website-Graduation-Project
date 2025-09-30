<?php
session_start();
require_once 'db.php';
require_once 'hospital_layout.php'; // layout الخاص بالمستشفى

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hospital') {
    die("🚫 لا تملك صلاحية الوصول إلى هذه الصفحة.");
}

$user_id = $_SESSION['user_id'];
$db = new Database();
$conn = $db->connect();

// جلب hospital_id للمستخدم الحالي
$stmt = $conn->prepare("SELECT hospitals_id FROM hospitals WHERE user_id = ?");
$stmt->execute([$user_id]);
$hospital_id = $stmt->fetchColumn();

if (!$hospital_id) {
    die("⚠️ لا يمكن العثور على بيانات المستشفى.");
}

// التحقق من وجود معرف الرسالة
if (!isset($_GET['id'])) {
    die("⚠️ معرف الرسالة غير موجود.");
}

$id = $_GET['id'];

// جلب الرسالة وتأكد أنها تخص هذا المستشفى
$stmt = $conn->prepare("SELECT * FROM contact_messages WHERE contact_messages_id = ? AND hospital_id = ?");
$stmt->execute([$id, $hospital_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    die("🚫 هذه الرسالة غير متاحة لك أو غير موجودة.");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الرسالة</title>
</head>
<body class="container my-5">
    <div class="card shadow p-4">
        <h4 class="text-primary">📌 <?= htmlspecialchars($message['subject']) ?></h4>
        <p><strong>تاريخ الإرسال:</strong> <?= $message['sent_at'] ?></p>
        <hr>
        <p><strong>نص الرسالة:</strong></p>
        <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
        <hr>
        <p><strong>تاريخ الرد:</strong> <?= $message['replied_at'] ?? 'لا يوجد رد بعد' ?></p>
        <p><strong>رد الموظف:</strong></p>
        <p><?= $message['reply'] ? nl2br(htmlspecialchars($message['reply'])) : 'لا يوجد رد حتى الآن.' ?></p>
    </div>
</body>
</html>
