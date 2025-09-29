<?php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../utils/JWT.php';

class AuthController {
    private $db;
    private $admin;
    private $jwt;

    public function __construct($db) {
        $this->db = $db;
        $this->admin = new Admin($db);
        $this->jwt = new JWT();
    }

    public function login() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['email']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                return;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                return;
            }

            $result = $this->admin->login($data['email'], $data['password']);

            if ($result['success']) {
                $payload = [
                    'admin_id' => $result['data']['id'],
                    'email' => $result['data']['email'],
                    'name' => $result['data']['name']
                ];

                $token = $this->jwt->encode($payload);

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'admin' => [
                        'id' => $result['data']['id'],
                        'email' => $result['data']['email'],
                        'name' => $result['data']['name']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function verifyToken() {
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

            if (empty($authHeader)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authorization header missing']);
                return;
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $result = $this->jwt->decode($token);

            if ($result['success']) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Token is valid',
                    'data' => $result['data']
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => $result['error']]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function register() {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                return;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                return;
            }

            $stmt = $this->admin->getByEmail($data['email']);
            if ($stmt->rowCount() > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                return;
            }

            $this->admin->email = htmlspecialchars(strip_tags($data['email']));
            $this->admin->password = $data['password'];
            $this->admin->name = htmlspecialchars(strip_tags($data['name']));

            if ($this->admin->create()) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Admin registered successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to register admin']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }
}
?>