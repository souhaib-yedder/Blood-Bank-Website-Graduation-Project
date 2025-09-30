
<?php
class BloodTest {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getRequestWithDonorById($request_id) {
    $sql = "SELECT r.*, d.donor_name, d.blood_type, d.donors_id
            FROM blood_bank_requests r
            JOIN donors d ON r.donors_id = d.donors_id
            WHERE r.request_id = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$request_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function insertBloodTest($data) {
    $sql = "INSERT INTO blood_tests (
        donors_id, test_date, rh_factor, crossmatch_result, hiv, hbv, hcv, syphilis, htlv,
        antibody_screening, rbc_count, wbc_count, platelet_count, hemoglobin_level, blood_condition
    ) VALUES (
        :donors_id, :test_date, :rh_factor, :crossmatch_result, :hiv, :hbv, :hcv, :syphilis, :htlv,
        :antibody_screening, :rbc_count, :wbc_count, :platelet_count, :hemoglobin_level, :blood_condition
    )";

    $stmt = $this->conn->prepare($sql);

    $success = $stmt->execute([
        ':donors_id' => $data['donors_id'],
        ':test_date' => $data['test_date'],
        ':rh_factor' => $data['rh_factor'],
        ':crossmatch_result' => $data['crossmatch_result'],
        ':hiv' => $data['hiv'],
        ':hbv' => $data['hbv'],
        ':hcv' => $data['hcv'],
        ':syphilis' => $data['syphilis'],
        ':htlv' => $data['htlv'],
        ':antibody_screening' => $data['antibody_screening'],
        ':rbc_count' => $data['rbc_count'],
        ':wbc_count' => $data['wbc_count'],
        ':platelet_count' => $data['platelet_count'],
        ':hemoglobin_level' => $data['hemoglobin_level'],
        ':blood_condition' => $data['blood_condition'],
    ]);

    if ($success) {
        $bloodTestId = $this->conn->lastInsertId();

        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // ⬅️ إشعار المتبرع
        $notification->addNotification(
            $data['donors_id'],
            'donor',
            'تحليلك جاهز',
            $bloodTestId,
            'blood_test'
        );

        // ⬅️ جلب اسم المتبرع لإرسال إشعار للموظفين
        $query = $this->conn->prepare("
            SELECT u.name
            FROM donors d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.donors_id = ?
        ");
        $query->execute([$data['donors_id']]);
        $donorInfo = $query->fetch(PDO::FETCH_ASSOC);
        $donorName = $donorInfo ? $donorInfo['name'] : 'مستخدم مجهول';

        $staffMessage = "🧪 تم إنشاء تحليل للمتبرع {$donorName}";

        // ⬅️ جلب كل الموظفين والإداريين باستثناء من قام بإنشاء التحليل (إذا عرفت الـ staff_id)
        $staffStmt = $this->conn->prepare("SELECT user_id FROM users WHERE role IN ('staff', 'admin')");
        $staffStmt->execute();
        $staffMembers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($staffMembers as $staff) {
            $notification->addNotification(
                $staff['user_id'],
                'staff',
                $staffMessage,
                $bloodTestId,
                'blood_test'
            );
        }
    }

    return $success;
}


public function updateBloodStockBasedOnRequest($request_id) {
    // جلب معلومات الطلب
    $stmt = $this->conn->prepare("SELECT * FROM blood_bank_requests WHERE request_id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request || $request['status'] !== 'completed') {
        return;
    }

    // جلب فصيلة دم المتبرع
    $stmt = $this->conn->prepare("SELECT blood_type FROM donors WHERE donors_id = ?");
    $stmt->execute([$request['donors_id']]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        return;
    }

    $donor_blood_type = trim($donor['blood_type']);
    $component = $request['blood_component'];
    $receipt_date = date('Y-m-d');
    $source = 'Local';
    $condition = 'Valid';

    // حساب تاريخ الانتهاء حسب نوع المكون
    $days_to_add = 35; // افتراضي لـ Whole Blood
    if ($component === 'Red Blood Cells') {
        $days_to_add = 42;
    } elseif ($component === 'Platelets') {
        $days_to_add = 5;
    } elseif ($component === 'Plasma') {
        $days_to_add = 365;
    }

    $expiration_date = date('Y-m-d', strtotime("+$days_to_add days"));

    if ($request['request_type'] === 'donor') {
        // خصم وحدة من المخزون المطلوب
        $stmt = $this->conn->prepare("SELECT * FROM blood_stock WHERE blood_type = ? AND blood_component = ?");
        $stmt->execute([$request['blood_type'], $component]);
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stock || $stock['quantity'] < 1) {
            return;
        }

        $stmt = $this->conn->prepare("UPDATE blood_stock SET quantity = quantity - 1 WHERE blood_type = ? AND blood_component = ?");
        $stmt->execute([$request['blood_type'], $component]);
    }

    // سواء كان الطلب من متبرع أو بنك، يتم إضافة وحدة جديدة حسب نوع الطلب
    if (in_array($request['request_type'], ['donor', 'bank'])) {
        $stmt = $this->conn->prepare("
            INSERT INTO blood_stock (blood_type, blood_component, quantity, receipt_date, source, blood_condition, expiration_date)
            VALUES (?, ?, 1, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $donor_blood_type,  // فصيلة دم المتبرع
            $component,         // نوع الدم حسب الطلب
            $receipt_date,
            $source,
            $condition,
            $expiration_date
        ]);
    }
}


     // عرض كل التحاليل مع اسم المتبرع وفصيلة الدم
public function getAllTestsWithDonorInfo() {
    $sql = "SELECT 
                bt.blood_tests_id,
                bt.donors_id,
                bt.test_date,
                bt.rh_factor,
                bt.crossmatch_result,
                bt.hiv,
                bt.hbv,
                bt.hcv,
                bt.syphilis,
                bt.htlv,
                bt.antibody_screening,
                bt.rbc_count,
                bt.wbc_count,
                bt.platelet_count,
                bt.hemoglobin_level,
                bt.blood_condition,
                d.blood_type,
                u.name AS donor_name
            FROM blood_tests bt
            INNER JOIN donors d ON bt.donors_id = d.donors_id
            INNER JOIN users u ON d.user_id = u.user_id";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // جلب تحليل معين للتعديل
    public function getTestById($test_id) {
    $sql = "SELECT * FROM blood_tests WHERE blood_tests_id = ?";  // غيرت هنا من test_id إلى blood_tests_id
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$test_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


    // تحديث تحليل
    public function updateBloodTest($data) {
        $sql = "UPDATE blood_tests SET
                    test_date = :test_date,
                    rh_factor = :rh_factor,
                    crossmatch_result = :crossmatch_result,
                    hiv = :hiv,
                    hbv = :hbv,
                    hcv = :hcv,
                    syphilis = :syphilis,
                    htlv = :htlv,
                    antibody_screening = :antibody_screening,
                    rbc_count = :rbc_count,
                    wbc_count = :wbc_count,
                    platelet_count = :platelet_count,
                    hemoglobin_level = :hemoglobin_level,
                    blood_condition = :blood_condition
                WHERE blood_tests_id = :blood_tests_id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    // حذف تحليل
    public function deleteBloodTest($test_id) {
        $sql = "DELETE FROM blood_tests WHERE blood_tests_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$test_id]);
    }


    // ADMIN manage blood tests

    public function getAllBloodTests() {
    $stmt = $this->conn->prepare("SELECT * FROM blood_tests");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

  // 1. جلب جميع التحاليل مع اسم المتبرع
    public function getAllBloodTestsWithDonorName() {
        $sql = "SELECT bt.*, u.name AS donor_name 
                FROM blood_tests bt
                JOIN donors d ON bt.donor_id = d.donor_id
                JOIN users u ON d.user_id = u.user_id
                ORDER BY bt.test_date DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. جلب تحليل واحد حسب id
    public function getBloodTestById($id) {
        $sql = "SELECT * FROM blood_tests WHERE blood_tests_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


   

public function updateBloodTestByBloodTestsId(
    $blood_tests_id,
    $test_date,
    $rh_factor,
    $crossmatch_result,
    $hiv,
    $hbv,
    $hcv,
    $syphilis,
    $htlv,
    $antibody_screening,
    $rbc_count,
    $wbc_count,
    $platelet_count,
    $hemoglobin_level,
    $blood_condition
) {
    $sql = "UPDATE blood_tests SET
                test_date = ?,
                rh_factor = ?,
                crossmatch_result = ?,
                hiv = ?,
                hbv = ?,
                hcv = ?,
                syphilis = ?,
                htlv = ?,
                antibody_screening = ?,
                rbc_count = ?,
                wbc_count = ?,
                platelet_count = ?,
                hemoglobin_level = ?,
                blood_condition = ?
            WHERE blood_tests_id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        $test_date,
        $rh_factor,
        $crossmatch_result,
        $hiv,
        $hbv,
        $hcv,
        $syphilis,
        $htlv,
        $antibody_screening,
        $rbc_count,
        $wbc_count,
        $platelet_count,
        $hemoglobin_level,
        $blood_condition,
        $blood_tests_id
    ]);
}



    
} 