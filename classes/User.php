<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $phone;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registar novo utilizador
    function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    password_hash = :password_hash,
                    phone = :phone";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        // Hash da password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind valores
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':phone', $this->phone);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar se email já existe
    function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        $num = $stmt->rowCount();
        return $num > 0;
    }

    // Login do utilizador
    public function login($email, $password) {
        $query = "SELECT id, first_name, last_name, email, password_hash FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password_hash'])) {
                return $row;
            }
        }
        return false;
    }
}
?>
