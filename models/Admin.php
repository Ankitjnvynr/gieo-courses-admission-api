<?php
class Admin {
    private $conn;
    private $table = 'admins';

    public $id;
    public $email;
    public $password;
    public $name;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                return [
                    'success' => true,
                    'data' => [
                        'id' => $row['id'],
                        'email' => $row['email'],
                        'name' => $row['name']
                    ]
                ];
            }
        }
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                SET
                    email = :email,
                    password = :password,
                    name = :name,
                    created_at = NOW()";

        $stmt = $this->conn->prepare($query);

        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':name', $this->name);

        return $stmt->execute();
    }
}
?>