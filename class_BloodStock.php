<?php
class BloodStock {
    private $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // جلب جميع المخزونات مع شروط الدم السليم والمخزون هو بنك دم فقط
public function getBloodStocks() {
    $stmt = $this->conn->prepare("SELECT blood_type, blood_component, SUM(quantity) AS quantity
                                  FROM blood_stock 
                                  WHERE blood_condition = 'Valid' 
                                  GROUP BY blood_type, blood_component 
                                  ORDER BY blood_type, blood_component");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function addBloodUnit($blood_type, $quantity, $receipt_date, $expiration_date, $blood_condition, $source, $notes, $blood_component) {
    // إدخال وحدة دم جديدة في جدول blood_stock
    $stmt = $this->conn->prepare("INSERT INTO blood_stock (
        blood_type, 
        blood_component, 
        quantity, 
        receipt_date, 
        expiration_date, 
        blood_condition, 
        source, 
        notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$blood_type, $blood_component, $quantity, $receipt_date, $expiration_date, $blood_condition, $source, $notes])) {
        
        // ✅ إذا كانت حالة الدم "Valid"، نقوم بتحديث المخزون
        if ($blood_condition == 'Valid') {
            $this->updateBloodStock($blood_type, $blood_component, $quantity);
        }

        // ✅ جلب ID الوحدة المُضافة
        $bloodStockId = $this->conn->lastInsertId();

        // ✅ إشعار الموظفين
        require_once 'class_Notification.php';
        $notification = new Notification($this->conn);

        // نص الإشعار
        $message = "🩸 قام أحد الموظفين بإضافة عدد {$quantity} وحدة دم إلى مخزون الدم.";

        // جلب كل الموظفين والإداريين
        $usersStmt = $this->conn->prepare("SELECT user_id FROM users WHERE role IN ('staff', 'admin')");
        $usersStmt->execute();
        $recipients = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recipients as $recipient) {
            $notification->addNotification(
                $recipient['user_id'],
                'staff', // recipient_role
                $message,
                $bloodStockId,
                'blood_stock'
            );
        }

        return true;
    } else {
        return "❌ حدث خطأ أثناء إضافة وحدة الدم.";
    }
}






    // تحديث كمية الدم في المخزون بعد قبول طلب الدم
    public function updateBloodStockQuantity($blood_stock_id, $quantity) {
        // نقوم بتقليص الكمية من المخزون بناءً على الطلب المقبول
        $stmt = $this->conn->prepare("UPDATE blood_stock SET quantity = quantity - ? WHERE blood_stock_id = ? AND blood_condition = 'Valid' ");
        $stmt->execute([$quantity, $blood_stock_id]);
        return $stmt->rowCount() > 0;
    }

    // جلب المخزون المتاح بناءً على شروط معينة
    public function getAvailableBloodStock($hospital_id) {
        $stmt = $this->conn->prepare("SELECT * FROM blood_stock WHERE hospital_id = ? AND blood_condition = 'Valid' ");
        $stmt->execute([$hospital_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // تحديث حالة المخزون وحساب الكمية المتاحة
public function updateBloodStock($blood_stock_id, $quantity) {
    // التحقق من أن حالة blood_condition هي "valid"
    $stmt = $this->conn->prepare("SELECT * FROM blood_stock WHERE blood_stock_id = ? AND blood_condition = 'valid'");
    $stmt->execute([$blood_stock_id]);
    $blood_stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($blood_stock) {
        // تقليل الكمية في المخزون فقط (لا يوجد تحديث للحالة بعد حذف عمود status)
        $new_quantity = $blood_stock['quantity'] - $quantity;

        // تأكد من أن الكمية الجديدة ليست سالبة
        if ($new_quantity < 0) {
            $new_quantity = 0; // أو يمكنك إلغاء العملية بالكامل
        }

        $stmt = $this->conn->prepare("UPDATE blood_stock SET quantity = ? WHERE blood_stock_id = ?");
        $stmt->execute([$new_quantity, $blood_stock_id]);

        return true;
    }

    return false; // إذا كانت الحالة غير "valid"
}

    




// جلب جميع وحدات الدم (تفصيلية)
public function getAllBloodUnits() {
    $stmt = $this->conn->prepare("SELECT * FROM blood_stock ORDER BY receipt_date DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// حذف وحدة دم
public function deleteBloodUnit($blood_stock_id) {
    $stmt = $this->conn->prepare("DELETE FROM blood_stock WHERE blood_stock_id = ?");
    return $stmt->execute([$blood_stock_id]);
}

// جلب وحدة دم مفردة
public function getBloodUnitById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM blood_stock WHERE blood_stock_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// تحديث وحدة دم
public function updateBloodUnit($id, $blood_type, $blood_component, $quantity, $receipt_date, $expiration_date, $blood_condition, $source, $notes) {
    $stmt = $this->conn->prepare("UPDATE blood_stock SET 
        blood_type = ?, 
        blood_component = ?, 
        quantity = ?, 
        receipt_date = ?, 
        expiration_date = ?, 
        blood_condition = ?, 
        source = ?, 
        notes = ? 
        WHERE blood_stock_id = ?");
    return $stmt->execute([$blood_type, $blood_component, $quantity, $receipt_date, $expiration_date, $blood_condition, $source, $notes, $id]);
}





public function invalidateExpiredBloodUnits() {
    $stmt = $this->conn->prepare("UPDATE blood_stock 
                                  SET blood_condition = 'Invalid' 
                                  WHERE expiration_date < CURDATE() AND blood_condition = 'Valid'");
    $stmt->execute();
}


public function getAllBloodUnitsFiltered($type = '', $component = '') {
    $sql = "SELECT * FROM blood_stock WHERE 1";
    $params = [];

    if (!empty($type)) {
        $sql .= " AND blood_type = ?";
        $params[] = $type;
    }

    if (!empty($component)) {
        $sql .= " AND blood_component = ?";
        $params[] = $component;
    }

    $stmt = $this->conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}

?>
