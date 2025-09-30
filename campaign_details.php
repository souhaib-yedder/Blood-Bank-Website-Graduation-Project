<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

if (!isset($_GET['id'])) {


       echo "<script>window.location.href='manage_campaigns.php';</script>";

    exit;
}

$campaign_id = intval($_GET['id']);

$db = new Database();
$conn = $db->connect();

// جلب بيانات المتبرعين المنضمين للحملة
$stmt = $conn->prepare("
    SELECT d.donation_date, u.name AS donor_name
    FROM donations d
    JOIN donors dr ON d.donor_id = dr.donors_id
    JOIN users u ON dr.user_id = u.user_id
    WHERE d.donation_campaign_id = ?
");
$stmt->execute([$campaign_id]);
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الحملة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">تفاصيل الحملة (رقم: <?= $campaign_id ?>)</h2>

    <?php if (count($donors) > 0): ?>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>اسم المتبرع</th>
                    <th>تاريخ التبرع</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($donors as $donor): ?>
                <tr>
                    <td><?= htmlspecialchars($donor['donor_name']) ?></td>
                    <td><?= htmlspecialchars($donor['donation_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info text-center">لا يوجد متبرعون مسجلون في هذه الحملة حتى الآن.</div>
    <?php endif; ?>

    <div class="text-center">
        <a href="manage_donation_campaigns.php" class="btn btn-secondary mt-3">العودة لإدارة الحملات</a>
    </div>
</div>
</body>
</html>
