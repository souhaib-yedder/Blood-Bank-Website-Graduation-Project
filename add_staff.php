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

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جلب البيانات من الفورم
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $national_id = trim($_POST['national_id'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $hiring_date = $_POST['hiring_date'] ?? '';

    // ------------------------------
    // فاليداشن PHP للسيرفر
    // ------------------------------
    if (empty($name)) $errors[] = "الاسم مطلوب.";

    // بريد إلكتروني صالح وغير مكرر
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "هذا البريد الإلكتروني مستخدم بالفعل.";
        }
    }

    // كلمة مرور قوية
    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+=-]).{12,}$/';
    if (empty($password)) {
        $errors[] = "كلمة المرور مطلوبة.";
    } elseif (!preg_match($passwordPattern, $password)) {
        $errors[] = "كلمة المرور يجب أن تحتوي على: حرف صغير، حرف كبير، رقم، رمز خاص، وطولها 12 حرفًا أو أكثر.";
    }

    if (empty($department)) $errors[] = "القسم مطلوب.";

    // رقم الهاتف 10 أرقام أو أكثر وأرقام فقط
    if (empty($phone)) {
        $errors[] = "الهاتف مطلوب.";
    } elseif (!preg_match('/^\d{10,}$/', $phone)) {
        $errors[] = "رقم الهاتف يجب أن يكون على الأقل 10 أرقام وأرقام فقط.";
    }

    if (empty($date_of_birth)) $errors[] = "تاريخ الميلاد مطلوب.";
    if (empty($national_id)) $errors[] = "الرقم الوطني مطلوب.";
    if (empty($salary) || !is_numeric($salary)) $errors[] = "الراتب يجب أن يكون رقم.";
    if (empty($hiring_date)) $errors[] = "تاريخ التعيين مطلوب.";

    // ------------------------------
    // إذا ما في أخطاء يتم الإدخال
    // ------------------------------
    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // إدخال المستخدم في جدول users
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtUser = $conn->prepare("INSERT INTO users (name, email, password, role, is_active, created_at) VALUES (?, ?, ?, 'staff', 1, NOW())");
            $stmtUser->execute([$name, $email, $hashedPassword]);
            $user_id = $conn->lastInsertId();

            // إدخال بيانات الموظف في جدول staff
            $stmtStaff = $conn->prepare("INSERT INTO staff (user_id, department, phone, date_of_birth, national_id, salary, hiring_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtStaff->execute([$user_id, $department, $phone, $date_of_birth, $national_id, $salary, $hiring_date]);

            $conn->commit();

            $success = "تم إضافة الموظف بنجاح.";
            $_POST = []; // تفريغ الفورم بعد الإدخال الناجح
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "حدث خطأ أثناء الإضافة: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>إضافة موظف جديد</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" />
</head>
<body>
<div class="container mt-4">
    <h3 class="mb-4 text-center text-primary">إضافة موظف جديد</h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">

        <!-- بيانات المستخدم -->
        <div class="mb-3">
            <label for="name" class="form-label">الاسم الكامل</label>
            <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">كلمة المرور</label>
            <input type="password" class="form-control" id="password" name="password" required
                   pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+=-]).{12,}"
                   title="كلمة المرور يجب أن تحتوي على حرف صغير، حرف كبير، رقم، رمز خاص، وطولها 12 حرفًا أو أكثر">
        </div>

        <!-- بيانات الموظف -->
        <div class="mb-3">
            <label for="department" class="form-label">القسم</label>
            <input type="text" class="form-control" id="department" name="department" required value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
        </div>
<div class="mb-3">
    <label for="phone" class="form-label">الهاتف</label>
    <input 
        type="text" 
        class="form-control" 
        id="phone" 
        name="phone" 
        required
        inputmode="numeric"
        pattern="^[0-9]{10}$" 
        title="الرجاء إدخال رقم هاتف صحيح مكون من 10 أرقام"
        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
</div>

<script>
    // منع أي حرف غير الأرقام أثناء الكتابة
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>


        <div class="mb-3">
            <label for="date_of_birth" class="form-label">تاريخ الميلاد</label>
            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
        </div>
<div class="mb-3">
    <label for="national_id" class="form-label">الرقم الوطني</label>
    <input 
        type="text" 
        class="form-control" 
        id="national_id" 
        name="national_id" 
        required
        pattern="^[0-9]+$"
        title="الرجاء إدخال الرقم الوطني بالأرقام فقط"
        value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
</div>

<script>
    // منع إدخال أي حرف أو رمز أثناء الكتابة
    const nationalIdInput = document.getElementById('national_id');
    nationalIdInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
</script>

<div class="mb-3">
    <label for="salary" class="form-label">الراتب</label>
    <input 
        type="number" 
        step="0.01" 
        min="1" 
        class="form-control" 
        id="salary" 
        name="salary" 
        required 
        value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>">
</div>


        <div class="mb-3">
            <label for="hiring_date" class="form-label">تاريخ التعيين</label>
            <input type="date" class="form-control" id="hiring_date" name="hiring_date" required value="<?= htmlspecialchars($_POST['hiring_date'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary">إضافة موظف</button>
        <a href="manage_staff.php" class="btn btn-secondary">عودة لإدارة الموظفين</a>
    </form>
</div>
</body>
</html>
