<?php

class Patient {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // ✅ إضافة مريض جديد
    public function addPatient($hospital_id, $patient_name, $blood_type, $urgency_level, $notes = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO patients (hospital_id, patient_name, blood_type, urgency_level, notes, registered_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        return $stmt->execute([
            $hospital_id,
            $patient_name,
            $blood_type,
            $urgency_level,
            $notes
        ]);
    }

    // ✅ جلب كل المرضى التابعين لمستشفى معيّن
    public function getPatientsByHospital($hospital_id) {
        $stmt = $this->conn->prepare("SELECT * FROM patients WHERE hospital_id = ?");
        $stmt->execute([$hospital_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ حذف مريض
 public function deletePatient($patient_id) {
        $stmt = $this->conn->prepare("DELETE FROM patients WHERE patients_id = ?");
        return $stmt->execute([$patient_id]);
    }

    // ✅ تحديث بيانات مريض
    public function updatePatient($patient_id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE patients SET 
                patient_name = :name,
                blood_type = :blood_type,
                urgency_level = :urgency_level,
                notes = :notes
            WHERE patients_id = :id
        ");
        return $stmt->execute([
            ':name' => $data['patient_name'],
            ':blood_type' => $data['blood_type'],
            ':urgency_level' => $data['urgency_level'],
            ':notes' => $data['notes'],
            ':id' => $patient_id
        ]);
    }

    // ✅ جلب مريض معين
    public function getPatientById($patient_id) {
        $stmt = $this->conn->prepare("SELECT * FROM patients WHERE patients_id = ?");
        $stmt->execute([$patient_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // ➔ إضافة مريض جديد
public function add($hospital_id, $patient_name, $blood_type, $urgency_level, $condition_description, $needed_units) {
        try {
            $sql = "INSERT INTO patients (hospitals_id, patient_name, blood_type, urgency_level, condition_description, needed_units, registered_at)
                    VALUES (:hospital_id, :patient_name, :blood_type, :urgency_level, :condition_description, :needed_units, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':hospital_id', $hospital_id);
            $stmt->bindParam(':patient_name', $patient_name);
            $stmt->bindParam(':blood_type', $blood_type);
            $stmt->bindParam(':urgency_level', $urgency_level);
            $stmt->bindParam(':condition_description', $condition_description);
            $stmt->bindParam(':needed_units', $needed_units);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return false;
        }
    }
// ➔ جلب كل المرضى لمستشفى معين
public function getByHospital($hospital_id) {
    $stmt = $this->conn->prepare("SELECT * FROM patients WHERE hospitals_id = ? ORDER BY registered_at DESC");
    $stmt->execute([$hospital_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ➔ جلب مريض معين حسب المعرف
public function getById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM patients WHERE patients_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ➔ تحديث بيانات مريض
public function update($id, $data) {
    $sql = "UPDATE patients SET 
                patient_name = ?, 
                blood_type = ?, 
                urgency_level = ?, 
                condition_description = ?, 
                needed_units = ? 
            WHERE patients_id = ?";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        $data['patient_name'],
        $data['blood_type'],
        $data['urgency_level'],
        $data['condition_description'],
        $data['needed_units'],
        $id
    ]);
}

public function delete($id) {
    $stmt = $this->conn->prepare("DELETE FROM patients WHERE patients_id = ?");
    return $stmt->execute([$id]);
}

public function getAllWithHospital() {
    $sql = "SELECT p.*, h.hospital_name 
            FROM patients p 
            JOIN hospitals h ON p.hospitals_id = h.hospitals_id 
            ORDER BY p.registered_at DESC";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
