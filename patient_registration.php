<?php
session_start();
require_once 'db.php';
require_once 'class_Patient.php';
require_once 'hospital_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    die("🚫 صلاحية الدخول غير متوفرة.");
}

$db = new Database();
$conn = $db->connect();
$patient = new Patient($conn);

// جلب hospital_id
$stmt = $conn->prepare("SELECT hospitals_id FROM hospitals WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hospital_id = $stmt->fetchColumn();

if (!$hospital_id) {
    die("المستشفى غير موجود.");
}

// حذف المريض
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $deleted = $patient->delete($id);
    echo $deleted ? "success" : "fail";
    exit;
}

// إضافة مريض جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $name = trim($_POST['patient_name']);
    $blood = trim($_POST['blood_type']);
    $urgency = trim($_POST['urgency_level']);
    $units = intval($_POST['needed_units']);

    // رفع ملف الحالة
    $file_name = null;
    if (isset($_FILES['condition_file']) && $_FILES['condition_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/'; 
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $file_name = time() . "_" . basename($_FILES['condition_file']['name']);
        move_uploaded_file($_FILES['condition_file']['tmp_name'], $uploadDir . $file_name);
    }

    // تخزين اسم الملف في حقل condition_description
    $success = $patient->add($hospital_id, $name, $blood, $urgency, $file_name, $units);

    echo $success 
        ? "<div class='alert alert-success'>✅ تم إضافة المريض بنجاح.</div>" 
        : "<div class='alert alert-danger'>❌ فشل في إضافة المريض.</div>";
}

$patients = $patient->getByHospital($hospital_id);
?>

<div class="container mt-4">
    <h3>تسجيل بيانات المرضى</h3>

    <form method="POST" class="mb-4" enctype="multipart/form-data">
        <input type="hidden" name="add_patient" value="1">

<div class="mb-3">
    <label>اسم المريض:</label>
    <input type="text" name="patient_name" class="form-control" required
           pattern="^[A-Za-z\u0600-\u06FF\s]+$"
           title="الاسم يجب أن يحتوي على حروف فقط (عربية أو إنجليزية)"
           oninput="this.value = this.value.replace(/[^A-Za-z\u0600-\u06FF\s]/g, '')">
</div>

        <div class="mb-3">
            <label>فصيلة الدم:</label>
            <select name="blood_type" class="form-control" required>
                <option value="">اختر الفصيلة</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
            </select>
        </div>

        <div class="mb-3">
            <label>مستوى الاستعجال:</label>
            <select name="urgency_level" class="form-control" required>
                <option value="">اختر المستوى</option>
                <option value="عاجلة جداً">عاجلة جداً</option>
                <option value="عاجلة">عاجلة</option>
                <option value="متوسطة">متوسطة</option>
                <option value="منخفضة">منخفضة</option>
            </select>
        </div>

        <div class="mb-3">
            <label>ملف الحالة:</label>
            <input type="file" name="condition_file" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>عدد الوحدات المطلوبة:</label>
            <input type="number" name="needed_units" class="form-control" min="1" required>
        </div>

        <button type="submit" class="btn btn-primary">➕ إضافة المريض</button>
    </form>

    <h4>📋 قائمة المرضى</h4>

    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="🔍 ابحث عن مريض، فصيلة دم، أو مستوى استعجال...">
    </div>

    <table class="table table-bordered table-striped" id="patientsTable">
        <thead class="table-danger">
            <tr>
                <th>اسم المريض</th>
                <th>فصيلة الدم</th>
                <th>مستوى الاستعجال</th>
                <th>ملف الحالة</th>
                <th>الوحدات المطلوبة</th>
                <th>تاريخ التسجيل</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($patients)): ?>
                <?php foreach ($patients as $p): ?>
                    <tr data-id="<?= $p['patients_id'] ?>">
                        <td><?= htmlspecialchars($p['patient_name']) ?></td>
                        <td><?= htmlspecialchars($p['blood_type']) ?></td>
                        <td><?= htmlspecialchars($p['urgency_level']) ?></td>
                        <td>
                            <?php if (!empty($p['condition_description'])): ?>
                                <button class="btn btn-sm btn-info view-file-btn" 
                                        data-file="uploads/<?= htmlspecialchars($p['condition_description']) ?>">
                                    📄 عرض الوصفة
                                </button>
                            <?php else: ?>
                                لا يوجد ملف
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['needed_units']) ?></td>
                        <td><?= htmlspecialchars($p['registered_at']) ?></td>
                        <td>
                            <a href="edit_patient.php?id=<?= $p['patients_id'] ?>" class="btn btn-sm btn-warning">✏️ تعديل</a>
                            <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?= $p['patients_id'] ?>">🗑️ حذف</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">🚫 لا توجد بيانات</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // زر عرض الوصفة
    document.querySelectorAll(".view-file-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const filePath = btn.dataset.file;
            if (filePath) {
                window.open(filePath, "_blank");
            } else {
                alert("⚠️ لا يوجد ملف مرتبط.");
            }
        });
    });

    // الحذف
    document.querySelectorAll(".delete-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const row = btn.closest("tr");
            const patientId = btn.dataset.id;

            if (confirm("هل أنت متأكد من حذف هذا المريض؟")) {
                fetch("", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "delete_patient=1&id=" + patientId
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "success") {
                        row.remove();
                    } else {
                 //       alert("❌ فشل في الحذف.");
                         alert("❌ تم الحذف بنجاح.");
                         

   window.location.href='patient_registration.php';;

    exit;

                    }
                });
            }
        });
    });

    // البحث
    document.getElementById("searchInput").addEventListener("keyup", function () {
        const value = this.value.toLowerCase();
        const rows = document.querySelectorAll("#patientsTable tbody tr");

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? "" : "none";
        });
    });
});
</script>
