<?php
session_start();

require_once 'db.php';
require_once 'class_Users.php';

$users = new Users($conn);
$msg = '';

$db = new Database();
$conn = $db->connect();

if (!isset($_SESSION['reset_email'])) {
 

       echo "<script>window.location.href='ForgotPassword.php';</script>";

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];

    if ($pass1 === $pass2) {
        $users->resetPassword($email, $pass1);
        session_destroy();
        header("Location: login.php");
        exit;
    } else {
        $msg = "❌ كلمتا المرور غير متطابقتين.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>إعادة تعيين كلمة المرور - بنك الدم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to right, #2b2b2b, #1c1c1c);
            color: white;
            font-family: 'Tajawal', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .reset-password-card {
            background-color: #1e1e1e;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 25px rgba(220, 53, 69, 0.6);
            animation: fadeIn 1.2s ease-in-out;
            text-align: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .reset-password-card h3 {
            margin-bottom: 25px;
            color: #dc3545;
            font-weight: bold;
        }

        .form-control {
            background-color: #2e2e2e;
            border: 1px solid #444;
            color: white;
        }

        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 8px #dc3545;
        }

        .btn-reset {
            background-color: #dc3545;
            border: none;
            transition: 0.3s;
            width: 100%;
            font-weight: bold;
        }

        .btn-reset:hover {
            background-color: #c82333;
            transform: scale(1.03);
        }

        .input-group-text {
            background-color: #2e2e2e;
            border: 1px solid #444;
            color: #dc3545;
        }

        .message {
            margin-top: 20px;
            color: #ff6666;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="reset-password-card">
    <h3>إعادة تعيين كلمة المرور</h3>

    <form method="post" novalidate>
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="كلمة المرور الجديدة" required>
            </div>
        </div>

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="confirm_password" class="form-control" placeholder="تأكيد كلمة المرور" required>
            </div>
        </div>

        <button type="submit" class="btn btn-reset">تحديث</button>

        <?php if (!empty($msg)): ?>
            <div class="message"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
