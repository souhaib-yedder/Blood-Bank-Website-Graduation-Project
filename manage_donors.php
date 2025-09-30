<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_Users.php';

// التحقق من صلاحية الأدمن
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$userObj = new Users($conn);

// تنفيذ الحذف
if (isset($_GET['delete_id'])) {
    $user_id = $_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM donors WHERE user_id = ?");
    $stmt->execute([$user_id]);

    echo "<script>window.location.href='manage_donors.php';</script>";
    exit;
}

// تنفيذ التفعيل / إلغاء التفعيل
if (isset($_GET['toggle_id'])) {
    $user_id = $_GET['toggle_id'];

    $stmt = $conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    $new_status = ($current && $current['is_active'] == 1) ? 0 : 1;

    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->execute([$new_status, $user_id]);

    echo "<script>window.location.href='manage_donors.php';</script>";
    exit;
}

// جلب بيانات المتبرعين
$donors = $userObj->getAllDonors();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المتبرعين</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body>
<div class="container mt-4">
    <h3 class="mb-4 text-center text-primary">إدارة المتبرعين</h3>

    <!-- 🔍 مربع البحث -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث عن متبرع بالاسم، البريد، فصيلة الدم...">
    </div>

    <table class="table table-bordered table-striped" id="requestsTable">
        <thead class="table-danger">
            <tr>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>فصيلة الدم</th>
                <th>تاريخ الميلاد</th>
                <th>الجنس</th>
                <th>الهاتف</th>
                <th>العنوان</th>
                <th>تاريخ آخر تبرع</th>
                <th>تاريخ التسجيل</th>
                <th>الحالة</th>
                <th>إجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($donors as $donor): ?>
            <tr>
                <td><?= htmlspecialchars($donor['name']) ?></td>
                <td><?= htmlspecialchars($donor['email']) ?></td>
                <td><?= htmlspecialchars($donor['blood_type']) ?></td>
                <td><?= htmlspecialchars($donor['birth_date']) ?></td>
                <td><?= htmlspecialchars($donor['gender']) ?></td>
                <td><?= htmlspecialchars($donor['phone']) ?></td>
                <td><?= htmlspecialchars($donor['address']) ?></td>
                <td><?= htmlspecialchars($donor['last_donation_date']) ?></td>
                <td><?= htmlspecialchars($donor['created_at']) ?></td>
                <td>
                    <?= $donor['is_active'] ? '<span class="text-success">مفعل</span>' : '<span class="text-danger">معطل</span>' ?>
                </td>
                <td>
                    <a href="?toggle_id=<?= $donor['user_id'] ?>" class="btn btn-sm <?= $donor['is_active'] ? 'btn-warning' : 'btn-success' ?>"
                       onclick="return confirm('هل أنت متأكد من تغيير حالة التفعيل؟')">
                        <?= $donor['is_active'] ? 'إلغاء التفعيل' : 'تفعيل' ?>
                    </a>
                    <a href="?delete_id=<?= $donor['user_id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('هل أنت متأكد من حذف المتبرع؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 🔍 سكربت البحث -->
<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#requestsTable tbody tr"); // تم تعديل المعرف هنا

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
