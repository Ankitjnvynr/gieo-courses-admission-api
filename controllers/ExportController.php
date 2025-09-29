<?php
require_once __DIR__ . '/../models/Admission.php';

class ExportController {
    private $db;
    private $admission;

    public function __construct($db) {
        $this->db = $db;
        $this->admission = new Admission($db);
    }

    public function exportCSV() {
        try {
            $filters = [
                'status' => $_GET['status'] ?? '',
                'admission_year' => $_GET['admission_year'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            $stmt = $this->admission->getAll(1, 100000, $filters);
            $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($admissions)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No data to export']);
                return;
            }

            $filename = 'admissions_' . date('Y-m-d_His') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            $headers = [
                'ID',
                'Admission Year',
                'Course',
                'Student Name',
                'Father Name',
                'Mother Name',
                'Academic Qualification',
                'WhatsApp Number',
                'Alternate Number',
                'Email Address',
                'Country',
                'Status',
                'Application Date'
            ];
            fputcsv($output, $headers);

            foreach ($admissions as $admission) {
                $row = [
                    $admission['id'],
                    $admission['admission_year'],
                    $admission['course'],
                    $admission['student_name'],
                    $admission['father_name'],
                    $admission['mother_name'],
                    $admission['academic_qualification'],
                    $admission['whatsapp_number'],
                    $admission['alternate_number'],
                    $admission['email_address'],
                    $admission['country'],
                    $admission['status'],
                    $admission['created_at']
                ];
                fputcsv($output, $row);
            }

            fclose($output);
            exit();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    public function exportExcel() {
        try {
            $filters = [
                'status' => $_GET['status'] ?? '',
                'admission_year' => $_GET['admission_year'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            $stmt = $this->admission->getAll(1, 100000, $filters);
            $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($admissions)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No data to export']);
                return;
            }

            $filename = 'admissions_' . date('Y-m-d_His') . '.xls';

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo '<table border="1">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Admission Year</th>';
            echo '<th>Course</th>';
            echo '<th>Student Name</th>';
            echo '<th>Father Name</th>';
            echo '<th>Mother Name</th>';
            echo '<th>Academic Qualification</th>';
            echo '<th>WhatsApp Number</th>';
            echo '<th>Alternate Number</th>';
            echo '<th>Email Address</th>';
            echo '<th>Country</th>';
            echo '<th>Status</th>';
            echo '<th>Application Date</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($admissions as $admission) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($admission['id']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['admission_year']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['course']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['student_name']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['father_name']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['mother_name']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['academic_qualification']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['whatsapp_number']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['alternate_number']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['email_address']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['country']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['status']) . '</td>';
                echo '<td>' . htmlspecialchars($admission['created_at']) . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            exit();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    public function exportPDF() {
        try {
            $filters = [
                'status' => $_GET['status'] ?? '',
                'admission_year' => $_GET['admission_year'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];

            $stmt = $this->admission->getAll(1, 100000, $filters);
            $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($admissions)) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No data to export']);
                return;
            }

            $filename = 'admissions_' . date('Y-m-d_His') . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            echo json_encode([
                'success' => false,
                'message' => 'PDF export requires additional library (TCPDF or FPDF). Use CSV or Excel export instead.'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
        }
    }
}
?>