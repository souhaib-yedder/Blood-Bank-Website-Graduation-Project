<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';
require_once 'db.php';


class Users {
    private $user_id;
    private $name;
    private $email;
    private $password;
    private $role;
    private $is_active;
    private $created_at;

    private $conn; // اتصال قاعدة البيانات

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ✅ تسجيل مستخدم جديد (عام)
    public function register($name, $email, $password, $role = 'donor') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role, is_active, created_at)
                                     VALUES (:name, :email, :password, :role, 1, :created_at)");

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':created_at', $createdAt);

        return $stmt->execute();
    }

    // ✅ تسجيل الدخول والتحقق من الصلاحية والتفعيل
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $this->user_id = $user['user_id'];
            $this->name = $user['name'];
            $this->email = $user['email'];
            $this->role = $user['role'];
            $this->is_active = $user['is_active'];
            $this->created_at = $user['created_at'];
            return $user;
        }
        return false;
    }

    // ✅ تسجيل متبرع
  public function registerDonor($data) {
    try {
        // تحقق من صحة البريد الإلكتروني
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "invalid_email";
        }

        // تحقق من تكرار البريد
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email=?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) {
            return "duplicate_email";
        }

        // تحقق من رقم الهاتف (10 أرقام فقط)
        if (!preg_match('/^\d{10}$/', $data['phone'])) {
            return "invalid_phone";
        }

        // تحقق من قوة كلمة المرور
        $password = $data['password'];
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $password)) {
            return "weak_password";
        }

        // تشفير كلمة المرور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // إدخال المستخدم
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'donor')");
        $stmt->execute([$data['name'], $data['email'], $hashedPassword]);
        $user_id = $this->conn->lastInsertId();

        // إدخال بيانات المتبرع
        $stmt2 = $this->conn->prepare("INSERT INTO donors (user_id, phone, birth_date, gender, blood_type, address, latitude, longitude, last_donation_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->execute([
            $user_id,
            $data['phone'],
            $data['birth_date'],
            $data['gender'],
            $data['blood_type'],
            $data['address'],
            $data['latitude'],
            $data['longitude'],
            $data['last_donation_date']
        ]);

        return true;

    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return false; // أي خطأ غير متوقع
    }
}



    // ✅ تسجيل مستشفى
    public function registerHospital($data) {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // إضافة المستخدم ب role hospital
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'hospital', 0)");
            $stmt->execute([$data['responsible_name'], $data['email'], $hashedPassword]);
            $user_id = $this->conn->lastInsertId();

            // إضافة بيانات المستشفى
            $stmt2 = $this->conn->prepare("INSERT INTO hospitals (user_id, hospital_name, phone, location, latitude, longitude, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt2->execute([
                $user_id,
                $data['hospital_name'],
                $data['phone'],
                $data['location'],
                $data['latitude'],
                $data['longitude']
            ]);

            return $user_id;
        } catch (PDOException $e) {
            error_log("Hospital registration error: " . $e->getMessage());
            return false;
        }
    }

    // ✅ إرسال إشعار
    public function sendNotification($message) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)");
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':message', $message);
        return $stmt->execute();
    }

    // ✅ تسجيل النشاط
    public function logActivity($action) {
        $stmt = $this->conn->prepare("INSERT INTO activity_logs (user_id, action, role) VALUES (:user_id, :action, :role)");
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':role', $this->role);
        return $stmt->execute();
    }

    // ✅ إرسال رسالة تواصل
    public function sendContactMessage($subject, $message) {
        $stmt = $this->conn->prepare("INSERT INTO contact_messages (user_id, subject, message)
                                     VALUES (:user_id, :subject, :message)");
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        return $stmt->execute();
    }

   
    // ✅ Getters
    public function getUserId() {
        return $this->user_id;
    }

    public function getRole() {
        return $this->role;
    }

    // ✅ تفعيل أو إلغاء تفعيل المستخدم
public function toggleActivation($user_id) {
    $stmt = $this->conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current = $stmt->fetchColumn();

    $newStatus = ($current == 1) ? 0 : 1;

    $stmt = $this->conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    return $stmt->execute([$newStatus, $user_id]);
}

// ✅ حذف مستخدم مع بياناته من donors
public function deleteUserWithDonor($user_id) {
    $stmt = $this->conn->prepare("DELETE FROM donors WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

public function getAllDonorUsers() {
    $stmt = $this->conn->prepare("
        SELECT u.user_id, u.name, u.email, u.created_at
        FROM users u
        JOIN donors d ON u.user_id = d.user_id
        WHERE u.role = 'donor'
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getAllDonors() {
    $stmt = $this->conn->query("
        SELECT 
            u.user_id, u.name, u.email, u.created_at, u.is_active,
            d.blood_type, d.birth_date, d.gender, d.phone, d.address, d.last_donation_date
        FROM users u
        JOIN donors d ON u.user_id = d.user_id
        WHERE u.role = 'donor'
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getAllHospitalsUsers() {
    $stmt = $this->conn->query("
        SELECT user_id, name, email, created_at, is_active
        FROM users
        WHERE role = 'hospital'
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function toggleUserActivation($user_id, $status)
{
    $stmt = $this->conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    return $stmt->execute([$status, $user_id]);
}




    // جلب كل المستخدمين من نوع staff مع بياناتهم من users فقط (لاحقاً ندمج مع staff في الصفحة)
    public function getAllStaffUsers() {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // حذف المستخدم
    public function deleteUser($user_id) {
// قبل حذف المستخدم
$stmt = $this->conn->prepare("DELETE FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);


        $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } 


    public function getUserById($user_id) {
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}



// ✅ إرسال رسالة دعم فني من المستشفى
public function sendHospitalSupportMessage($hospital_id, $subject, $message) {
    // 1. إدراج الرسالة
    $stmt = $this->conn->prepare("INSERT INTO contact_messages (hospital_id, subject, message) VALUES (?, ?, ?)");
    $success = $stmt->execute([$hospital_id, $subject, $message]);

    if ($success) {
        $message_id = $this->conn->lastInsertId(); // جلب ID الخاص بالرسالة

        // 2. جلب اسم المستشفى
        $hospitalStmt = $this->conn->prepare("SELECT hospital_name FROM hospitals WHERE hospitals_id = ?");
        $hospitalStmt->execute([$hospital_id]);
        $hospital = $hospitalStmt->fetch(PDO::FETCH_ASSOC);

        $hospital_name = $hospital ? $hospital['hospital_name'] : 'مستشفى غير معروف';

        // 3. استدعاء كلاس الإشعارات
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // 4. صياغة الرسالة
        $notif_message = "📨 تم إرسال رسالة من المستشفى: {$hospital_name}.";

        // 5. جلب المستخدمين (staff + admin)
        $userStmt = $this->conn->prepare("SELECT user_id FROM users WHERE role IN ('staff', 'admin')");
        $userStmt->execute();
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. إرسال إشعار لكل مستخدم
        foreach ($users as $user) {
            $notification->addNotification(
                $user['user_id'],            // user_id
                'staff',                     // recipient_role
                $notif_message,              // message
                $message_id,                 // reference_id (من contact_messages)
                'contact_messages'           // reference_type
            );
        }
    }

    return $success;
}


// ✅ جلب رسائل الدعم الفني الخاصة بمستشفى معين
public function getHospitalSupportMessages($hospital_id) {
    $stmt = $this->conn->prepare("SELECT * FROM contact_messages WHERE hospital_id = ? ORDER BY sent_at DESC");
    $stmt->execute([$hospital_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



// ✅ جلب جميع رسائل الدعم الفني مع ربطها بالمستشفيات


// ✅ جلب رسالة واحدة للرد أو العرض
public function getContactMessageById($id) {
    $stmt = $this->conn->prepare("
        SELECT cm.*, h.hospital_name 
        FROM contact_messages cm
        JOIN hospitals h ON cm.user_id = h.user_id
        WHERE cm.contact_messages_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ✅ الرد على الرسالة
public function replyToContactMessage($id, $replyText) {
    $now = date('Y-m-d H:i:s');
    $stmt = $this->conn->prepare("
        UPDATE contact_messages
        SET reply = ?, replied_at = ?
        WHERE contact_messages_id = ?
    ");
    return $stmt->execute([$replyText, $now, $id]);
}


    public function getAllContactMessages() {
        $sql = "SELECT c.*, h.name AS hospital_name 
                FROM contact_messages c
                JOIN hospitals h ON c.hospital_id = h.hospitals_id
                ORDER BY c.sent_at DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMessageById($id) {
        $stmt = $this->conn->prepare("SELECT c.*, h.name AS hospital_name 
                                      FROM contact_messages c 
                                      JOIN hospitals h ON c.hospital_id = h.hospitals_id 
                                      WHERE contact_messages_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function replyToMessage($message_id, $reply, $staff_id) {
        $stmt = $this->conn->prepare("UPDATE contact_messages 
                                      SET reply = ?, replied_at = NOW(), staff_id = ? 
                                      WHERE contact_messages_id = ?");
        return $stmt->execute([$reply, $staff_id, $message_id]);
    }

public function getAllContactMessagesWithHospital() {
    $query = "
        SELECT cm.*, h.name AS hospital_name
        FROM contact_messages cm
        JOIN hospitals h ON cm.hospital_id = h.id
        JOIN users u ON cm.staff_id = u.id
    ";
    $stmt = $this->conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    // جلب كل رسائل الدعم الفني مع اسم المستشفى (hospital_name) واسم الموظف المجيب (staff_name) إذا رد
    public function supportMessageGetAll() {
        $sql = "
            SELECT cm.contact_messages_id, cm.subject, cm.message, cm.sent_at, cm.reply, cm.replied_at,
                   h.hospital_name,
                   s.name AS staff_name
            FROM contact_messages cm
            JOIN hospitals h ON cm.hospital_id = h.hospitals_id
            LEFT JOIN staff st ON cm.staff_id = st.staff_id
            LEFT JOIN users s ON st.user_id = s.user_id
            ORDER BY cm.sent_at DESC
        ";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // جلب رسالة دعم فني واحدة مع بيانات المستشفى واسم الموظف المجيب (إن وجد)
    public function supportMessageGetById($message_id) {
        $sql = "
            SELECT cm.*, h.hospital_name,
                   s.name AS staff_name
            FROM contact_messages cm
            JOIN hospitals h ON cm.hospital_id = h.hospitals_id
            LEFT JOIN staff st ON cm.staff_id = st.staff_id
            LEFT JOIN users s ON st.user_id = s.user_id
            WHERE cm.contact_messages_id = ?
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$message_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // الرد على رسالة دعم فني مع تسجيل الرد وتاريخ الرد وموظف الرد
public function supportMessageReply($message_id, $reply_text, $staff_id) {
    // 1. الرد على الرسالة
    $sql = "
        UPDATE contact_messages
        SET reply = ?, replied_at = NOW(), staff_id = ?
        WHERE contact_messages_id = ?
    ";
    $stmt = $this->conn->prepare($sql);
    $success = $stmt->execute([$reply_text, $staff_id, $message_id]);

    if ($success && $stmt->rowCount() > 0) {
        // 2. جلب user_id الخاص بالمستشفى المرتبط بالرسالة
        $stmt2 = $this->conn->prepare("
            SELECT users.user_id
            FROM contact_messages
            JOIN hospitals ON contact_messages.hospital_id = hospitals.hospitals_id
            JOIN users ON hospitals.user_id = users.user_id
            WHERE contact_messages.contact_messages_id = ?
        ");
        $stmt2->execute([$message_id]);
        $result = $stmt2->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['user_id'])) {
            $user_id = $result['user_id'];

            // 3. إرسال الإشعار
            require_once 'class_Notification.php';
            $notification = new Notification($this->conn);

            $notification->createNotification([
                'user_id' => $user_id,
                'recipient_role' => 'hospital',
                'message' => '📩 تم مراسلتك من قبل الدعم الفني.',
                'reference_id' => $message_id,
                'reference_type' => 'contact_messages'
            ]);
        }
    }

    return $success;
}





  public function requestPasswordReset($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) return false;

        $user_id = $user['user_id'];
        $token = rand(100000, 999999);

        $stmt = $this->conn->prepare("INSERT INTO password_reset_requests (user_id, reset_token) VALUES (?, ?)");
        $stmt->execute([$user_id, $token]);

        // إعداد البريد
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'expensetracker04@gmail.com';
            $mail->Password = 'lcxyixesqpsipykf';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('expensetracker04@gmail.com', 'نظام بنك الدم');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'رمز استعادة كلمة المرور';
            $mail->Body = "رمز التحقق الخاص بك هو: <b>$token</b>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // يمكنك تسجيل الخطأ هنا أو طباعته لأغراض التطوير
            return false;
        }
    }

    public function verifyResetToken($email, $token) {
        $stmt = $this->conn->prepare("SELECT u.user_id, prr.reset_token, prr.status
            FROM users u
            JOIN password_reset_requests prr ON u.user_id = prr.user_id
            WHERE u.email = ? AND prr.reset_token = ? AND prr.status = 'pending'
            ORDER BY prr.request_date DESC LIMIT 1");
        $stmt->execute([$email, $token]);
        $result = $stmt->fetch();

        if ($result) {
            $stmt = $this->conn->prepare("UPDATE password_reset_requests SET status = 'used' WHERE reset_token = ?");
            $stmt->execute([$token]);
            return true;
        }
        return false;
    }

    public function resetPassword($email, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        return $stmt->execute([$hashed, $email]);
    }


    
}
?>
