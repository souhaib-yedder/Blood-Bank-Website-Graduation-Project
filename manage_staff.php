<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_Users.php';
require_once 'class_Staff.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$users = new Users($conn);
$staff = new Staff($conn);

// تنفيذ الحذف
if (isset($_GET['delete_id'])) {
    $user_id = intval($_GET['delete_id']);

    $users->deleteUser($user_id);
    $stmt = $conn->prepare("DELETE FROM staff WHERE user_id = ?");
    $stmt->execute([$user_id]);

    echo "<script>window.location.href='manage_staff.php';</script>";
    exit;
}

// تنفيذ تفعيل/إلغاء التفعيل
if (isset($_GET['toggle_status_id'])) {
    $user_id = intval($_GET['toggle_status_id']);

    $stmt = $conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if ($row) {
        $current_status = $row['is_active'];
        $new_status = $current_status == 1 ? 0 : 1;
        $users->toggleUserActivation($user_id, $new_status);
    }

    echo "<script>window.location.href='manage_staff.php';</script>";
    exit;
}

$staffUsers = $users->getAllStaffUsers();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>إدارة الموظفين</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" />
    <style>
        #searchInput {
            max-width: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h3 class="mb-4 text-center text-primary">إدارة الموظفين</h3>

    <!-- حقل البحث -->
    <input type="text" id="searchInput" class="form-control mx-auto" placeholder="ابحث باسم الموظف أو البريد...">

    <table class="table table-bordered table-striped" id="staffTable">
        <thead class="table-danger">
            <tr>
                <th>الاسم</th>
                <th>البريد الإلكتروني</th>
                <th>القسم</th>
                <th>الهاتف</th>
                <th>تاريخ الميلاد</th>
                <th>الرقم الوطني</th>
                <th>الراتب</th>
                <th>تاريخ التعيين</th>
                <th>تاريخ التسجيل</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($staffUsers as $user): 
            $staffData = $staff->getStaffByUserId($user['user_id']);
        ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($staffData['department'] ?? '-') ?></td>
                <td><?= htmlspecialchars($staffData['phone'] ?? '-') ?></td>
                <td><?= htmlspecialchars($staffData['date_of_birth'] ?? '-') ?></td>
                <td><?= htmlspecialchars($staffData['national_id'] ?? '-') ?></td>
                <td><?= htmlspecialchars($staffData['salary'] ?? '-') ?></td>
                <td><?= htmlspecialchars($staffData['hiring_date'] ?? '-') ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td><?= $user['is_active'] == 1 ? '<span class="text-success">مفعل</span>' : '<span class="text-danger">معطل</span>' ?></td>
                <td>
                    <a href="?toggle_status_id=<?= $user['user_id'] ?>" class="btn btn-sm <?= $user['is_active'] == 1 ? 'btn-warning' : 'btn-success' ?>">
                        <?= $user['is_active'] == 1 ? 'إلغاء التفعيل' : 'تفعيل' ?>
                    </a>
                    <a href="?delete_id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف الموظف؟')">حذف</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- JavaScript للبحث في جميع الأعمدة -->
<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll("#staffTable tbody tr");

    rows.forEach(function(row) {
        const cells = row.querySelectorAll('td');
        let match = false;
        cells.forEach(function(cell) {
            if (cell.textContent.toLowerCase().includes(filter)) {
                match = true;
            }
        });
        row.style.display = match ? "" : "none";
    });
});
</script>

</body>
</html>
