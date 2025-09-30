<?php
class Hospital {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function addBloodRequest($blood_type, $blood_component, $units_needed, $hospitals_id, $status = 'Pending') {
        $stmt = $this->conn->prepare("INSERT INTO blood_requests (blood_type, blood_component, units_needed, hospitals_id, status, request_date) 
                                      VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$blood_type, $blood_component, $units_needed, $hospitals_id, $status]);
        return $stmt->rowCount() > 0;
    }

    public function updateBloodRequestStatus($request_id, $new_status) {
        $stmt = $this->conn->prepare("UPDATE blood_requests SET status = ? WHERE blood_requests_id = ?");
        $stmt->execute([$new_status, $request_id]);
        return $stmt->rowCount() > 0;
    }

    public function getBloodTypes() {
        return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    }

    public function getBloodComponents() {
        return ['Red Blood Cells', 'Platelets', 'Plasma', 'Whole Blood'];
    }

    public function getPendingBloodRequestsHospitals() {
        $stmt = $this->conn->prepare("SELECT DISTINCT hospitals_id FROM blood_requests WHERE status = 'Pending'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBloodRequestsByHospital($hospitals_id) {
        $stmt = $this->conn->prepare("SELECT br.*, h.hospital_name 
                                      FROM blood_requests br
                                      JOIN hospitals h ON br.hospitals_id = h.hospitals_id
                                      WHERE br.hospitals_id = ? AND br.status = 'Pending'");
        $stmt->execute([$hospitals_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   public function rejectBloodRequest($request_id) {
    // 1. تحديث حالة الطلب إلى "مرفوض"
    $stmt = $this->conn->prepare("UPDATE blood_requests SET status = 'rejected' WHERE blood_requests_id = ?");
    $success = $stmt->execute([$request_id]);

    if ($success && $stmt->rowCount() > 0) {
        // 2. جلب user_id للمستشفى من جدول blood_requests
        $stmt2 = $this->conn->prepare("SELECT hospitals.user_id 
                                       FROM blood_requests 
                                       JOIN hospitals ON blood_requests.hospitals_id = hospitals.hospitals_id 
                                       WHERE blood_requests.blood_requests_id = ?");
        $stmt2->execute([$request_id]);
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['user_id'])) {
            $user_id = $result['user_id'];

            // 3. إعداد بيانات الإشعار
            $message = "❌ تم رفض طلبك للحصول على وحدات الدم من بنك الدم.";
            $recipient_role = 'hospital';
            $reference_type = 'reject_request_hospital';

            // 4. استدعاء كلاس الإشعارات
            require_once 'class_Notification.php';
            $notification = new Notification($this->conn);

            // 5. إنشاء الإشعار
            $notification->createNotification([
                'user_id' => $user_id,
                'recipient_role' => $recipient_role,
                'message' => $message,
                'reference_id' => $request_id,
                'reference_type' => $reference_type
            ]);
        }
    }

    return $success;
}


 public function acceptBloodRequest($request_id) {
    // 1. جلب بيانات الطلب
    $stmt = $this->conn->prepare("SELECT blood_type, blood_component, units_needed, hospitals_id FROM blood_requests WHERE blood_requests_id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) return false;

    $blood_type = $request['blood_type'];
    $blood_component = $request['blood_component'];
    $units_needed = $request['units_needed'];
    $hospitals_id = $request['hospitals_id'];

    // 2. جلب user_id المرتبط بالمستشفى (من جدول hospitals)
    $stmt = $this->conn->prepare("SELECT user_id FROM hospitals WHERE hospitals_id = ?");
    $stmt->execute([$hospitals_id]);
    $hospitalUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_id = $hospitalUser['user_id'] ?? null;

    // 3. حساب إجمالي الكمية المتوفرة
    $stmt = $this->conn->prepare("SELECT SUM(quantity) as total_quantity FROM blood_stock 
                                  WHERE blood_type = ? AND blood_component = ? AND blood_condition = 'Valid'");
    $stmt->execute([$blood_type, $blood_component]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_available = $result['total_quantity'] ?? 0;

    if ($total_available < $units_needed) {
        echo "<script>alert('❌ المخزون غير كافٍ لتلبية هذا الطلب');</script>";
        return false;
    }

    // 4. خصم الكمية من المخزون
    $stmt = $this->conn->prepare("SELECT blood_stock_id, quantity FROM blood_stock 
                                  WHERE blood_type = ? AND blood_component = ? AND blood_condition = 'Valid' 
                                  ORDER BY quantity DESC");
    $stmt->execute([$blood_type, $blood_component]);
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $remaining = $units_needed;

    foreach ($stocks as $stock) {
        $stock_id = $stock['blood_stock_id'];
        $available_qty = $stock['quantity'];

        if ($available_qty >= $remaining) {
            $new_qty = $available_qty - $remaining;
            $stmtUpdate = $this->conn->prepare("UPDATE blood_stock SET quantity = ?, blood_condition = 'Valid' WHERE blood_stock_id = ?");
            $stmtUpdate->execute([$new_qty, $stock_id]);
            break;
        } else {
            $stmtUpdate = $this->conn->prepare("UPDATE blood_stock SET quantity = 0, blood_condition = 'Valid' WHERE blood_stock_id = ?");
            $stmtUpdate->execute([$stock_id]);
            $remaining -= $available_qty;
        }
    }

    // 5. تحديث حالة الطلب إلى "approved"
    $stmt = $this->conn->prepare("UPDATE blood_requests SET status = 'approved' WHERE blood_requests_id = ?");
    $stmt->execute([$request_id]);

    // 6. إنشاء إشعار للمستخدم المرتبط بالمستشفى
    require_once 'class_Notification.php';
    $notification = new Notification($this->conn);

    if ($user_id !== null) {
        $notification->createNotification([
            'user_id' => $user_id,
            'recipient_role' => 'hospital',
            'message' => '✅ تم قبول طلبك للحصول على وحدات الدم من بنك الدم.',
            'reference_id' => $request_id,
            'reference_type' => 'accept_request_hospital'
        ]);
    }

    return true;
}




    // جلب بيانات مستشفى معين بواسطة user_id (الربط عبر user_id)
public function getHospitalByUserId($user_id) {
    $stmt = $this->conn->prepare("
        SELECT hospital_name, phone, location, status, letter_file, license_file, tax_file, id_file, hospitals_id,
               latitude, longitude
        FROM hospitals
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// جلب كل المستشفيات مع بيانات المستخدمين (لصفحة الإدارة مثلاً)
public function getAllHospitalsWithUsers() {
    $stmt = $this->conn->query("
        SELECT h.hospitals_id, h.hospital_name, h.phone, h.location, h.status,
               h.letter_file, h.license_file, h.tax_file, h.id_file,
               u.user_id, u.name, u.email, u.created_at, u.is_active
        FROM hospitals h
        JOIN users u ON h.user_id = u.user_id
        WHERE u.role = 'hospital'
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function updateHospital($hospitals_id, $data) {
    $stmt = $this->conn->prepare("
        UPDATE hospitals SET
            hospital_name = :hospital_name,
            phone = :phone,
            location = :location,
            status = :status,
            letter_file = :letter_file,
            license_file = :license_file,
            tax_file = :tax_file,
            id_file = :id_file
        WHERE hospitals_id = :hospitals_id
    ");
    return $stmt->execute([
        ':hospital_name' => $data['hospital_name'],
        ':phone' => $data['phone'],
        ':location' => $data['location'],
        ':status' => $data['status'],
        ':letter_file' => $data['letter_file'],
        ':license_file' => $data['license_file'],
        ':tax_file' => $data['tax_file'],
        ':id_file' => $data['id_file'],
        ':hospitals_id' => $hospitals_id
    ]);
}


public function deleteHospital($hospitals_id) {
    $stmt = $this->conn->prepare("DELETE FROM hospitals WHERE hospitals_id = ?");
    return $stmt->execute([$hospitals_id]);
}






public function updateHospitalStatus($hospitals_id, $new_status) {
    $stmt = $this->conn->prepare("UPDATE hospitals SET status = ? WHERE hospitals_id = ?");
    return $stmt->execute([$new_status, $hospitals_id]);
}



public function updateHospitalProfile($hospitals_id, $hospital_name, $phone, $location, $latitude, $longitude) {
    $stmt = $this->conn->prepare("UPDATE hospitals 
                                  SET hospital_name = ?, phone = ?, location = ?, latitude = ?, longitude = ?
                                  WHERE hospitals_id = ?");
    return $stmt->execute([$hospital_name, $phone, $location, $latitude, $longitude, $hospitals_id]);
}


public function updateHospitalFiles($hospitals_id, $files) {
    $stmt = $this->conn->prepare("UPDATE hospitals 
        SET letter_file = ?, license_file = ?, tax_file = ?, id_file = ? 
        WHERE hospitals_id = ?");
    return $stmt->execute([
        $files['letter_file'], 
        $files['license_file'], 
        $files['tax_file'], 
        $files['id_file'], 
        $hospitals_id
    ]);
}

// جلب كل طلبات الدم للمستشفى بكل الحالات (مفلترة حسب hospitals_id)
public function getAllBloodRequestsByHospital($hospitals_id) {
    $stmt = $this->conn->prepare("SELECT * FROM blood_requests WHERE hospitals_id = ? ORDER BY request_date DESC");
    $stmt->execute([$hospitals_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// حذف طلب دم حسب request_id
public function deleteBloodRequest($request_id) {
    $stmt = $this->conn->prepare("DELETE FROM blood_requests WHERE blood_requests_id = ?");
    return $stmt->execute([$request_id]);
}



public function sendActivationEmail($email, $hospital_name) {
    require_once 'vendor/autoload.php'; // PHPMailer

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'expensetracker04@gmail.com'; // ✅ بريدك
        $mail->Password = 'lcxyixesqpsipykf';           // ✅ كلمة مرور التطبيق
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('expensetracker04@gmail.com', 'نظام بنك الدم');
        $mail->addAddress($email, $hospital_name);

        $mail->isHTML(true);
        $mail->Subject = 'تم تفعيل حساب المستشفى';
        $mail->Body = "
            <h3>تم تفعيل حساب المستشفى الخاص بك</h3>
            <p>تم التأكد من بيانات المستشفى <strong>{$hospital_name}</strong>.</p>
            <p>يمكنك الآن تسجيل الدخول إلى حسابك واستخدام النظام.</p>
            <p>شكرًا لتعاونكم.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}


}
?>
