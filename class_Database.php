<?php
class Database {
    private $host = 'localhost';
private $db_name = 'blood_bank';

    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
