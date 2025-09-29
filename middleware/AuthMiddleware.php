<?php
require_once __DIR__ . '/../utils/JWT.php';

class AuthMiddleware {
    private $jwt;

    public function __construct() {
        $this->jwt = new JWT();
    }

    public function authenticate() {
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

            if (empty($authHeader)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authorization header missing']);
                exit();
            }

            $token = str_replace('Bearer ', '', $authHeader);
            
            if (!$this->jwt->validateToken($token)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
                exit();
            }

            $payload = $this->jwt->getPayload($token);
            return $payload;

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication failed: ' . $e->getMessage()]);
            exit();
        }
    }

    public function optionalAuth() {
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

            if (empty($authHeader)) {
                return null;
            }

            $token = str_replace('Bearer ', '', $authHeader);
            
            if ($this->jwt->validateToken($token)) {
                return $this->jwt->getPayload($token);
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }
}
?>