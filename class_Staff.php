<?php
class Staff {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getStaffByUserId($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.user_id, u.name, u.email, u.created_at,
                   s.staff_id, s.department, s.phone, s.date_of_birth, s.national_id, s.hiring_date, s.salary
            FROM users u
            JOIN staff s ON u.user_id = s.user_id
            WHERE u.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePhone($user_id, $newPhone) {
        $stmt = $this->conn->prepare("UPDATE staff SET phone = ? WHERE user_id = ?");
        return $stmt->execute([$newPhone, $user_id]);
    }

    public function updateUserInfo($user_id, $name, $email) {
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
        return $stmt->execute([$name, $email, $user_id]);
    }


    public function getStaffById($staff_id)
{
    $sql = "SELECT * FROM staff WHERE staff_id = :staff_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}
