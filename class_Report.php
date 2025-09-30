<?php
class Report {
    private $conn;
    private $table = "reports";

    public function __construct($db) {
        $this->conn = $db;
    }

   

    // جلب تقارير لموظف معين مع اسم الموظف من جدول users
    public function getReportsByStaffId($staff_id) {
        $sql = "SELECT r.*, u.name AS staff_name 
                FROM {$this->table} r
                JOIN users u ON u.user_id = r.staff_id
                WHERE r.staff_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$staff_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    
  


    public function getReportById($report_id, $staff_id) {
        $sql = "SELECT * FROM {$this->table} WHERE report_id = ? AND staff_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$report_id, $staff_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateReport($report_id, $staff_id, $report_type, $report_title, $report_body, $priority, $attachment_file = null) {
        if ($attachment_file) {
            $sql = "UPDATE {$this->table} SET report_type = ?, report_title = ?, report_body = ?, priority = ?, attachment_file = ? WHERE report_id = ? AND staff_id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$report_type, $report_title, $report_body, $priority, $attachment_file, $report_id, $staff_id]);
        } else {
            $sql = "UPDATE {$this->table} SET report_type = ?, report_title = ?, report_body = ?, priority = ? WHERE report_id = ? AND staff_id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$report_type, $report_title, $report_body, $priority, $report_id, $staff_id]);
        }
    }






public function uploadAttachmentFile($file)
{
    if (!empty($file['name'])) {
        $upload_dir = 'uploads/reports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($file['name']);
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $file_name;
        }
    }

    return null;
}

public function createReport($staff_id, $report_type, $report_title, $report_body, $priority, $attachment_file = null)
{
    $sql = "INSERT INTO reports (staff_id, report_type, report_title, report_body, priority, attachment_file)
            VALUES (:staff_id, :report_type, :report_title, :report_body, :priority, :attachment_file)";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->bindParam(':report_type', $report_type);
    $stmt->bindParam(':report_title', $report_title);
    $stmt->bindParam(':report_body', $report_body);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':attachment_file', $attachment_file);

    $success = $stmt->execute();

    if ($success) {
        // 1. جلب report_id
        $report_id = $this->conn->lastInsertId();

        // 2. تحميل كلاس الإشعارات
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // 3. صياغة نص الإشعار
        $notif_message = "📄 قام موظف بنك الدم بإرسال التقرير.";

        // 4. جلب كل المستخدمين اللي دورهم admin
        $userStmt = $this->conn->prepare("SELECT user_id FROM users WHERE role = 'admin'");
        $userStmt->execute();
        $admins = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. إرسال الإشعار لكل إداري
        foreach ($admins as $admin) {
            $notification->addNotification(
                $admin['user_id'],      // user_id
                'admin',                // recipient_role
                $notif_message,         // message
                $report_id,             // reference_id (من جدول reports)
                'reports'               // reference_type
            );
        }
    }

    return $success;
}


public function deleteReport($report_id, $staff_id) {
    $sql = "DELETE FROM reports WHERE report_id = :report_id AND staff_id = :staff_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    return $stmt->execute();
}


 

public function getAllReportsWithStaffName() {
    $sql = "SELECT r.*, u.name AS staff_name
            FROM reports r
            LEFT JOIN staff s ON r.staff_id = s.user_id
            LEFT JOIN users u ON s.user_id = u.user_id
            ORDER BY r.created_at DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}






public function getAllReports() {
    $stmt = $this->conn->query("SELECT * FROM reports ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}





public function deleteReportByAdmin($report_id) {
    $sql = "DELETE FROM reports WHERE report_id = :report_id";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute(['report_id' => $report_id]);
}



public function updateReportByAdmin($report_id, $report_type, $report_title, $report_body, $priority, $attachment_file = null)
{
    if ($attachment_file) {
        $sql = "UPDATE reports 
                SET report_type = ?, report_title = ?, report_body = ?, priority = ?, attachment_file = ?
                WHERE report_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$report_type, $report_title, $report_body, $priority, $attachment_file, $report_id]);
    } else {
        $sql = "UPDATE reports 
                SET report_type = ?, report_title = ?, report_body = ?, priority = ?
                WHERE report_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$report_type, $report_title, $report_body, $priority, $report_id]);
    }
}

public function getReportForAdmin($report_id) {
    $query = "
        SELECT 
            r.*,
            s.department,
            u.name AS staff_name,
            u.email,
            u.is_active
        FROM reports r
        JOIN staff s ON r.staff_id = s.staff_id
        JOIN users u ON s.user_id = u.user_id
        WHERE r.report_id = :report_id
        LIMIT 1
    ";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function getReportByIdForAdmin($report_id) {
    $sql = "SELECT * FROM reports WHERE report_id = :report_id LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':report_id', $report_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        return $stmt->fetch(PDO::FETCH_ASSOC); // ترجع صف واحد أو false لو ما في تقرير
    } else {
        return false; // فشل التنفيذ
    }
}



  

    // تحديث تقرير (لـ admin) حسب معرف التقرير
    public function updateReportForAdmin($report_id, $report_type, $report_title, $report_body, $priority, $attachment_file) {
        $sql = "UPDATE reports 
                SET report_type = ?, report_title = ?, report_body = ?, priority = ?, attachment_file = ? 
                WHERE report_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $report_type,
            $report_title,
            $report_body,
            $priority,
            $attachment_file,
            $report_id
        ]);
    }


}
