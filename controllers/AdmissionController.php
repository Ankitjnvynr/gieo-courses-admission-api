<?php
require_once __DIR__ . '/../models/Admission.php';
require_once __DIR__ . '/../utils/FileUpload.php';

class AdmissionController {
    private $db;
    private $admission;
    private $fileUpload;

    public function __construct($db) {
        $this->db = $db;
        $this->admission = new Admission($db);
        $this->fileUpload = new FileUpload();
    }

    public function create() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($_POST['admissionYear']) || empty($_POST['studentName']) || 
                empty($_POST['fatherName']) || empty($_POST['motherName']) ||
                empty($_POST['academicQualification']) || empty($_POST['whatsappNumber']) ||
                empty($_POST['emailAddress']) || empty($_POST['country'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            if (!filter_var($_POST['emailAddress'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                return;
            }

            if (!isset($_FILES['photo']) || !isset($_FILES['signature'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Photo and signature are required']);
                return;
            }

            $photoUpload = $this->fileUpload->upload($_FILES['photo'], 'photos');
            if (!$photoUpload['success']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Photo upload failed: ' . $photoUpload['error']]);
                return;
            }

            $signatureUpload = $this->fileUpload->upload($_FILES['signature'], 'signatures');
            if (!$signatureUpload['success']) {
                $this->fileUpload->deleteFile($photoUpload['path']);
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Signature upload failed: ' . $signatureUpload['error']]);
                return;
            }

            $this->admission->admission_year = htmlspecialchars(strip_tags($_POST['admissionYear']));
            $this->admission->course = htmlspecialchars(strip_tags($_POST['course']));
            $this->admission->student_name = htmlspecialchars(strip_tags($_POST['studentName']));
            $this->admission->father_name = htmlspecialchars(strip_tags($_POST['fatherName']));
            $this->admission->mother_name = htmlspecialchars(strip_tags($_POST['motherName']));
            $this->admission->academic_qualification = htmlspecialchars(strip_tags($_POST['academicQualification']));
            $this->admission->whatsapp_number = htmlspecialchars(strip_tags($_POST['whatsappNumber']));
            $this->admission->alternate_number = htmlspecialchars(strip_tags($_POST['alternateNumber'] ?? ''));
            $this->admission->email_address = htmlspecialchars(strip_tags($_POST['emailAddress']));
            $this->admission->country = htmlspecialchars(strip_tags($_POST['country']));
            $this->admission->photo_path = $photoUpload['path'];
            $this->admission->signature_path = $signatureUpload['path'];
            $this->admission->status = 'pending';

            if ($this->admission->create()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Admission application submitted successfully',
                    'data' => [
                        'id' => $this->admission->id,
                        'application_number' => 'ADM' . date('Y') . str_pad($this->admission->id, 6, '0', STR_PAD_LEFT)
                    ]
                ]);
            } else {
                $this->fileUpload->deleteFile($photoUpload['path']);
                $this->fileUpload->deleteFile($signatureUpload['path']);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to submit application']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

public function getAll() {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        $filters = [
            'status' => $_GET['status'] ?? '',
            'admission_year' => $_GET['admission_year'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $stmt = $this->admission->getAll($page, $limit, $filters);
        $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build base server URL dynamically
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . $host . "/";

        // Attach full URLs to photo and signature paths
        foreach ($admissions as &$admission) {
            if (!empty($admission['photo_path'])) {
                $admission['photo_url'] = $baseUrl . $admission['photo_path'];
            }
            if (!empty($admission['signature_path'])) {
                $admission['signature_url'] = $baseUrl . $admission['signature_path'];
            }
        }

        $total = $this->admission->getCount($filters);
        $totalPages = ceil($total / $limit);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $admissions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $total,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}


  public function getById($id) {
    try {
        $stmt = $this->admission->getById($id);
        
        if ($stmt->rowCount() > 0) {
            $admission = $stmt->fetch(PDO::FETCH_ASSOC);

            // Build base server URL dynamically
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . $host . "/";

            // Attach full URLs
            if (!empty($admission['photo_path'])) {
                $admission['photo_url'] = $baseUrl . $admission['photo_path'];
            }
            if (!empty($admission['signature_path'])) {
                $admission['signature_url'] = $baseUrl . $admission['signature_path'];
            }

            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $admission]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Admission not found']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}


    public function updateStatus($id) {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Status is required']);
                return;
            }

            $allowedStatuses = ['pending', 'approved', 'rejected'];
            if (!in_array($data['status'], $allowedStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $this->admission->id = $id;
            $this->admission->status = htmlspecialchars(strip_tags($data['status']));

            if ($this->admission->update()) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->admission->getById($id);
            
            if ($stmt->rowCount() > 0) {
                $admission = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($this->admission->delete($id)) {
                    $this->fileUpload->deleteFile($admission['photo_path']);
                    $this->fileUpload->deleteFile($admission['signature_path']);
                    
                    http_response_code(200);
                    echo json_encode(['success' => true, 'message' => 'Admission deleted successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to delete admission']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Admission not found']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
?>