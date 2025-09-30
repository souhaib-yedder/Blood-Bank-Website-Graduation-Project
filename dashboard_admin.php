<?php
session_start();
require_once 'admin_layout.php';
require_once 'db.php';
require_once 'Statistics.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}





// التحقق من نجاح الاتصال وإنشاء الكائن
if (!isset($conn)) {
    die("<h1>خطأ في الاتصال بقاعدة البيانات. يرجى التحقق من ملف 'db_connection.php'</h1>");
}
$stats = new Statistics($conn);

// --- جلب جميع البيانات اللازمة للصفحة ---

// 1. بيانات لوحة التحكم العامة
$totalDonors = $stats->getTotalDonors();
$totalStaff = $stats->getTotalStaff();
$totalHospitals = $stats->getTotalHospitals();
$totalCampaigns = $stats->getTotalCampaigns();
$totalBloodStock = $stats->getBloodStockCount();

// 2. بيانات لوحة المتبرعين
$totalDonorRequests = $stats->getTotalDonorRequests();
$pendingDonorRequests = $stats->getPendingDonorRequests();
$latestDonors = $stats->getLatestRecords('donors', 'donors_id');
$donationsByMonth = $stats->getDonationsByMonth();
$donationMonths = json_encode(array_column($donationsByMonth, 'month'));
$donationCounts = json_encode(array_column($donationsByMonth, 'count'));

// 3. بيانات لوحة الموظفين
$totalReports = $stats->getTotalReports();
$highPriorityReports = $stats->getHighPriorityReports();
$latestStaff = $stats->getLatestRecords('staff', 'hiring_date');

// 4. بيانات لوحة المستشفيات
$totalHospitalRequests = $stats->getTotalHospitalRequests();
$pendingHospitalRequests = $stats->getPendingHospitalRequests();
$latestHospitals = $stats->getLatestRecords('hospitals', 'hospitals_id');

// 5. بيانات لوحة مخزون الدم
$bloodStockByType = $stats->getBloodStockByType();
$bloodTypes = json_encode(array_column($bloodStockByType, 'blood_type'));
$bloodQuantities = json_encode(array_column($bloodStockByType, 'total'));
$latestBloodStock = $stats->getLatestRecords('blood_stock', 'receipt_date');

// 6. بيانات لوحة الحملات
$activeCampaigns = $stats->getActiveCampaigns();
$pendingCampaigns = $stats->getPendingCampaigns();
$latestCampaigns = $stats->getLatestRecords('donation_campaigns', 'campaign_date');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .main-header { background-color: #fff; padding: 1rem 1.5rem; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.07 ); margin-bottom: 1.5rem; }
        .notification-bell { font-size: 1.5rem; color: #6c757d; text-decoration: none; }
        .stat-card { background-color: #fff; border: none; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; height: 100%; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .stat-card .card-body { padding: 1.5rem; }
        .stat-card .stat-icon { font-size: 3rem; opacity: 0.5; color: #0d6efd; }
        .nav-pills .nav-link { color: #495057; font-weight: 600; border-radius: 0.75rem; margin: 0 5px; padding: 0.75rem 1.25rem; }
        .nav-pills .nav-link.active { background-color: #0d6efd; color: white; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.4); }
        .dashboard-section { display: none; animation: fadeIn 0.5s; }
        .dashboard-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .table-responsive { border-radius: 0.5rem; overflow: hidden; }
        .card-table { border-radius: 1rem; }
    </style>
</head>
<body>

<div class="container-fluid py-3">

    <!-- الهيدر -->


    <!-- أزرار التنقل -->
    <ul class="nav nav-pills mb-4 justify-content-center" id="pills-tab">
        <li class="nav-item"><button class="nav-link active" data-bs-target="#main-dashboard">الرئيسية</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#donors-dashboard">المتبرعين</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#staff-dashboard">الموظفين</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#hospitals-dashboard">المستشفيات</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#stock-dashboard">مخزون الدم</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-target="#campaigns-dashboard">الحملات</button></li>
    </ul>

    <!-- ======================= لوحة التحكم الرئيسية (نظرة عامة) ======================= -->
    <div id="main-dashboard" class="dashboard-section active">
        <div class="row g-4">
            <div class="col-lg col-md-6"><div class="stat-card"><div class="card-body d-flex justify-content-between align-items-center"><div><h5>المتبرعين</h5><h2 class="fw-bold"><?= htmlspecialchars($totalDonors) ?></h2></div><div class="stat-icon">🩸</div></div></div></div>
            <div class="col-lg col-md-6"><div class="stat-card"><div class="card-body d-flex justify-content-between align-items-center"><div><h5>الموظفين</h5><h2 class="fw-bold"><?= htmlspecialchars($totalStaff) ?></h2></div><div class="stat-icon">👥</div></div></div></div>
            <div class="col-lg col-md-6"><div class="stat-card"><div class="card-body d-flex justify-content-between align-items-center"><div><h5>المستشفيات</h5><h2 class="fw-bold"><?= htmlspecialchars($totalHospitals) ?></h2></div><div class="stat-icon">🏥</div></div></div></div>
            <div class="col-lg col-md-6"><div class="stat-card"><div class="card-body d-flex justify-content-between align-items-center"><div><h5>مخزون الدم</h5><h2 class="fw-bold"><?= htmlspecialchars($totalBloodStock) ?></h2></div><div class="stat-icon">📦</div></div></div></div>
            <div class="col-lg col-md-6"><div class="stat-card"><div class="card-body d-flex justify-content-between align-items-center"><div><h5>الحملات</h5><h2 class="fw-bold"><?= htmlspecialchars($totalCampaigns) ?></h2></div><div class="stat-icon">📢</div></div></div></div>
        </div>
    </div>

    <!-- ======================= لوحة تحكم المتبرعين ======================= -->
    <div id="donors-dashboard" class="dashboard-section">
        <div class="row g-4">
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي الطلبات</h5><h2 class="fw-bold"><?= htmlspecialchars($totalDonorRequests) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>طلبات قيد الانتظار</h5><h2 class="fw-bold"><?= htmlspecialchars($pendingDonorRequests) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي المتبرعين</h5><h2 class="fw-bold"><?= htmlspecialchars($totalDonors) ?></h2></div></div></div>
            <div class="col-lg-7"><div class="card card-table"><div class="card-header"><h5>التبرعات شهريًا</h5></div><div class="card-body"><canvas id="donationsChart"></canvas></div></div></div>
            <div class="col-lg-5"><div class="card card-table"><div class="card-header"><h5>آخر 10 متبرعين</h5></div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>فصيلة الدم</th><th>الهاتف</th></tr></thead><tbody>
                <?php foreach($latestDonors as $item): ?><tr><td><?= htmlspecialchars($item['donors_id']) ?></td><td><?= htmlspecialchars($item['blood_type']) ?></td><td><?= htmlspecialchars($item['phone']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div></div></div>
        </div>
    </div>

    <!-- ======================= لوحة تحكم الموظفين ======================= -->
    <div id="staff-dashboard" class="dashboard-section">
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي الموظفين</h5><h2 class="fw-bold"><?= htmlspecialchars($totalStaff) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي التقارير</h5><h2 class="fw-bold"><?= htmlspecialchars($totalReports) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>تقارير عاجلة</h5><h2 class="fw-bold"><?= htmlspecialchars($highPriorityReports) ?></h2></div></div></div>
        </div>
        <div class="card card-table"><div class="card-header"><h5>آخر 10 موظفين</h5></div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>القسم</th><th>الهاتف</th><th>تاريخ التوظيف</th></tr></thead><tbody>
            <?php foreach($latestStaff as $item): ?><tr><td><?= htmlspecialchars($item['staff_id']) ?></td><td><?= htmlspecialchars($item['department']) ?></td><td><?= htmlspecialchars($item['phone']) ?></td><td><?= htmlspecialchars($item['hiring_date']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>

    <!-- ======================= لوحة تحكم المستشفيات ======================= -->
    <div id="hospitals-dashboard" class="dashboard-section">
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي المستشفيات</h5><h2 class="fw-bold"><?= htmlspecialchars($totalHospitals) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي الطلبات</h5><h2 class="fw-bold"><?= htmlspecialchars($totalHospitalRequests) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>طلبات قيد الانتظار</h5><h2 class="fw-bold"><?= htmlspecialchars($pendingHospitalRequests) ?></h2></div></div></div>
        </div>
        <div class="card card-table"><div class="card-header"><h5>آخر 10 مستشفيات</h5></div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>اسم المستشفى</th><th>الهاتف</th><th>الحالة</th></tr></thead><tbody>
            <?php foreach($latestHospitals as $item): ?><tr><td><?= htmlspecialchars($item['hospitals_id']) ?></td><td><?= htmlspecialchars($item['hospital_name']) ?></td><td><?= htmlspecialchars($item['phone']) ?></td><td><span class='badge bg-success'><?= htmlspecialchars($item['status']) ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>

    <!-- ======================= لوحة تحكم مخزون الدم ======================= -->
    <div id="stock-dashboard" class="dashboard-section">
        <div class="row g-4">
            <div class="col-lg-7"><div class="card card-table"><div class="card-header"><h5>آخر الإضافات للمخزون</h5></div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>فصيلة</th><th>مكون</th><th>كمية</th><th>تاريخ</th></tr></thead><tbody>
                <?php foreach($latestBloodStock as $item): ?><tr><td><?= htmlspecialchars($item['blood_type']) ?></td><td><?= htmlspecialchars($item['blood_component']) ?></td><td><?= htmlspecialchars($item['quantity']) ?></td><td><?= htmlspecialchars($item['receipt_date']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div></div></div>
            <div class="col-lg-5"><div class="card card-table"><div class="card-header"><h5>توزيع المخزون حسب الفصيلة</h5></div><div class="card-body"><canvas id="stockChart"></canvas></div></div></div>
        </div>
    </div>

    <!-- ======================= لوحة تحكم الحملات ======================= -->
    <div id="campaigns-dashboard" class="dashboard-section">
        <div class="row g-4 mb-4">
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>إجمالي الحملات</h5><h2 class="fw-bold"><?= htmlspecialchars($totalCampaigns) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>حملات نشطة</h5><h2 class="fw-bold"><?= htmlspecialchars($activeCampaigns) ?></h2></div></div></div>
            <div class="col-md-4"><div class="stat-card"><div class="card-body"><h5>حملات قيد الانتظار</h5><h2 class="fw-bold"><?= htmlspecialchars($pendingCampaigns) ?></h2></div></div></div>
        </div>
        <div class="card card-table"><div class="card-header"><h5>آخر 10 حملات</h5></div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>#</th><th>اسم الحملة</th><th>التاريخ</th><th>الموقع</th><th>الحالة</th></tr></thead><tbody>
            <?php foreach($latestCampaigns as $item): ?><tr><td><?= htmlspecialchars($item['donation_campaigns_id']) ?></td><td><?= htmlspecialchars($item['campaign_name']) ?></td><td><?= htmlspecialchars($item['campaign_date']) ?></td><td><?= htmlspecialchars($item['location']) ?></td><td><span class='badge bg-info'><?= htmlspecialchars($item['status']) ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pills = document.querySelectorAll('#pills-tab .nav-link');
    const sections = document.querySelectorAll('.dashboard-section');
    let charts = {}; // لتخزين الرسوم البيانية وتجنب إعادة إنشائها

    function setActiveTab(targetId) {
        sections.forEach(section => {
            section.classList.remove('active');
        });
        pills.forEach(p => {
            p.classList.remove('active');
        });

        const targetSection = document.querySelector(targetId);
        const targetPill = document.querySelector(`.nav-link[data-bs-target="${targetId}"]`);

        if (targetSection) targetSection.classList.add('active');
        if (targetPill) targetPill.classList.add('active');

        // إنشاء الرسم البياني فقط عند عرض قسمه لأول مرة
        if (targetId === '#donors-dashboard' && !charts.donations) {
            charts.donations = new Chart(document.getElementById('donationsChart'), {
                type: 'bar',
                data: { labels: <?= $donationMonths ?>, datasets: [{ label: 'عدد التبرعات', data: <?= $donationCounts ?>, backgroundColor: 'rgba(13, 110, 253, 0.7)' }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        } else if (targetId === '#stock-dashboard' && !charts.stock) {
            charts.stock = new Chart(document.getElementById('stockChart'), {
                type: 'doughnut',
                data: { labels: <?= $bloodTypes ?>, datasets: [{ data: <?= $bloodQuantities ?>, backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
            });
        }
    }

    pills.forEach(pill => {
        pill.addEventListener('click', function (event) {
            event.preventDefault();
            const targetId = this.getAttribute('data-bs-target');
            setActiveTab(targetId);
        });
    });

    // تفعيل القسم الرئيسي عند تحميل الصفحة
    setActiveTab('#main-dashboard');
});
</script>

</body>
</html>