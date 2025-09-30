<?php
require_once 'db.php';
require_once 'class_Donor.php';

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'donor') {
    header("Location: unauthorized.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$donor = new Donor($conn);

$user_id = $_SESSION['user_id'];
$results = [];
$message = '';
$blood_type = '';
$sort_by = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_type = $_POST['blood_type'];
    $sort_by = $_POST['sort_by'] ?? '';
    $results = $donor->searchCompatibleDonors($user_id, $blood_type, $sort_by);
    $message = 'نتائج البحث:';
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>طلب تبرع بالدم</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body>

<?php include 'donor_layout.php'; ?>

<div class="container py-4">
  <h2 class="text-center text-danger mb-4">طلب تبرع بالدم</h2>

  <form method="POST" class="mb-4">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-bold">اختر فصيلة الدم:</label>
        <select name="blood_type" class="form-select" required>
          <option value="">-- اختر فصيلة الدم --</option>
          <?php
          $types = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
          foreach ($types as $type) {
            $selected = ($type == $blood_type) ? 'selected' : '';
            echo "<option value='$type' $selected>$type</option>";
          }
          ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label fw-bold">ترتيب حسب:</label>
        <select name="sort_by" class="form-select">
          <option value="">نوع الدم</option>
          <option value="distance" <?= ($sort_by == 'distance') ? 'selected' : '' ?>>حسب الأقرب (≤ 5 كم)</option>
        </select>
      </div>

      <div class="col-md-2">
        <button type="submit" class="btn btn-danger w-100">بحث</button>
      </div>

      <div class="col-md-2">
        <a href="dashboard.php" class="btn btn-secondary w-100">رجوع</a>
      </div>
    </div>
  </form>

  <?php if (!empty($results)): ?>
    <div class="alert alert-info text-center"><?= $message ?></div>
    <table class="table table-bordered table-striped">
      <thead class="table-danger">
        <tr>
          <th>الاسم</th>
          <th>فصيلة الدم</th>
          <th>نوع التوافق</th>
          <th>رقم الهاتف</th>
          <th>العنوان</th>
          <th>الإجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['blood_type']) ?></td>
            <td><?= $row['compatibility'] ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td>
              <a href="donor_make_request.php?target_id=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-danger">طلب</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="alert alert-warning text-center">لا توجد نتائج</div>
  <?php endif; ?>
</div>
</body>
</html>
