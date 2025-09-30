<?php

require_once 'db.php';
require_once 'class_Users.php';

$users = new Users($conn);
$msg = '';

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    if ($users->requestPasswordReset($email)) {
        session_start();
        $_SESSION['reset_email'] = $email;
        echo "<script>window.location.href='OTPVerification.php';</script>";
        exit;
    } else {
        $msg = "❌ البريد الإلكتروني غير مسجل.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>نسيت كلمة المرور - بنك الدم</title>
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

        .reset-card {
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

        .reset-card h3 {
            margin-bottom: 30px;
            font-weight: 700;
            color: #a7c7e7;
            text-shadow: 0 0 10px #3b82f6;
        }

        .form-control {
            background: rgba(255 255 255 / 0.15);
            border: 1px solid rgba(255 255 255 / 0.3);
            color: #e0e7ff;
            padding: 12px 15px;
            font-size: 1rem;
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

        .input-group-text {
            background: rgba(255 255 255 / 0.15);
            border: 1px solid rgba(255 255 255 / 0.3);
            color: #3b82f6;
            font-size: 1.2rem;
        }

        .btn-reset {
            background: #3b82f6;
            border: none;
            font-weight: 700;
            padding: 12px 0;
            font-size: 1.1rem;
            color: white;
            border-radius: 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px #3b82f6aa;
            width: 100%;
        }

        .btn-reset:hover,
        .btn-reset:focus {
            background: #2563eb;
            transform: scale(1.05);
            box-shadow: 0 6px 20px #2563ebcc;
        }

        .message {
            margin-top: 18px;
            color: #f87171;
            font-weight: 600;
            text-shadow: 0 0 6px #f87171aa;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="reset-card" role="main" aria-label="نموذج نسيت كلمة المرور">
    <h3>نسيت كلمة المرور</h3>

    <form method="post" novalidate onsubmit="return validateForm()" autocomplete="off">
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    placeholder="أدخل بريدك الإلكتروني"
                    aria-label="البريد الإلكتروني"
                    required
                />
            </div>
        </div>

        <button type="submit" class="btn btn-reset" aria-label="إرسال الرمز">إرسال الرمز</button>

        <?php if (!empty($msg)): ?>
            <div class="message" role="alert"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
    </form>
</div>

<script>
    function validateForm() {
        const email = document.getElementById('email').value.trim();
        if (!email) {
            alert('يرجى إدخال البريد الإلكتروني.');
            return false;
        }
        return true;
    }
</script>

</body>
</html>
