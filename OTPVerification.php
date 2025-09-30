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
    $token = $_POST['token'];
    $email = $_SESSION['reset_email'];

    if ($users->verifyResetToken($email, $token)) {
        echo "<script>window.location.href='ResetPassword.php';</script>";
        exit;
    } else {
        $msg = "❌ الرمز غير صحيح. سيتم إعادتك لتسجيل الدخول.";
        session_destroy();
        header("refresh:3;url=login.php");
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>التحقق من الرمز - بنك الدم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal&display=swap');

 body {
    height: 100vh;
    margin: 0;
    font-family: 'Tajawal', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f0f4f8;
    /* تدرج ألوان مناسب لبنك الدم */
    background: linear-gradient(135deg, #ff4d4f 0%, #8b0000 50%, #1c1c1c 100%);
}

        .verify-card {
            background: rgba(255 255 255 / 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgb(40 62 81 / 0.5);
            padding: 40px 35px;
            width: 100%;
            max-width: 400px;
            animation: fadeInDown 1s ease forwards;
            position: relative;
            text-align: center;
        }

        @keyframes fadeInDown {
            0% {
                opacity: 0;
                transform: translateY(-30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .verify-card h3 {
            margin-bottom: 30px;
            font-weight: 700;
            color: #a7c7e7;
            text-shadow: 0 0 10px #3b82f6;
        }

        .form-control {
            background: rgba(255 255 255 / 0.15);
            border: 1px solid rgba(255 255 255 / 0.3);
            color: #e0e7ff;
            font-size: 22px;
            letter-spacing: 12px;
            text-align: center;
            padding: 10px 0;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: #cbd5e1;
            opacity: 1;
        }

        .form-control:focus {
            background: rgba(255 255 255 / 0.3);
            border-color: #3b82f6;
            box-shadow: 0 0 10px #3b82f6;
            color: white;
            outline: none;
        }

        .btn-verify {
            background: #3b82f6;
            border: none;
            font-weight: 700;
            padding: 12px 0;
            font-size: 18px;
            color: white;
            border-radius: 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px #3b82f6aa;
            width: 100%;
            margin-top: 15px;
        }

        .btn-verify:hover,
        .btn-verify:focus {
            background: #2563eb;
            transform: scale(1.05);
            box-shadow: 0 6px 20px #2563ebcc;
        }

        .message {
            margin-top: 20px;
            color: #f87171;
            font-weight: 600;
            text-shadow: 0 0 6px #f87171aa;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="verify-card" role="main" aria-label="نموذج التحقق من الرمز">
    <h3>التحقق من الرمز</h3>

    <form method="post" novalidate autocomplete="off" onsubmit="return validateToken()">
        <div class="mb-3">
            <input
                type="text"
                name="token"
                class="form-control"
                placeholder="أدخل الرمز المكون من 6 أرقام"
                maxlength="6"
                minlength="6"
                required
                pattern="\d{6}"
                title="ادخل 6 أرقام فقط"
                autofocus
                aria-label="حقل إدخال رمز التحقق"
            />
        </div>

        <button type="submit" class="btn btn-verify" aria-label="زر التحقق">تحقق</button>

        <?php if (!empty($msg)): ?>
            <div class="message" role="alert"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
    </form>
</div>

<script>
    function validateToken() {
        const tokenInput = document.querySelector('input[name="token"]');
        const token = tokenInput.value.trim();
        const regex = /^\d{6}$/;
        if (!regex.test(token)) {
            alert('يرجى إدخال رمز تحقق صحيح مكون من 6 أرقام.');
            tokenInput.focus();
            return false;
        }
        return true;
    }
</script>

</body>
</html>
