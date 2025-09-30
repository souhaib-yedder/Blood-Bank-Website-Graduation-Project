<?php
session_start();
require_once 'hospital_layout.php';
require_once 'db.php';
require_once 'class_Hospital.php';
require_once 'class_FileUploader.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$hospital = new Hospital($conn);
$fileUploader = new FileUploader();

$user_id = $_SESSION['user_id'];
$hospitalData = $hospital->getHospitalByUserId($user_id);
$hospitals_id = $hospitalData['hospitals_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $files = [];

    foreach (['letter_file', 'license_file', 'tax_file', 'id_file'] as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploaded = $fileUploader->upload($_FILES[$field], $field . "_");
            $files[$field] = $uploaded ?: $hospitalData[$field];
        } else {
            $files[$field] = $hospitalData[$field];
        }
    }

    if ($hospital->updateHospitalFiles($hospitals_id, $files)) {
        echo "<script>alert('✅ تم تحديث الملفات بنجاح'); window.location.href='hospital_documents.php';</script>";
        exit();
    } else {
        echo "<script>alert('❌ حدث خطأ أثناء تحديث الملفات');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>الوثائق الرسمية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* تنسيق القائمة الجانبية */
        body, .sidebar, .sidebar a, .sidebar h5 {
            font-family: 'Cairo', sans-serif;
        }

        /* تنسيق الصفحة */
        #page-content-wrapper {
            margin-right: 280px;
            padding: 30px;
        }

        .preview-img {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .file-section {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>


<!-- ✅ محتوى الصفحة -->
<div >
        <h2 class="text-center mb-4">الوثائق الرسمية للمستشفى</h2>
        <form method="POST" enctype="multipart/form-data" novalidate>
            <?php
            $labels = [
                'letter_file' => 'خطاب المستشفى',
                'license_file' => 'الرخصة الطبية',
                'tax_file' => 'الملف الضريبي',
                'id_file' => 'بطاقة الهوية'
            ];

            foreach ($labels as $key => $label):
                $filename = $hospitalData[$key] ?? '';
                $filepath = __DIR__ . '/uploads/hospitals/' . $filename;
                $urlpath = '/blood_bank02/uploads/hospitals/' . $filename;
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            ?>
                <div class="file-section">
                    <label class="form-label fw-bold"><?= htmlspecialchars($label) ?>:</label><br>

                    <?php if (!empty($filename) && file_exists($filepath)): ?>
                        <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?= htmlspecialchars($urlpath) ?>" alt="<?= htmlspecialchars($label) ?>" class="preview-img" />
                            <p class="text-center text-muted mt-1">الصورة الحالية لـ <?= htmlspecialchars($label) ?></p>
                        <?php endif; ?>
                        <div class="text-center mb-2">
                            <a href="<?= htmlspecialchars($urlpath) ?>" target="_blank" class="btn btn-outline-primary btn-sm">عرض / تحميل الملف الحالي</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">لا يوجد ملف حالي</p>
                    <?php endif; ?>

                    <input type="file" name="<?= htmlspecialchars($key) ?>" class="form-control mt-2" accept="image/*,application/pdf" />
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5">تحديث الملفات</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
