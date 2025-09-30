<?php
class Notification {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // إضافة إشعار واحد
    public function createNotification($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, recipient_role, message, reference_id, reference_type, created_at, is_read
            ) VALUES (?, ?, ?, ?, ?, NOW(), 0)
        ");
        return $stmt->execute([
            $data['user_id'],
            $data['recipient_role'],
            $data['message'],
            $data['reference_id'],
            $data['reference_type']
        ]);
    }

    public function addNotification($user_id, $recipient_role, $message, $reference_id, $reference_type) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, recipient_role, message, reference_id, reference_type, created_at, is_read
            ) VALUES (
                :user_id, :recipient_role, :message, :reference_id, :reference_type, NOW(), 0
            )
        ");
        return $stmt->execute([
            ':user_id' => $user_id,
            ':recipient_role' => $recipient_role,
            ':message' => $message,
            ':reference_id' => $reference_id,
            ':reference_type' => $reference_type
        ]);
    }

    public function notifyAllDonors($message, $reference_id, $reference_type) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE role = 'donor'");
        $stmt->execute();
        $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertStmt = $this->conn->prepare("
            INSERT INTO notifications (
                user_id, recipient_role, message, reference_id, reference_type, created_at, is_read
            ) VALUES (
                :user_id, 'donor', :message, :reference_id, :reference_type, NOW(), 0
            )
        ");

        foreach ($donors as $donor) {
            $insertStmt->execute([
                ':user_id' => $donor['user_id'],
                ':message' => $message,
                ':reference_id' => $reference_id,
                ':reference_type' => $reference_type
            ]);
        }
        return true;
    }

 
    // ✅ تحديث حالة الإشعار إلى "مقروء"
    public function markAsRead($notification_id) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
        return $stmt->execute([$notification_id]);
    }

    // ❌ حذف الإشعار
    public function deleteNotification($notification_id) {
        $stmt = $this->conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
        return $stmt->execute([$notification_id]);
    }


//3//


public function getNotificationsByUser($user_id, $recipient_role = 'donor') {
    $stmt = $this->conn->prepare("
        SELECT notification_id, message, created_at, is_read, reference_type, reference_id
        FROM notifications 
        WHERE user_id = :user_id AND recipient_role = :recipient_role
        ORDER BY created_at DESC
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':recipient_role' => $recipient_role
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



//}
public function getNotificationsByUserId(int $user_id) {
    $stmt = $this->conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
