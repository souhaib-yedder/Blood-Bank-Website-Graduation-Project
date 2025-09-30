<?php
class DonationCampaigns {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }




    public function createCampaign($staff_id, $name, $date, $location, $description, $target_units, $latitude, $longitude) {
    $stmt = $this->conn->prepare("INSERT INTO donation_campaigns 
        (staff_id, campaign_name, campaign_date, location, description, target_units, latitude, longitude, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");

    $success = $stmt->execute([
        $staff_id, $name, $date, $location, $description,
        $target_units, $latitude, $longitude
    ]);

    if ($success) {
        $campaign_id = $this->conn->lastInsertId();

        // ✅ استدعاء كلاس الإشعارات
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // 🟢 إشعار 1 للمتبرعين
        $messageToDonors = "📢 تم إضافة حملة تطوعية، لو مهتم بها أنضم لها.";
        $notification->notifyAllDonors($messageToDonors, $campaign_id, 'donation_campaigns');

        // 🟢 إشعار 2 لباقي الموظفين
        $messageToStaff = "📢 قام أحد الموظفين بإضافة حملة تطوعية تحت إسم {$name} ومكانها {$location} وهدفها تجميع {$target_units} وحدة دم.";

        $stmtStaff = $this->conn->prepare("SELECT user_id FROM users WHERE role = 'staff' AND user_id != ?");
        $stmtStaff->execute([$staff_id]);
        $staffMembers = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);

        foreach ($staffMembers as $staff) {
            $notification->createNotification([
                'user_id' => $staff['user_id'],
                'recipient_role' => 'staff',
                'message' => $messageToStaff,
                'reference_id' => $campaign_id,
                'reference_type' => 'donation_campaigns'
            ]);
        }
    }

    return $success;
}



    // جلب الحملات القريبة من موقع المستخدم (≤ 10 كم) ولم ينضم لها بعد
    public function getNearbyCampaigns($latitude, $longitude, $user_id) {
        $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);
        $donor_id = $donor['donors_id'] ?? 0;

        $sql = "
            SELECT c.*, 
                   (6371 * acos(
                       cos(radians(?)) * cos(radians(c.latitude)) *
                       cos(radians(c.longitude) - radians(?)) +
                       sin(radians(?)) * sin(radians(c.latitude))
                   )) AS distance
            FROM donation_campaigns c
            LEFT JOIN donations d ON c.donation_campaigns_id = d.donation_campaign_id AND d.donor_id = ?
            WHERE d.donation_campaign_id IS NULL
            HAVING distance <= 10
            ORDER BY distance ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$latitude, $longitude, $latitude, $donor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // جلب جميع الحملات التي لم ينضم إليها المتبرع
    public function getAllCampaignsExcludingUser($user_id) {
        $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);
        $donor_id = $donor['donors_id'] ?? 0;

        $sql = "
            SELECT c.*
            FROM donation_campaigns c
            LEFT JOIN donations d ON c.donation_campaigns_id = d.donation_campaign_id AND d.donor_id = ?
            WHERE d.donation_campaign_id IS NULL
            ORDER BY c.campaign_date DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$donor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // انضمام المتبرع لحملة تطوعية
    public function joinCampaign($campaign_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT donors_id FROM donors WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $donor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donor) return false;

        $donor_id = $donor['donors_id'];

        $check = $this->conn->prepare("SELECT * FROM donations WHERE donor_id = ? AND donation_campaign_id = ?");
        $check->execute([$donor_id, $campaign_id]);
        if ($check->fetch()) return false;

        $stmt = $this->conn->prepare("INSERT INTO donations (donor_id, donation_date, donation_campaign_id) VALUES (?, NOW(), ?)");
        return $stmt->execute([$donor_id, $campaign_id]);
    }

    // جلب الحملات التي انضم إليها المتبرع
    public function getJoinedCampaigns($donor_id) {
        $sql = "
            SELECT c.*
            FROM donations d
            JOIN donation_campaigns c ON d.donation_campaign_id = c.donation_campaigns_id
            WHERE d.donor_id = ?
            ORDER BY c.campaign_date DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$donor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // إلغاء الانضمام لحملة
    public function cancelJoin($campaign_id, $donor_id) {
        $stmt = $this->conn->prepare("DELETE FROM donations WHERE donation_campaign_id = ? AND donor_id = ?");
        return $stmt->execute([$campaign_id, $donor_id]);
    }


    // حذف حملة تطوعية
public function deleteCampaign($campaign_id) {
    // جلب اسم الحملة والموقع قبل الحذف
    $stmtSelect = $this->conn->prepare("SELECT campaign_name, location FROM donation_campaigns WHERE donation_campaigns_id = ?");
    $stmtSelect->execute([$campaign_id]);
    $campaign = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$campaign) {
        return false; // لم يتم العثور على الحملة
    }

    // تنفيذ الحذف
    $stmtDelete = $this->conn->prepare("DELETE FROM donation_campaigns WHERE donation_campaigns_id = ?");
    $deleted = $stmtDelete->execute([$campaign_id]);

    if ($deleted) {
        // إرسال إشعارات بعد الحذف
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        $message = "🚫 قام أحد الموظفين بحذف حملة تطوعية بعنوان '{$campaign['campaign_name']}' في موقع '{$campaign['location']}'.";

        // جلب المستخدمين الذين دورهم staff أو admin
        $userStmt = $this->conn->prepare("SELECT user_id FROM users WHERE role IN ('staff', 'admin')");
        $userStmt->execute();
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $notification->addNotification(
                $user['user_id'],
                'staff',
                $message,
                $campaign_id,
                'donation_campaigns'
            );
        }

        return true;
    }

    return false;
}


   public function updateCampaign($campaign_id, $staff_id, $campaign_name, $campaign_date, $location, $description, $target_units, $latitude, $longitude) {
 $stmt = $this->conn->prepare("
        UPDATE donation_campaigns
        SET 
            staff_id = ?, 
            campaign_name = ?, 
            campaign_date = ?, 
            location = ?, 
            description = ?, 
            target_units = ?, 
            latitude = ?, 
            longitude = ?
        WHERE donation_campaigns_id = ?
    ");

    $updated = $stmt->execute([
        $staff_id, $campaign_name, $campaign_date, $location, $description,
        $target_units, $latitude, $longitude, $campaign_id
    ]);

    if ($updated) {
        // إرسال إشعار بعد التحديث
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // صياغة الرسالة
        $message = "✏️ قام أحد الموظفين بتعديل الحملة التطوعية '{$campaign_name}' في موقع '{$location}'.";

        // جلب المستخدمين الذين دورهم staff أو admin
        $userStmt = $this->conn->prepare("SELECT user_id FROM users WHERE role IN ('staff', 'admin')");
        $userStmt->execute();
        $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        // إرسال إشعار لكل مستخدم
        foreach ($users as $user) {
            $notification->addNotification(
                $user['user_id'],          // user_id
                'staff',                   // recipient_role
                $message,                  // message
                $campaign_id,              // reference_id
                'donation_campaigns'       // reference_type
            );
        }
    }

    return $updated;
}

public function getAllCampaigns() {
    $sql = "SELECT * FROM donation_campaigns ORDER BY campaign_date DESC";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// admin mange campign 

public function getCampaignById($campaign_id) {
    $stmt = $this->conn->prepare("SELECT * FROM donation_campaigns WHERE donation_campaigns_id = ?");
    $stmt->execute([$campaign_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


public function getCampaignDonors($campaign_id) {
    $query = "
        SELECT u.name AS donor_name, d.blood_type, dn.donation_date
        FROM donations dn
        JOIN donors d ON dn.donor_id = d.donors_id
        JOIN users u ON d.user_id = u.user_id
        WHERE dn.donation_campaign_id = ?
    ";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([$campaign_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // الدالة الجديدة للحذف
    public function removeCampaignById($id) {
        $sql = "DELETE FROM donation_campaigns WHERE donation_campaigns_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }


   

}

?>
