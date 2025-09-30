<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_DonationCampaigns.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$campaigns = new DonationCampaigns($conn);

// حذف الحملة عبر AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $campaign_id = intval($_POST['delete_id']);
    $result = $campaigns->removeCampaignById($campaign_id);
    echo $result ? 'success' : 'fail';
    exit();
}

$allCampaigns = $campaigns->getAllCampaigns();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الحملات التطوعية</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #searchInput {
            max-width: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">إدارة الحملات التطوعية</h2>

    <!-- مربع البحث -->
    <input type="text" id="searchInput" class="form-control mx-auto" placeholder="ابحث باسم الحملة أو الموقع أو التاريخ..." onkeyup="filterTable()">

    <table class="table table-bordered table-striped" id="requestsTable">
        <thead class="table-danger">
            <tr>
                <th>اسم الحملة</th>
                <th>التاريخ</th>
                <th>الموقع</th>
                <th>الهدف</th>
                <th>الوصف</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allCampaigns as $row): ?>
            <tr id="row_<?= $row['donation_campaigns_id'] ?>">
                <td><?= htmlspecialchars($row['campaign_name']) ?></td>
                <td><?= htmlspecialchars($row['campaign_date']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= htmlspecialchars($row['target_units']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td>
                    <a href="edit_donation_campaigns.php?id=<?= $row['donation_campaigns_id'] ?>" class="btn btn-sm btn-primary">تعديل</a>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $row['donation_campaigns_id'] ?>">حذف</button>
                    <a href="campaign_details.php?id=<?= $row['donation_campaigns_id'] ?>" class="btn btn-sm btn-info">عرض التفاصيل</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- حذف الحملة -->
<script>
$(document).on("click", ".delete-btn", function () {
    if (confirm("هل أنت متأكد من حذف هذه الحملة؟")) {
        let id = $(this).data("id");
        $.post("manage_donation_campaigns.php", { delete_id: id }, function (response) {
            if (response.trim() === 'success') {
                $("#row_" + id).remove();
                alert("تم حذف الحملة بنجاح");
            } else {
                window.location.href='manage_donation_campaigns.php';
            }
        });
    }
});

// 🔍 تصحيح البحث
function filterTable() {
    var input = document.getElementById("searchInput").value.toLowerCase();
    var rows = document.querySelectorAll("#requestsTable tbody tr");

    rows.forEach(function(row) {
        let rowText = row.textContent.toLowerCase();
        row.style.display = rowText.includes(input) ? "" : "none";
    });
}
</script>
</body>
</html>
