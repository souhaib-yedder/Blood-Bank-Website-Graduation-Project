<?php
session_start();

// التحقق إن كان المستخدم مسجلاً الدخول
if (!isset($_SESSION['role'])) {


      echo "<script>window.location.href='login.php';</script>";

    exit;
}

// الحصول على الدور قبل التدمير (للاستخدام الاختياري بعد الحذف)
$role = $_SESSION['role'];

// تدمير الجلسة
session_unset();
session_destroy();

// إعادة التوجيه بناءً على الدور
switch ($role) {
    case 'admin':
     
            echo "<script>window.location.href='login.php';</script>";

    exit;
    case 'staff':
  
              echo "<script>window.location.href='login.php';</script>";

    exit;

    case 'hospital':
      

              echo "<script>window.location.href='login.php';</script>";

    exit;

    case 'donor':
   

             echo "<script>window.location.href='login.php';</script>";

    exit;

    default:
      
              echo "<script>window.location.href='login.php';</script>";

    exit;
}

exit();
?>
