<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'class_Users.php';
require_once 'class_Hospital.php';
require_once 'vendor/autoload.php'; // PHPMailer

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$users = new Users($conn);
$hospital = new Hospital($conn);

// حذف مستشفى
if (isset($_GET['delete_id'])) {
    $hospitals_id = intval($_GET['delete_id']);

    $stmt = $conn->prepare("SELECT user_id FROM hospitals WHERE hospitals_id = ?");
    $stmt->execute([$hospitals_id]);
    $row = $stmt->fetch();

    if ($row) {
        $user_id = $row['user_id'];
        $hospital->deleteHospital($hospitals_id);
        $stmt2 = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt2->execute([$user_id]);
    }

    echo "<script>window.location.href='manage_hospitals.php';</script>";
    exit;
}

// تفعيل / إلغاء التفعيل
if (isset($_GET['toggle_status_id'])) {
    $user_id = intval($_GET['toggle_status_id']);
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if ($row) {
        $new_status = $row['is_active'] == 1 ? 0 : 1;
        $users->toggleUserActivation($user_id, $new_status);

        $hospital_status = $new_status == 1 ? 'approved' : 'rejected';
        $stmt_update = $conn->prepare("UPDATE hospitals SET status = ? WHERE user_id = ?");
        $stmt_update->execute([$hospital_status, $user_id]);

        $stmt2 = $conn->prepare("SELECT u.email, h.hospital_name 
                                FROM users u 
                                JOIN hospitals h ON u.user_id = h.user_id 
                                WHERE u.user_id = ?");
        $stmt2->execute([$user_id]);
        $info = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($info) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'expensetracker04@gmail.com';
                $mail->Password = 'lcxyixesqpsipykf';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom('expensetracker04@gmail.com', 'نظام بنك الدم');
                $mail->addAddress($info['email'], $info['hospital_name']);
                $mail->isHTML(true);

                if ($new_status == 1) {
                    $mail->Subject = 'تم تفعيل حساب المستشفى';
                    $mail->Body = "
                        <h3>تم تفعيل حساب المستشفى الخاص بك</h3>
                        <p>تم التأكد من بيانات المستشفى <strong>{$info['hospital_name']}</strong>.</p>
                        <p>يمكنك الآن تسجيل الدخول إلى حسابك واستخدام النظام.</p>
                        <p>شكرًا لتعاونكم.</p>
                    ";
                } else {
                    $mail->Subject = 'تم إلغاء تفعيل حساب المستشفى';
                    $mail->Body = "
                        <h3>نأسف، تم رفض تفعيل حساب المستشفى</h3>
                        <p>بيانات المستشفى <strong>{$info['hospital_name']}</strong> لم يتم قبولها.</p>
                        <p>للمزيد من التفاصيل يرجى التواصل مع إدارة النظام.</p>
                        <p>شكرًا لتفهمكم.</p>
                    ";
                }

                $mail->send();
            } catch (Exception $e) {
                error_log("Email error: " . $mail->ErrorInfo);
            }
        }
    }

    echo "<script>window.location.href='manage_hospitals.php';</script>";
    exit;
}

$hospitals = $hospital->getAllHospitalsWithUsers();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المستشفيات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body>
<div class="container mt-4">
    <h3 class="mb-4 text-center text-primary">إدارة المستشفيات</h3>

    <!-- 🔍 مربع البحث -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث عن مستشفى، البريد، المستخدم...">
    </div>

    <table class="table table-bordered table-striped" id="requestsTable">
        <thead class="table-danger">
            <tr>
                <th>اسم المستشفى</th>
                <th>اسم المستخدم</th>
                <th>البريد الإلكتروني</th>
                <th>الهاتف</th>
                <th>الموقع</th>
                <th>الحالة</th>
                <th>الملفات</th>
                <th>تاريخ التسجيل</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($hospitals as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['hospital_name']) ?></td>
                <td><?= htmlspecialchars($h['name']) ?></td>
                <td><?= htmlspecialchars($h['email']) ?></td>
                <td><?= htmlspecialchars($h['phone']) ?></td>
                <td><?= htmlspecialchars($h['location']) ?></td>
                <td>
                    <?= $h['is_active'] == 1 ? '<span class="text-success">مفعل</span>' : '<span class="text-danger">معطل</span>' ?>
                </td>
                <td>
                    <?php 
                    $files = ['letter_file', 'license_file', 'tax_file', 'id_file'];
                    foreach ($files as $file) {
                        if (!empty($h[$file])) {
                            echo "<a href='uploads/hospitals/{$h[$file]}' target='_blank' class='d-block'>" . ucfirst(str_replace('_', ' ', $file)) . "</a>";
                        } else {
                            echo "<span class='text-muted d-block'>-</span>";
                        }
                    }
                    ?>
                </td>
                <td><?= htmlspecialchars($h['created_at']) ?></td>
                <td>
                    <a href="?toggle_status_id=<?= $h['user_id'] ?>" class="btn btn-sm <?= $h['is_active'] == 1 ? 'btn-warning' : 'btn-success' ?>">
                        <?= $h['is_active'] == 1 ? 'إلغاء التفعيل' : 'تفعيل' ?>
                    </a>
                    <a href="?delete_id=<?= $h['hospitals_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف المستشفى؟')">حذف</a>
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
    const rows = document.querySelectorAll("#requestsTable tbody tr"); // تم تعديل id ليطابق الجدول

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
