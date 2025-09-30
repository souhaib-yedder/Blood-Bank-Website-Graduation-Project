<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class Donor {



    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // ✅ إرسال بريد إلكتروني للمستلم (الشخص الذي طلبنا منه التبرع)
    public function sendEmailToDonor($toEmail, $toName, $data) {
        require_once 'vendor/autoload.php';

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'expensetracker04@gmail.com'; // ⚠️ استخدم بريدك هنا
            $mail->Password = 'lcxyixesqpsipykf';           // ⚠️ استخدم كلمة المرور الخاصة بالتطبيق
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('expensetracker04@gmail.com', 'نظام بنك الدم');
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = '📢 طلب تبرع بالدم';
            $mail->Body = "
                <h3>مرحباً {$toName}</h3>
                <p>تم إرسال طلب تبرع بالدم إليك بالمعلومات التالية:</p>
                <ul>
                    <li><strong>اسم المريض:</strong> {$data['patient_name']}</li>
                    <li><strong>المستشفى:</strong> {$data['hospital_name']}</li>
                    <li><strong>فصيلة الدم:</strong> {$data['blood_type']}</li>
                    <li><strong>عدد الوحدات المطلوبة:</strong> {$data['units_needed']}</li>
                    <li><strong>نوع مكون الدم:</strong> {$data['blood_component']}</li>
                    <li><strong>تشخيص الحالة:</strong> {$data['diagnosis']}</li>
                    <li><strong>طلب مستعجل:</strong> " . ($data['urgent_request'] === 'yes' ? 'نعم' : 'لا') . "</li>
                    <li><strong>تاريخ الحاجة:</strong> {$data['operation_date']}</li>
                </ul>
                <p>إذا كنت قادراً على المساعدة، يرجى التواصل فوراً. شكرًا لك 🌟</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // ✅ تسجيل طلب التبرع في قاعدة البيانات
 public function makeBloodRequest($donors_id, $blood_type, $units_needed, $patient_name, $hospital_name, $urgent_request, $operation_date, $diagnosis, $blood_component) {
    // 1. إضافة الطلب في جدول blood_donor_requests
    $stmt = $this->conn->prepare("
        INSERT INTO blood_donor_requests (
            donors_id, blood_type_needed, units_needed, patient_name, hospital_name,
            urgent_request, operation_date, diagnosis, blood_component, request_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $success = $stmt->execute([
        $donors_id, $blood_type, $units_needed, $patient_name, $hospital_name,
        $urgent_request, $operation_date, $diagnosis, $blood_component
    ]);

    // 2. إذا نجح الإدخال، أضف إشعارات للمتبرعين
    if ($success) {
        $request_id = $this->conn->lastInsertId(); // reference_id
        $message = "قام متبرع بطلب دم منك للمريض '$patient_name' في مستشفى '$hospital_name'، عدد الوحدات المطلوبة: $units_needed.";

        // استدعاء كلاس الإشعارات
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // جلب user_id للمتبرعين ماعدا المرسل
$stmtDonors = $this->conn->prepare("SELECT user_id FROM donors WHERE donors_id = ?");
        $stmtDonors->execute([$donors_id]);
        $donorsList = $stmtDonors->fetchAll(PDO::FETCH_ASSOC);

        foreach ($donorsList as $donor) {
            $notification->createNotification([
                'user_id' => $donor['user_id'], // استخدم user_id الصحيح
                'recipient_role' => 'donor',
                'message' => $message,
                'reference_id' => $request_id,
                'reference_type' => 'blood_request'
            ]);
        }
    }

    return $success;
}


    // ✅ جلب بيانات المتبرع الحالي
    public function getDonorProfile($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.name, u.email, d.phone, d.blood_type, d.birth_date, 
                   d.address, d.last_donation_date, d.latitude, d.longitude
            FROM users u
            JOIN donors d ON u.user_id = d.user_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ البحث عن متبرعين متوافقين
    public function searchCompatibleDonors($current_user_id, $blood_type, $sort_by = '') {
        $results = [];

        // الموقع الحالي للمستخدم
        $stmt = $this->conn->prepare("SELECT latitude, longitude FROM donors WHERE user_id = ?");
        $stmt->execute([$current_user_id]);
        $location = $stmt->fetch();

        // الفصائل المتوافقة
        $compatible = [$blood_type];
        $comp_stmt = $this->conn->prepare("SELECT compatible_with FROM blood_compatibility WHERE recipient_type = ?");
        $comp_stmt->execute([$blood_type]);
        $rows = $comp_stmt->fetchAll();

        foreach ($rows as $row) {
            $list = array_map('trim', explode(',', $row['compatible_with']));
            foreach ($list as $type) {
                if (!in_array($type, $compatible)) {
                    $compatible[] = $type;
                }
            }
        }

        $placeholders = str_repeat('?,', count($compatible) - 1) . '?';
        $params = $compatible;

        if ($sort_by === 'distance' && $location) {
            $query = "SELECT u.user_id, u.name, u.email, d.*, 
                     (6371 * ACOS(
                        COS(RADIANS(?)) * COS(RADIANS(d.latitude)) *
                        COS(RADIANS(d.longitude) - RADIANS(?)) + 
                        SIN(RADIANS(?)) * SIN(RADIANS(d.latitude))
                     )) AS distance
                     FROM donors d
                     JOIN users u ON u.user_id = d.user_id
                     WHERE d.latitude IS NOT NULL AND d.longitude IS NOT NULL
                     AND d.blood_type IN ($placeholders)
                     AND u.user_id != ? 
                     HAVING distance <= 5
                     ORDER BY distance ASC";
            $params = array_merge([$location['latitude'], $location['longitude'], $location['latitude']], $params, [$current_user_id]);
        } else {
            $query = "SELECT u.user_id, u.name, u.email, d.* FROM donors d
                      JOIN users u ON d.user_id = u.user_id
                      WHERE d.blood_type IN ($placeholders)
                      AND u.user_id != ?";
            $params[] = $current_user_id;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $all = $stmt->fetchAll();

        // ترتيب النتائج حسب التوافق
        $priority = array_flip(array_merge([$blood_type], array_diff($compatible, [$blood_type])));
        usort($all, function($a, $b) use ($priority) {
            return $priority[$a['blood_type']] <=> $priority[$b['blood_type']];
        });

        foreach ($all as $row) {
            $row['compatibility'] = ($row['blood_type'] === $blood_type) ? 'مطابق ✅' : 'متوافق ♻️';
            $results[] = $row;
        }

        return $results;
    }


public function getDonationRequestsByUser($user_id) {
    $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $donor = $stmt->fetch();

    if (!$donor) return [];

    $donors_id = $donor['donors_id'];

    $stmt = $this->conn->prepare("
        SELECT blood_donor_requests_id, units_needed, request_date, patient_name, 
               urgent_request, operation_date, status, blood_type_needed, hospital_name
        FROM blood_donor_requests 
        WHERE donors_id = ?
        ORDER BY request_date DESC
    ");
    $stmt->execute([$donors_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ممكن تكون كلاس طلبات الدم البداية 

// ✅ جلب الطلبات المعلقة (pending) الخاصة بمتبرع معين
public function getPendingRequests($user_id) {
    $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $donor = $stmt->fetch();
    if (!$donor) return [];

    $donors_id = $donor['donors_id'];

    $stmt = $this->conn->prepare("
        SELECT blood_donor_requests_id, units_needed, request_date, patient_name, 
               urgent_request, operation_date, status, blood_type_needed, hospital_name
        FROM blood_donor_requests 
        WHERE donors_id = ? AND status = 'pending'
    ");
    $stmt->execute([$donors_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
//  النهاية  ممكن تكون كلاس طلبات الدم 
  



// ✅ تحديث حالة طلب معين (approved/rejected)
public function updateRequestStatus($request_id, $status) {
    $stmt = $this->conn->prepare("UPDATE blood_donor_requests SET status = ? WHERE blood_donor_requests_id = ?");
    return $stmt->execute([$status, $request_id]);
}




    // ✅ تحديث بيانات المتبرع
    public function updateDonorProfile($data) {
        $stmt = $this->conn->prepare("
            UPDATE donors 
            SET phone = :phone,
                blood_type = :blood_type,
                birth_date = :birth_date,
                address = :address,
                latitude = :latitude,
                longitude = :longitude
            WHERE user_id = :user_id
        ");
        return $stmt->execute([
            ':phone' => $data['phone'],
            ':blood_type' => $data['blood_type'],
            ':birth_date' => $data['birth_date'],
            ':address' => $data['address'],
            ':latitude' => $data['latitude'],
            ':longitude' => $data['longitude'],
            ':user_id' => $data['user_id']
        ]);
    }



    //start      طلبات الدم متبرع الى بنك الدم والعكس //



    // ✅ جلب آخر تاريخ تبرع من جدول donors
public function getLastDonationDate($donors_id) {
    $stmt = $this->conn->prepare("SELECT last_donation_date FROM donors WHERE donors_id = ?");
    $stmt->execute([$donors_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['last_donation_date'] : null;
}

// ✅ إنشاء طلب جديد لبنك الدم
public function requestFromBloodBank($donors_id, $blood_type, $blood_component) {
    // 1. تحقق من تاريخ التبرع
    $last_date = $this->getLastDonationDate($donors_id);
    if ($last_date) {
        $months = $this->monthDiff($last_date, date("Y-m-d"));
        if ($months < 4) {
            return "❌ لا يمكنك طلب دم لأن آخر تبرع كان منذ أقل من 4 أشهر.";
        }
    }

    // 2. إدراج الطلب
    $stmt = $this->conn->prepare("INSERT INTO blood_bank_requests 
        (blood_type, blood_component, status, request_date, request_type, donors_id) 
        VALUES (?, ?, 'pending', NOW(), 'donor', ?)");

    $success = $stmt->execute([$blood_type, $blood_component, $donors_id]);

    if ($success) {
        // 3. جلب ID للطلب الذي تم إنشاؤه للتو
        $request_id = $this->conn->lastInsertId();

        // 4. جلب اسم المتبرع
        $stmtDonor = $this->conn->prepare("
            SELECT u.name 
            FROM donors d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.donors_id = ?
        ");
        $stmtDonor->execute([$donors_id]);
        $donorData = $stmtDonor->fetch(PDO::FETCH_ASSOC);
        $donorName = $donorData ? $donorData['name'] : 'متبرع';

        // 5. جلب جميع الموظفين لإرسال إشعار لهم
        $stmtStaff = $this->conn->prepare("SELECT user_id FROM users WHERE role = 'staff'");
        $stmtStaff->execute();
        $staffList = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);

        // 6. إنشاء كائن الإشعارات واستدعاء الكلاس
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        $message = "📢 قام المتبرع {$donorName} بطلب دم من بنك الدم.";

        foreach ($staffList as $staff) {
            $notification->createNotification([
                'user_id' => $staff['user_id'],
                'recipient_role' => 'staff',
                'message' => $message,
                'reference_id' => $request_id,
                'reference_type' => 'blood_bank_requests'
            ]);
        }

        return "✅ تم إرسال طلب الدم بنجاح.";
    } else {
        return "❌ فشل في إرسال الطلب.";
    }
}

// ✅ لحساب فرق الأشهر بين تاريخين
private function monthDiff($start, $end) {
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    return ($endDate->format('Y') - $startDate->format('Y')) * 12 + ($endDate->format('m') - $startDate->format('m'));
}

  // ✅ جلب قائمة المتبرعين مع بيانات المستخدم
    public function getEligibleDonorsForBankRequest() {
        $stmt = $this->conn->prepare("SELECT d.donors_id, d.blood_type, d.last_donation_date, d.phone, u.name
                                      FROM donors d
                                      JOIN users u ON u.user_id = d.user_id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ إنشاء طلب تبرع جديد من بنك الدم
  public function makeBankRequest($donors_id, $blood_type, $blood_component) {
    // 1. تنفيذ الإدخال في جدول الطلبات
    $stmt = $this->conn->prepare("INSERT INTO blood_bank_requests (
                                    donors_id, blood_type, blood_component, status,
                                    request_date, request_type
                                  ) VALUES (?, ?, ?, 'pending', NOW(), 'bank')");

    $success = $stmt->execute([$donors_id, $blood_type, $blood_component]);

    if ($success) {
        // 2. جلب request_id بعد الإدخال
        $request_id = $this->conn->lastInsertId();

        // 3. إعداد رسالة الإشعار
        $message = "📢 قام بنك الدم بإرسال طلب دم منك وأن تكون متبرعًا لبنك الدم.";
        $reference_type = 'bank_request';

        // 4. إرسال الإشعار
        require_once 'class_Notification.php'; // تأكد من المسار الصحيح
        $notification = new Notification($this->conn);

        $notification->createNotification([
            'user_id'        => $donors_id,
            'recipient_role' => 'donor',
            'message'        => $message,
            'reference_id'   => $request_id,
            'reference_type' => $reference_type
        ]);
    }

    return $success;
}




 // ✅ تحديث حالة الطلب
public function updateBankRequestStatus($request_id, $new_status) {
    // 1. تحديث حالة الطلب
    $stmt = $this->conn->prepare("UPDATE blood_bank_requests SET status = ? WHERE request_id = ?");
    $success = $stmt->execute([$new_status, $request_id]);

    if ($success) {
        // 2. جلب donors_id من جدول الطلبات
        $stmt2 = $this->conn->prepare("SELECT donors_id FROM blood_bank_requests WHERE request_id = ?");
        $stmt2->execute([$request_id]);
        $donorData = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($donorData && isset($donorData['donors_id'])) {
            $user_id = $donorData['donors_id'];

            // 3. إعداد رسالة الإشعار
            $message = '';
            $reference_type = '';

            if ($new_status === 'completed') {
                $message = '✅ وافق بنك الدم وتم قبول طلبك للحصول على وحدات الدم.';
                $reference_type = 'accept_bank_request';
            } elseif ($new_status === 'cancelled') {
                $message = '❌ رفض بنك الدم طلبك للحصول على وحدات الدم.';
                $reference_type = 'reject_bank_request';
            }

            // 4. إنشاء إشعار
            require_once 'class_Notification.php';
            $notification = new Notification($this->conn);

            $notification->createNotification([
                'user_id' => $user_id,
                'recipient_role' => 'donor',
                'message' => $message,
                'reference_id' => $request_id,
                'reference_type' => $reference_type
            ]);
        }
    }

    return $success;
}


public function getBloodBankRequestsWithDonorName($type, $status) {
    $stmt = $this->conn->prepare("
        SELECT 
            r.request_id,  -- ✅ المفتاح الأساسي الصحيح
            r.blood_type,
            r.blood_component,
            r.status,
            r.request_date,
            u.name AS donor_name
        FROM blood_bank_requests r
        JOIN donors d ON r.donors_id = d.donors_id
        JOIN users u ON d.user_id = u.user_id
        WHERE r.request_type = ? AND r.status = ?
        ORDER BY r.request_date DESC
    ");
    $stmt->execute([$type, $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
//4/طلبات الواردة 

 
// ✅ جلب الطلبات المعلقة من المرضى إلى المتبرع
public function getDonorPendingRequests($user_id) {
    $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $donor = $stmt->fetch();
    if (!$donor) return [];

    $donors_id = $donor['donors_id'];
    $stmt = $this->conn->prepare("
        SELECT * FROM blood_donor_requests 
        WHERE donors_id = ? AND status = 'pending'
        ORDER BY request_date DESC
    ");
    $stmt->execute([$donors_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ تحديث حالة طلب التبرع المرسل من مريض إلى المتبرع
public function updateDonorRequestStatus($request_id, $status) {
    $stmt = $this->conn->prepare("
        UPDATE blood_donor_requests 
        SET status = ? 
        WHERE blood_donor_requests_id = ?
    ");
    return $stmt->execute([$status, $request_id]);
}

// ✅ جلب الطلبات المرسلة من بنك الدم إلى المتبرع (الحالة pending فقط)
public function getIncomingBankRequests($donors_id) {
    $stmt = $this->conn->prepare("
        SELECT * FROM blood_bank_requests 
        WHERE request_type = 'bank' 
          AND status = 'pending'
          AND donors_id = ?
        ORDER BY request_date DESC
    ");
    $stmt->execute([$donors_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ جلب الطلبات المرسلة من بنك الدم إلى المتبرع (معالجة: مقبولة أو مرفوضة)
public function getProcessedBankRequests($donors_id) {
    $stmt = $this->conn->prepare("
        SELECT * FROM blood_bank_requests 
        WHERE request_type = 'bank' 
          AND status IN ('completed', 'cancelled')
          AND donors_id = ?
        ORDER BY request_date DESC
    ");
    $stmt->execute([$donors_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// ✅ تحديث حالة الطلب المرسل من بنك الدم إلى المتبرع
public function updateIncomingBankRequestStatus($request_id, $status) {
    $stmt = $this->conn->prepare("
        UPDATE blood_bank_requests 
        SET status = ? 
        WHERE request_id = ?
    ");
    return $stmt->execute([$status, $request_id]);
}

public function getBloodBankRequestsCompletedAllTypes() {
    $stmt = $this->conn->prepare("
        SELECT 
            r.request_id,  
            r.blood_type,
            r.blood_component,
            r.status,
            r.request_date,
            r.request_type,
            u.name AS donor_name
        FROM blood_bank_requests r
        JOIN donors d ON r.donors_id = d.donors_id
        JOIN users u ON d.user_id = u.user_id
        WHERE r.status = 'completed'
        ORDER BY r.request_date DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

///خاصة بالتحليل  
public function getRequestWithDonorById($request_id) {
    $stmt = $this->conn->prepare("
        SELECT r.*, u.name AS donor_name, d.donors_id
        FROM blood_bank_requests r
        JOIN donors d ON r.donors_id = d.donors_id
        JOIN users u ON d.user_id = u.user_id
        WHERE r.request_id = ?
    ");
    $stmt->execute([$request_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/// 
public function registerDonor($data) {
    try {
        // أولاً: تحقق من وجود إيميل مسبقاً
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'هذا البريد الإلكتروني مستخدم مسبقًا.'];
        }

        // سجل بيانات المستخدم في جدول users
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'donor')");
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->execute([$data['name'], $data['email'], $hashedPassword]);

        $user_id = $this->conn->lastInsertId();

        // سجل بيانات المتبرع في جدول donors
        $stmt = $this->conn->prepare("INSERT INTO donors (user_id, phone, blood_type, birth_date, gender, address, last_donation_date, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $data['phone'],
            $data['blood_type'],
            $data['birth_date'],
            $data['gender'],
            $data['address'],
            $data['last_donation_date'],
            $data['latitude'],
            $data['longitude']
        ]);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ أثناء التسجيل.'];
    }
}

//

public function getDonorDetailsByUserId($user_id) {
    $stmt = $this->conn->prepare("
        SELECT * FROM donors WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//admin manamge blood tests

public function getDonorById($donor_id) {
    $stmt = $this->conn->prepare("SELECT * FROM donors WHERE donors_id = ?");
    $stmt->execute([$donor_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/////2////


public function getAllRequests($user_id) {
    $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $donor = $stmt->fetch();
    if (!$donor) return [];

    $donors_id = $donor['donors_id'];

    $stmt = $this->conn->prepare("
        SELECT blood_donor_requests_id, units_needed, request_date, patient_name, 
               urgent_request, operation_date, status, blood_type_needed, hospital_name
        FROM blood_donor_requests 
        WHERE donors_id = ?
        ORDER BY request_date DESC
    ");
    $stmt->execute([$donors_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




}
