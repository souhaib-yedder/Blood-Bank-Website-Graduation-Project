<?php
require_once 'class_Users.php';
require_once 'db.php';
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new Users($conn);

    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $user->login($email, $password);

    if ($result) {
        if ($result['is_active'] == 0) {
            echo "<script>alert('غير مسموح لك بالدخول، حسابك غير مفعل.');</script>";
        } else {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['name'] = $result['name'];
            $_SESSION['role'] = $result['role'];

            switch ($result['role']) {
                case 'donor':
                    echo "<script>window.location.href='dashboard_doner.php';</script>";
                    exit;
                case 'staff':
                    echo "<script>window.location.href='dashboard_staff.php';</script>";
                    exit;
                case 'admin':
                    echo "<script>window.location.href='dashboard_admin.php';</script>";
                    exit;
                case 'hospital':
                    echo "<script>window.location.href='dashboard_hospital.php';</script>";
                    exit;
                default:
                    $message = "صلاحية غير معروفة.";
            }
        }
    } else {
        $message = "البريد الإلكتروني أو كلمة المرور غير صحيحة.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>تسجيل الدخول - بنك الدم</title>
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

        .login-card {
            background: rgba(255 255 255 / 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgb(40 62 81 / 0.5);
            padding: 40px 35px;
            width: 100%;
            max-width: 400px;
            animation: fadeInDown 1s ease forwards;
            position: relative;
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

        .logo {
            width: 90px;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 4px #60a5fa);
            animation: pulse 2s infinite ease-in-out alternate;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                filter: drop-shadow(0 0 5px #60a5fa);
            }
            100% {
                transform: scale(1.1);
                filter: drop-shadow(0 0 12px #3b82f6);
            }
        }

        h2 {
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

        .btn-login {
            background: #3b82f6;
            border: none;
            font-weight: 700;
            padding: 12px 0;
            font-size: 1.1rem;
            color: white;
            border-radius: 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px #3b82f6aa;
            margin-top: 10px;
        }

        .btn-login:hover,
        .btn-login:focus {
            background: #2563eb;
            transform: scale(1.05);
            box-shadow: 0 6px 20px #2563ebcc;
        }

        .message {
            margin-top: 18px;
            color: #f87171;
            font-weight: 600;
            text-align: center;
            text-shadow: 0 0 6px #f87171aa;
        }

        .forgot-link {
            color: #a7c7e7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }

        .forgot-link:hover {
            color: #3b82f6;
            text-decoration: underline;
        }

    </style>
</head>
<body>

<div class="login-card text-center" role="main" aria-label="نموذج تسجيل الدخول">
   
    <h2>تسجيل الدخول</h2>

    <form method="POST" novalidate onsubmit="return validateForm()" autocomplete="off">
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-control"
                    placeholder="البريد الإلكتروني"
                    aria-label="البريد الإلكتروني"
                    required
                />
            </div>
        </div>

        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="كلمة المرور"
                    aria-label="كلمة المرور"
                    required
                />
            </div>
        </div>

        <button type="submit" class="btn btn-login w-100" aria-label="تسجيل الدخول">دخول</button>

        <div class="mt-3 text-end">
            <a href="ForgotPassword.php" class="forgot-link" tabindex="0">نسيت كلمة المرور؟</a>
        </div>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message" role="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

<script>
    function validateForm() {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        if (!email || !password) {
            alert('يرجى ملء جميع الحقول.');
            return false;
        }
        return true;
    }
</script>

</body>
</html>
