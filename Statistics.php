<?php



class Statistics
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }


        // عدد السجلات في أي جدول
    public function countRecords($table) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS total FROM `$table`");
        $stmt->execute();
        return $stmt->fetchColumn();
    }


    

    /**
     * دالة مساعدة عامة ومحسّنة لجلب عدد الصفوف.
     * @param string $table اسم الجدول.
     * @param string $condition الشرط (مثال: "WHERE user_id = :id").
     * @param array $params مصفوفة المتغيرات لربطها بالاستعلام.
     * @return int عدد الصفوف.
     */
    private function getCount($table, $condition = "", $params = [])
    {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $table . " " . $condition;
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        } catch (PDOException $e) {
            // يمكنك تسجيل الخطأ هنا في ملف logs
            return 0;
        }
    }

    /**
     * دالة مساعدة عامة ومحسّنة لجلب آخر السجلات.
     * @param string $table اسم الجدول.
     * @param string $order_column العمود للترتيب بناءً عليه.
     * @param int $limit عدد السجلات.
     * @param string $condition الشرط (مثال: "WHERE donors_id = :id").
     * @param array $params مصفوفة المتغيرات لربطها.
     * @return array مصفوفة من السجلات.
     */
    public function getLatestRecords($table, $order_column, $limit = 10, $condition = "", $params = [])
    {
        try {
            // استخدام htmlspecialchars و strip_tags للحماية الأساسية من الحقن في أسماء الجداول والأعمدة
            $safe_table = htmlspecialchars(strip_tags($table));
            $safe_order_col = htmlspecialchars(strip_tags($order_column));

            $query = "SELECT * FROM " . $safe_table . " " . $condition . " ORDER BY " . $safe_order_col . " DESC LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => $val) {
                // استخدام bindValue للربط الآمن
                $stmt->bindValue(":$key", $val);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * دالة مساعدة لتنفيذ استعلام يجلب صفًا واحدًا.
     */
    private function executeQuery($query, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return null; }
    }

    /**
     * دالة مساعدة لتنفيذ استعلام يجلب كل الصفوف.
     */
    private function fetchAll($query, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }



    public function getTotalDonors() { return $this->getCount('donors'); }
    public function getTotalStaff() { return $this->getCount('staff'); }
    public function getTotalHospitals() { return $this->getCount('hospitals'); }
    public function getTotalCampaigns() { return $this->getCount('donation_campaigns'); }
    public function getBloodStockCount() {
        $result = $this->executeQuery("SELECT SUM(quantity) as total FROM blood_stock");
        return $result && $result['total'] ? (int)$result['total'] : 0;
    }
    public function getTotalDonorRequests() { return $this->getCount('blood_donor_requests'); }
    public function getPendingDonorRequests() { return $this->getCount('blood_donor_requests', "WHERE status = 'pending'"); }
    public function getTotalHospitalRequests() { return $this->getCount('blood_requests'); }
    public function getPendingHospitalRequests() { return $this->getCount('blood_requests', "WHERE status = 'pending'"); }
    public function getTotalReports() { return $this->getCount('reports'); }
    public function getHighPriorityReports() { return $this->getCount('reports', "WHERE priority = 'عالية'"); }
    public function getActiveCampaigns() { return $this->getCount('donation_campaigns', "WHERE status = 'active'"); }
    public function getPendingCampaigns() { return $this->getCount('donation_campaigns', "WHERE status = 'pending'"); }
    public function getDonationsByMonth() {
        return $this->fetchAll("SELECT DATE_FORMAT(donation_date, '%Y-%m') as month, COUNT(donations_id) as count FROM donations WHERE donation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
    }
    public function getBloodStockByType() {
        return $this->fetchAll("SELECT blood_type, SUM(quantity) as total FROM blood_stock GROUP BY blood_type HAVING total > 0 ORDER BY total DESC");
    }


    public function countDonationsByDonor($donor_id) {
        return $this->getCount('donations', "WHERE donor_id = :id", ['id' => $donor_id]);
    }

    public function getLastDonationDate($donor_id) {
        $result = $this->executeQuery("SELECT last_donation_date as last_date FROM donors WHERE donors_id = :id", ['id' => $donor_id]);
        return $result && $result['last_date'] ? $result['last_date'] : 'لا يوجد';

    }

    public function getLatestBloodTestResult($donor_id) {
        $result = $this->executeQuery("SELECT blood_condition FROM blood_tests WHERE donors_id = :id ORDER BY test_date DESC LIMIT 1", ['id' => $donor_id]);
        return $result ? $result['blood_condition'] : 'لا يوجد';
    }

    public function countRequestsToDonor($donor_id) {
    
    $bankRequests = $this->executeQuery(
        "SELECT COUNT(*) as total 
         FROM blood_bank_requests 
         WHERE status = 'pending' 
        
         AND request_type = 'bank'
         
           AND donors_id = :id",
        ['id' => $donor_id]
    );

    // عد الطلبات من جدول blood_donor_requests
    $donorRequests = $this->executeQuery(
        "SELECT COUNT(*) as total 
         FROM blood_donor_requests 
         WHERE status = 'pending' 
         AND donors_id = :id",
        ['id' => $donor_id]
    );

    // اجمع النتيجتين
    $totalBank = $bankRequests ? (int)$bankRequests['total'] : 0;
    $totalDonor = $donorRequests ? (int)$donorRequests['total'] : 0;

    return $totalBank + $totalDonor;
       
    }

    public function countUnreadNotificationsForUser($user_id) {
        return $this->getCount('notifications', "WHERE user_id = :id", ['id' => $user_id]);
    }

   public function countNearbyCampaigns($donor_id, $radius_km = 25) {
   

         // جلب donor_id والإحداثيات الخاصة بالمستخدم
    $stmt = $this->conn->prepare("SELECT donors_id, latitude, longitude FROM donors WHERE user_id = ?");
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor || is_null($donor['latitude']) || is_null($donor['longitude'])) {
        return 0; // ما فيش إحداثيات
    }

    $donor_id = $donor['donors_id'];
    $latitude = $donor['latitude'];
    $longitude = $donor['longitude'];

    // الاستعلام لحساب الحملات القريبة
    $sql = "
        SELECT COUNT(*) as total
        FROM (
            SELECT c.donation_campaigns_id,
                   (6371 * acos(
                       cos(radians(?)) * cos(radians(c.latitude)) *
                       cos(radians(c.longitude) - radians(?)) +
                       sin(radians(?)) * sin(radians(c.latitude))
                   )) AS distance
            FROM donation_campaigns c
            LEFT JOIN donations d 
                   ON c.donation_campaigns_id = d.donation_campaign_id 
                  AND d.donor_id = ?
            WHERE d.donation_campaign_id IS NULL
              AND c.status = 'active'
            HAVING distance <= 10
        ) as nearby
    ";

    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$latitude, $longitude, $latitude, $donor_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? (int)$result['total'] : 0;
      
    }

    public function getLatestBloodTestsForDonor($donor_id) {
        return $this->getLatestRecords('blood_tests', 'test_date', 10, "WHERE donors_id = :id", ['id' => $donor_id]);
    }

    public function getDonationLocationsForDonor($donor_id) {
        $query = "SELECT dc.campaign_name, dc.location, dc.latitude, dc.longitude
                  FROM donations d
                  JOIN donation_campaigns dc ON d.donation_campaign_id = dc.donation_campaigns_id
                  WHERE d.donor_id = :id AND dc.latitude IS NOT NULL AND dc.longitude IS NOT NULL";
        return $this->fetchAll($query, ['id' => $donor_id]);
    }



    /**
     * حساب عدد طلبات الدم لمستشفى معين.
     * @param int $hospital_id
     * @return int
     */
    public function countBloodRequestsByHospital($hospital_id) {
        return $this->getCount('blood_requests', "WHERE hospitals_id = :id", ['id' => $hospital_id]);
    }

    /**
     * حساب عدد المرضى المسجلين في مستشفى معين.
     * @param int $hospital_id
     * @return int
     */
    public function countPatientsByHospital($hospital_id) {
        return $this->getCount('patients', "WHERE hospitals_id = :id", ['id' => $hospital_id]);
    }

    /**
     * حساب عدد الإشعارات غير المقروءة لمستشفى معين.
     * @param int $user_id معرف المستخدم الخاص بالمستشفى.
     * @return int
     */
    public function countUnreadNotificationsForHospital($user_id) {
        // نفترض أن الإشعارات الموجهة للمستشفى تستخدم user_id الخاص بها
        return $this->getCount('notifications', "WHERE user_id = :id AND is_read = 0 AND recipient_role = 'hospital'", ['id' => $user_id]);
    }

    /**
     * حساب عدد الرسائل (الواردة) لمستشفى معين.
     * @param int $hospital_id
     * @return int
     */
    public function countMessagesForHospital($hospital_id) {
        return $this->getCount('contact_messages', "WHERE hospital_id = :id", ['id' => $hospital_id]);
    }


}
?>
