<?php
/**
 * Setup Controller
 * Handles database installation via API endpoint
 */

class SetupController {
    private $host;
    private $dbName;
    private $username;
    private $password;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbName = $_ENV['DB_NAME'] ?? 'admission_db';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
    }

    public function install() {
        try {
            $steps = [];
            
            // Step 1: Connect to MySQL server (without database)
            $steps[] = ['step' => 'Connecting to MySQL server', 'status' => 'processing'];
            
            $conn = new PDO(
                "mysql:host={$this->host}",
                $this->username,
                $this->password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $steps[0]['status'] = 'success';
            $steps[0]['message'] = 'Connected successfully';

            // Step 2: Create database
            $steps[] = ['step' => 'Creating database', 'status' => 'processing'];
            
            $conn->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn->exec("USE `{$this->dbName}`");
            
            $steps[1]['status'] = 'success';
            $steps[1]['message'] = "Database '{$this->dbName}' created/verified";

            // Step 3: Create admins table
            $steps[] = ['step' => 'Creating admins table', 'status' => 'processing'];
            
            $conn->exec("CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $steps[2]['status'] = 'success';
            $steps[2]['message'] = 'Admins table created';

            // Step 4: Create admissions table
            $steps[] = ['step' => 'Creating admissions table', 'status' => 'processing'];
            
            $conn->exec("CREATE TABLE IF NOT EXISTS admissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admission_year VARCHAR(20) NOT NULL,
                course VARCHAR(255) NOT NULL,
                student_name VARCHAR(255) NOT NULL,
                father_name VARCHAR(255) NOT NULL,
                mother_name VARCHAR(255) NOT NULL,
                academic_qualification VARCHAR(100) NOT NULL,
                whatsapp_number VARCHAR(20) NOT NULL,
                alternate_number VARCHAR(20),
                email_address VARCHAR(255) NOT NULL,
                country VARCHAR(100) NOT NULL,
                photo_path VARCHAR(500) NOT NULL,
                signature_path VARCHAR(500) NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_admission_year (admission_year),
                INDEX idx_email (email_address),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $steps[3]['status'] = 'success';
            $steps[3]['message'] = 'Admissions table created';

            // Step 5: Create application_logs table
            $steps[] = ['step' => 'Creating application_logs table', 'status' => 'processing'];
            
            $conn->exec("CREATE TABLE IF NOT EXISTS application_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admission_id INT,
                action VARCHAR(100) NOT NULL,
                performed_by INT,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admission_id) REFERENCES admissions(id) ON DELETE CASCADE,
                FOREIGN KEY (performed_by) REFERENCES admins(id) ON DELETE SET NULL,
                INDEX idx_admission_id (admission_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $steps[4]['status'] = 'success';
            $steps[4]['message'] = 'Application logs table created';

            // Step 6: Insert default admin user
            $steps[] = ['step' => 'Creating default admin user', 'status' => 'processing'];
            
            // Check if admin already exists
            $stmt = $conn->query("SELECT COUNT(*) FROM admins WHERE email = 'admin@example.com'");
            $adminExists = $stmt->fetchColumn() > 0;
            
            if (!$adminExists) {
                $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO admins (email, password, name) VALUES (?, ?, ?)");
                $stmt->execute(['admin@example.com', $defaultPassword, 'Admin User']);
                $steps[5]['status'] = 'success';
                $steps[5]['message'] = 'Default admin created (admin@example.com / admin123)';
            } else {
                $steps[5]['status'] = 'success';
                $steps[5]['message'] = 'Default admin already exists';
            }

            // Return success response
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Database setup completed successfully!',
                'steps' => $steps,
                'credentials' => [
                    'email' => 'admin@example.com',
                    'password' => 'admin123',
                    'warning' => 'Please change the default password immediately!'
                ],
                'database_info' => [
                    'host' => $this->host,
                    'database' => $this->dbName
                ]
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database setup failed',
                'error' => $e->getMessage(),
                'steps' => $steps ?? []
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Setup error',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkStatus() {
        try {
            $conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbName}",
                $this->username,
                $this->password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if all tables exist
            $tables = ['admins', 'admissions', 'application_logs'];
            $existingTables = [];
            $missingTables = [];

            foreach ($tables as $table) {
                $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }

            $isInstalled = count($missingTables) === 0;

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'installed' => $isInstalled,
                'message' => $isInstalled ? 'Database is properly set up' : 'Database setup required',
                'tables' => [
                    'existing' => $existingTables,
                    'missing' => $missingTables
                ],
                'database_info' => [
                    'host' => $this->host,
                    'database' => $this->dbName
                ]
            ]);

        } catch (PDOException $e) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'installed' => false,
                'message' => 'Database not found or not accessible',
                'error' => $e->getMessage(),
                'database_info' => [
                    'host' => $this->host,
                    'database' => $this->dbName
                ]
            ]);
        }
    }

    public function reset() {
        try {
            // Security check - only allow in development
            if ($_ENV['APP_ENV'] ?? 'production' !== 'development') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database reset is only allowed in development environment'
                ]);
                return;
            }

            $conn = new PDO(
                "mysql:host={$this->host}",
                $this->username,
                $this->password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Drop database
            $conn->exec("DROP DATABASE IF EXISTS `{$this->dbName}`");

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Database reset successfully. Please run /api/setup/install to reinstall.',
                'warning' => 'All data has been deleted!'
            ]);

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to reset database',
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>