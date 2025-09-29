<?php
class Admission {
    private $conn;
    private $table = 'admissions';

    public $id;
    public $admission_year;
    public $course;
    public $student_name;
    public $father_name;
    public $mother_name;
    public $academic_qualification;
    public $whatsapp_number;
    public $alternate_number;
    public $email_address;
    public $country;
    public $photo_path;
    public $signature_path;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                SET
                    admission_year = :admission_year,
                    course = :course,
                    student_name = :student_name,
                    father_name = :father_name,
                    mother_name = :mother_name,
                    academic_qualification = :academic_qualification,
                    whatsapp_number = :whatsapp_number,
                    alternate_number = :alternate_number,
                    email_address = :email_address,
                    country = :country,
                    photo_path = :photo_path,
                    signature_path = :signature_path,
                    status = :status,
                    created_at = NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':admission_year', $this->admission_year);
        $stmt->bindParam(':course', $this->course);
        $stmt->bindParam(':student_name', $this->student_name);
        $stmt->bindParam(':father_name', $this->father_name);
        $stmt->bindParam(':mother_name', $this->mother_name);
        $stmt->bindParam(':academic_qualification', $this->academic_qualification);
        $stmt->bindParam(':whatsapp_number', $this->whatsapp_number);
        $stmt->bindParam(':alternate_number', $this->alternate_number);
        $stmt->bindParam(':email_address', $this->email_address);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindParam(':photo_path', $this->photo_path);
        $stmt->bindParam(':signature_path', $this->signature_path);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getAll($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT * FROM " . $this->table . " WHERE 1=1";
        
        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
        }
        if (!empty($filters['admission_year'])) {
            $query .= " AND admission_year = :admission_year";
        }
        if (!empty($filters['search'])) {
            $query .= " AND (student_name LIKE :search OR email_address LIKE :search OR whatsapp_number LIKE :search)";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        if (!empty($filters['admission_year'])) {
            $stmt->bindParam(':admission_year', $filters['admission_year']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt;
    }

    public function getCount($filters = []) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        
        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
        }
        if (!empty($filters['admission_year'])) {
            $query .= " AND admission_year = :admission_year";
        }
        if (!empty($filters['search'])) {
            $query .= " AND (student_name LIKE :search OR email_address LIKE :search OR whatsapp_number LIKE :search)";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        if (!empty($filters['admission_year'])) {
            $stmt->bindParam(':admission_year', $filters['admission_year']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table . "
                SET
                    status = :status
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>