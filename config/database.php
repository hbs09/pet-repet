<?php
class Database {
    private $host = "localhost";
    private $db_name = "pet_repet";
    private $username = "root";
    private $password = "2144";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        if (!extension_loaded('pdo_mysql')) {
            die("PDO MySQL driver not installed. Please enable it in your php.ini.");
        }
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
