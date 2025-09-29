<?php
class JWT {
    private $secret;
    private $algorithm;
    private $expiry;

    public function __construct() {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->algorithm = $_ENV['JWT_ALGORITHM'];
        $this->expiry = $_ENV['JWT_EXPIRY'];
    }

    public function encode($payload) {
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiry;

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            $this->secret,
            true
        );
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function decode($token) {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                throw new Exception('Invalid token format');
            }

            list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

            $signature = hash_hmac(
                'sha256',
                $base64UrlHeader . "." . $base64UrlPayload,
                $this->secret,
                true
            );
            $expectedSignature = $this->base64UrlEncode($signature);

            if (!hash_equals($base64UrlSignature, $expectedSignature)) {
                throw new Exception('Invalid signature');
            }

            $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);

            if (!isset($payload['exp']) || $payload['exp'] < time()) {
                throw new Exception('Token expired');
            }

            return [
                'success' => true,
                'data' => $payload
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function validateToken($token) {
        $result = $this->decode($token);
        return $result['success'];
    }

    public function getPayload($token) {
        $result = $this->decode($token);
        return $result['success'] ? $result['data'] : null;
    }
}
?>