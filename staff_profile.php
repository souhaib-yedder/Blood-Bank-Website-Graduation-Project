<?php
session_start();
require_once 'db.php';
require_once 'class_Staff.php';
require_once 'staff_layout.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: unauthorized.php");
    exit();
}

$conn = (new Database())->connect();
$staff = new Staff($conn);

$user_id = $_SESSION['user_id'];
$data = $staff->getStaffByUserId($user_id);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    $update1 = $staff->updatePhone($user_id, $phone);
    $update2 = $staff->updateUserInfo($user_id, $name, $email);

    if ($update1 || $update2) {
        $success = "تم تحديث البيانات بنجاح.";
        $data = $staff->getStaffByUserId($user_id); // تحديث البيانات المعروضة
    } else {
        $error = "لم يتم تحديث البيانات أو لا توجد تغييرات.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ملفي الشخصي - مشرف</title>
 
</head>
<body>
<div class="container py-5">
    <h2 class="text-center text-primary mb-4">ملفي الشخصي</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row mb-3">
           <div class="col-md-6">
    <label>الاسم:</label>
    <input type="text" name="name" class="form-control" 
           value="<?= htmlspecialchars($data['name']) ?>" required
           pattern="^[A-Za-z\u0600-\u06FF\s]+$"
           title="الاسم يجب أن يحتوي على حروف فقط (بدون أرقام أو رموز خاصة)"
           oninput="this.value = this.value.replace(/[^A-Za-z\u0600-\u06FF\s]/g, '')">
</div>
<div class="col-md-6">
    <label>البريد الإلكتروني:</label>
    <input type="email" name="email" class="form-control" 
           value="<?= htmlspecialchars($data['email']) ?>" 
           readonly>
</div>


        <div class="row mb-3">
           <div class="col-md-6">
    <label>رقم الهاتف:</label>
    <input type="text" name="phone" class="form-control" 
           value="<?= htmlspecialchars($data['phone']) ?>" required
           pattern="^[0-9]{10}$"
           minlength="10" maxlength="10"
           title="رقم الهاتف يجب أن يحتوي على 10 أرقام فقط"
           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
</div>

            <div class="col-md-6">
                <label>القسم:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['department']) ?>" disabled>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label>تاريخ الميلاد:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['date_of_birth']) ?>" disabled>
            </div>
            <div class="col-md-4">
                <label>الرقم الوطني:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['national_id']) ?>" disabled>
            </div>
            <div class="col-md-4">
                <label>تاريخ التوظيف:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['hiring_date']) ?>" disabled>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>الراتب:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['salary']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label>تاريخ التسجيل في النظام:</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($data['created_at']) ?>" disabled>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">تحديث</button>
    </form>
</div>
</body>
</html>
