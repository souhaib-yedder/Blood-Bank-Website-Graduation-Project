<?php
require_once 'db.php';
require_once 'Statistics.php';
$db = new Database();
$conn = $db->connect();
$stats = new Statistics($conn);

// جلب الإحصائيات
$totalTests = $stats->countRecords('blood_tests');
$totalHospitals = $stats->countRecords('hospitals');
$totalDonors = $stats->countRecords('donors');
$totalStaff = $stats->countRecords('staff');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بنك الدم الليبي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-heart text-white me-2"></i>
                بنك الدم الليبي
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#contraindications">موانع التبرع</a></li>
                    <li class="nav-item"><a class="nav-link" href="#why-donate">لماذا أتبرع؟</a></li>
                    <li class="nav-item"><a class="nav-link" href="#benefits">فوائد التبرع</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about-us">من نحن</a></li>
                    <li class="nav-item"><a class="nav-link" href="#steps">خطوات التبرع</a></li>
                    <li class="nav-item"><a class="nav-link" href="#disallowed-cases">خرافات حول التبرع بالدم</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">أسئلة شائعة</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_donor.php">تسجيل كمتبرع</a></li>
                    <li class="nav-item"><a class="nav-link" href="register_hospital.php">تسجيل كمستشفى</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">تسجيل الدخول</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact-us">التواصل معنا</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">خدماتنا</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-image">
            <img src="images/img1.jpg" alt="بنك الدم" class="img-fluid w-100">
            <div class="hero-overlay">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 text-center">
                            <h1 class="display-4 fw-bold text-white mb-4">
                                مرحباً بكم في بنك الدم الليبي – حيث تنقذ قطرة دم حياة
                            </h1>
                            <p class="lead text-white">
                                نوفر خدمات نقل وتخزين الدم وفق أعلى معايير السلامة والتقنية الحديثة
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Image Gallery -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-2">
                    <img src="images/img10.jpg" alt="صورة 1" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img20.jpg" alt="صورة 2" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img30.jpg" alt="صورة 3" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img40.jpg" alt="صورة 4" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img50.jpg" alt="صورة 5" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img66.jpg" alt="صورة 6" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img70.png" alt="صورة 7" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img80.png" alt="صورة 8" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img90.png" alt="صورة 9" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img100.jpg" alt="صورة 10" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img110.png" alt="صورة 11" class="img-fluid rounded shadow">
                </div>
                <div class="col-md-2">
                    <img src="images/img111.png" alt="صورة 12" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about-us" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="bg-white p-5 rounded shadow">
                        <h2 class="text-danger mb-4">من نحن</h2>
                        <p class="mb-3">
                            بنك الدم الليبي هو مؤسسة طبية متخصصة تهدف إلى توفير خدمات نقل وتخزين الدم بأعلى معايير الجودة والسلامة، حيث نعمل على ضمان توفر الدم ومشتقاته للمرضى المحتاجين في جميع أنحاء ليبيا.
                        </p>
                        <p class="mb-3">
                            نحن نلتزم بتطبيق أحدث التقنيات والبروتوكولات الطبية العالمية في عمليات جمع وفحص وتخزين الدم، مع الحرص على سلامة المتبرعين والمرضى على حد سواء.
                        </p>
                        <p class="mb-0">
                            رؤيتنا هي أن نكون المرجع الأول في مجال خدمات الدم في ليبيا، ونساهم في إنقاذ الأرواح من خلال توفير شبكة متكاملة من الخدمات الطبية المتخصصة.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <img src="images/img2.jpg" alt="من نحن" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Why Donate Section -->
    <section id="why-donate" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="bg-white p-5 rounded shadow">
                        <h2 class="text-danger mb-4">لماذا أتبرع؟</h2>
                        <p class="mb-3">
                            التبرع بالدم هو عمل إنساني نبيل يساهم في إنقاذ الأرواح وتقديم الأمل للمرضى المحتاجين. كل تبرع واحد يمكن أن ينقذ حياة ثلاثة أشخاص.
                        </p>
                        <p class="mb-3">
                            عندما تتبرع بالدم، فإنك تساهم في علاج المرضى الذين يعانون من حوادث، عمليات جراحية، أمراض الدم، والسرطان. تبرعك يعني الفرق بين الحياة والموت للكثيرين.
                        </p>
                        <p class="mb-0">
                            بالإضافة إلى الأثر الإيجابي على المجتمع، فإن التبرع بالدم له فوائد صحية للمتبرع نفسه، حيث يساعد على تجديد خلايا الدم وتحسين الدورة الدموية.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <img src="images/img5.jpg" alt="لماذا أتبرع" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    
    <!-- Contraindications Section -->
    <section id="contraindications" class="py-5">
        <div class="container">
            <h2 class="text-center text-danger mb-5">موانع التبرع</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-thermometer-half fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">الحمى والمرض</h5>
                            <p class="card-text">عدم التبرع في حالة الإصابة بالحمى أو أي مرض معدي</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-pills fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">تناول الأدوية</h5>
                            <p class="card-text">بعض الأدوية قد تمنع التبرع مؤقتاً أو دائماً</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-baby fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">الحمل والرضاعة</h5>
                            <p class="card-text">منع التبرع أثناء فترة الحمل والرضاعة الطبيعية</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-weight fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">الوزن المنخفض</h5>
                            <p class="card-text">يجب أن يكون الوزن أكثر من 50 كيلوغرام</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-heart-broken fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">أمراض القلب</h5>
                            <p class="card-text">الإصابة بأمراض القلب المزمنة تمنع التبرع</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-syringe fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">تعاطي المخدرات</h5>
                            <p class="card-text">تعاطي المخدرات أو استخدام الإبر الملوثة</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center text-danger mb-5">فوائد التبرع بالدم</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-heart fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">تحسين صحة القلب</h5>
                            <p class="card-text">التبرع المنتظم يساعد في تقليل مخاطر أمراض القلب والأوعية الدموية</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-sync-alt fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">تجديد خلايا الدم</h5>
                            <p class="card-text">يحفز الجسم على إنتاج خلايا دم جديدة وصحية</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-weight-hanging fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">حرق السعرات</h5>
                            <p class="card-text">التبرع الواحد يحرق حوالي 650 سعرة حرارية</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-vial fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">فحص مجاني</h5>
                            <p class="card-text">الحصول على فحص طبي شامل مجاني مع كل تبرع</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-smile fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">الشعور بالسعادة</h5>
                            <p class="card-text">الشعور بالرضا والسعادة لمساعدة الآخرين</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-shield-alt fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">تقوية المناعة</h5>
                            <p class="card-text">يساعد في تقوية جهاز المناعة الطبيعي</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="registration-card">
                        <img src="images/img3.jpg" alt="تسجيل متبرع" class="img-fluid">
                        <div class="registration-overlay">
                            <a href="register_donor.php" class="btn btn-danger btn-lg">تسجيل كمتبرع</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="registration-card">
                        <img src="images/img4.jpg" alt="تسجيل مستشفى" class="img-fluid">
                        <div class="registration-overlay">
                            <a href="register_hospital.php" class="btn btn-danger btn-lg">تسجيل كمستشفى</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Steps Section -->
    <section id="steps" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center text-danger mb-5">خطوات التبرع</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow text-center">
                        <div class="card-body">
                            <div class="step-number">1</div>
                            <h5 class="card-title">التسجيل والفحص</h5>
                            <p class="card-text">ملء استمارة التسجيل والخضوع للفحص الطبي الأولي</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow text-center">
                        <div class="card-body">
                            <div class="step-number">2</div>
                            <h5 class="card-title">عملية التبرع</h5>
                            <p class="card-text">التبرع بالدم في بيئة آمنة ومعقمة تحت إشراف طبي</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow text-center">
                        <div class="card-body">
                            <div class="step-number">3</div>
                            <h5 class="card-title">الراحة والمتابعة</h5>
                            <p class="card-text">فترة راحة مع تناول المرطبات ومتابعة الحالة الصحية</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Disallowed Cases Section -->
    <section id="disallowed-cases" class="py-5">
        <div class="container">
            <h2 class="text-center text-danger mb-5">خرافات عن التبرع </h2>
            <div class="text-center">
                <img src="images/img6.jpg" alt="الحالات التي لا يسمح بها للتبرع" class="img-fluid rounded shadow">
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center text-danger mb-5">أسئلة شائعة</h2>
            <div class="row">
                <div class="col-lg-6">
                    <div class="faq-questions">
                        <div class="faq-item" data-question="1">
                            <h5>هل التبرع بالدم آمن؟</h5>
                        </div>
                        <div class="faq-item" data-question="2">
                            <h5>كم مرة يمكنني التبرع في السنة؟</h5>
                        </div>
                        <div class="faq-item" data-question="3">
                            <h5>ما هي الشروط المطلوبة للتبرع؟</h5>
                        </div>
                        <div class="faq-item" data-question="4">
                            <h5>هل يؤثر التبرع على صحتي؟</h5>
                        </div>
                        <div class="faq-item" data-question="5">
                            <h5>كم من الوقت تستغرق عملية التبرع؟</h5>
                        </div>
                        <div class="faq-item" data-question="6">
                            <h5>ماذا أفعل بعد التبرع؟</h5>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="faq-answer" id="faq-answer">
                        <p class="text-muted">اختر سؤالاً لعرض الإجابة</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <h2 class="text-center text-danger mb-5">خدماتنا</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-tint fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">جمع الدم</h5>
                            <p class="card-text">خدمات جمع الدم من المتبرعين وفق أعلى معايير السلامة</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-vials fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">فحص الدم</h5>
                            <p class="card-text">فحص شامل للدم للتأكد من سلامته وخلوه من الأمراض</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-snowflake fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">تخزين الدم</h5>
                            <p class="card-text">تخزين الدم في ظروف مثالية للحفاظ على جودته</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-truck fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">نقل الدم</h5>
                            <p class="card-text">خدمات نقل الدم للمستشفيات والمراكز الطبية</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">حملات التبرع</h5>
                            <p class="card-text">تنظيم حملات التبرع في المجتمعات والمؤسسات</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow">
                        <div class="card-body text-center">
                            <i class="fas fa-phone fa-3x text-danger mb-3"></i>
                            <h5 class="card-title">الدعم الطارئ</h5>
                            <p class="card-text">خدمة الطوارئ على مدار الساعة لتوفير الدم</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="statistics" class="py-5 bg-danger text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold counter" data-target="<?php echo $totalTests; ?>">0</h3>
                        <p>التحاليل المنجزة</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold counter" data-target="<?php echo $totalHospitals; ?>">0</h3>
                        <p>المستشفيات المسجلة</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold counter" data-target="<?php echo $totalDonors; ?>">0</h3>
                        <p>المتبرعين</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <h3 class="display-4 fw-bold counter" data-target="<?php echo $totalStaff; ?>">0</h3>
                        <p>أعضاء فريق العمل</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact-us" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center text-danger mb-5">معلومات التواصل</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row g-4">
                        <div class="col-md-4 text-center">
                            <i class="fas fa-phone fa-3x text-danger mb-3"></i>
                            <h5>الهاتف</h5>
                            <p>+218-21-123-4567</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-envelope fa-3x text-danger mb-3"></i>
                            <h5>البريد الإلكتروني</h5>
                            <p>info@bloodbank.ly</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-map-marker-alt fa-3x text-danger mb-3"></i>
                            <h5>العنوان</h5>
                            <p>ليبيا - طرابلس</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>بنك الدم الليبي</h5>
                    <p>نعمل على إنقاذ الأرواح من خلال توفير خدمات الدم الآمنة والموثوقة</p>
                </div>
                <div class="col-md-6">
                    <h5>التواصل معنا</h5>
                    <p><i class="fas fa-phone me-2"></i> +218-21-123-4567</p>
                    <p><i class="fas fa-envelope me-2"></i> info@bloodbank.ly</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> ليبيا - طرابلس</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2025 بنك الدم الليبي. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    
    <!-- Animated Counter Script -->
    <script>
        $(document ).ready(function() {
            const counters = document.querySelectorAll('.counter');
            const speed = 200; // يمكنك تغيير هذه القيمة لتسريع أو إبطاء العداد

            const animateCounters = (entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target;
                        const updateCount = () => {
                            const target = +counter.getAttribute('data-target');
                            const count = +counter.innerText;
                            const inc = target / speed;

                            if (count < target) {
                                counter.innerText = Math.ceil(count + inc);
                                setTimeout(updateCount, 15); // يمكنك تعديل هذه القيمة لتغيير سلاسة الحركة
                            } else {
                                counter.innerText = target;
                            }
                        };
                        updateCount();
                        observer.unobserve(counter); // إيقاف المراقبة بعد بدء العداد
                    }
                });
            };

            const observer = new IntersectionObserver(animateCounters, {
                root: null,
                threshold: 0.5 // يبدأ العداد عندما يكون 50% من العنصر مرئياً
            });

            counters.forEach(counter => {
                observer.observe(counter);
            });
        });
    </script>

    <script src="script.js"></script>
</body>
</html>
