<?php
session_start();

$role = $_SESSION['role'] ?? 'unknown';
$message = '';

switch ($role) {
    case 'admin':
        $message = '⚠️ لا تملك صلاحية الوصول كـ "أدمن".';
        break;
    case 'staff':
        $message = '⚠️ لا تملك صلاحية الوصول كـ "موظف".';
        break;
    case 'hospital':
        $message = '⚠️ لا تملك صلاحية الوصول كـ "مستشفى".';
        break;
    case 'donor':
        $message = '⚠️ لا تملك صلاحية الوصول كـ "متبرع".';
        break;
    default:
        $message = '⚠️ ليس لديك صلاحية للوصول إلى هذه الصفحة.';
        break;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دخول غير مصرح</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body class="bg-light text-center p-5">
    <div class="container">
        <div class="alert alert-danger mt-5">
            <h3>🚫 دخول غير مصرح</h3>
            <p><?= $message ?></p>
        </div>
        <a href="index.php" class="btn btn-primary mt-3">العودة إلى الصفحة الرئيسية</a>
    </div>
</body>
</html>
