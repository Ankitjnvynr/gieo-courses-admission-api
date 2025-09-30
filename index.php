<?php

// index.php

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    // Fallback defaults
    $_ENV['DB_HOST'] = getenv('DB_HOST') ?: 'localhost';
    $_ENV['DB_NAME'] = getenv('DB_NAME') ?: 'gieo_admission_db';
    $_ENV['DB_USERNAME'] = getenv('DB_USERNAME') ?: 'root';
    $_ENV['DB_PASSWORD'] = getenv('DB_PASSWORD') ?: '';
    $_ENV['JWT_SECRET'] = getenv('JWT_SECRET') ?: 'your_secret_key_here';
    $_ENV['JWT_ALGORITHM'] = getenv('JWT_ALGORITHM') ?: 'HS256';
    $_ENV['JWT_EXPIRY'] = getenv('JWT_EXPIRY') ?: '3600';
    $_ENV['UPLOAD_DIR'] = getenv('UPLOAD_DIR') ?: 'uploads/';
    $_ENV['MAX_FILE_SIZE'] = getenv('MAX_FILE_SIZE') ?: '2097152';
    $_ENV['ALLOWED_IMAGE_TYPES'] = getenv('ALLOWED_IMAGE_TYPES') ?: 'jpg,jpeg,png';
    $_ENV['BASE_URL'] = getenv('BASE_URL') ?: 'http://localhost';
    $_ENV['CORS_ORIGIN'] = getenv('CORS_ORIGIN') ?: 'http://localhost:3000';
}

// --------------------
// ✅ Handle CORS
// --------------------
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    // add your production frontend domain here
    'https://yourdomain.com',
    'http://localhost',
    'https://bhagwat-five.vercel.app',
    'https://gieogitaeducourses.org',
    'http://www.gieogitaeducourses.org',
    'https://www.gieogitaeducourses.org',
    'https://edu.gieogitaeducourses.org',
    'http://edu.gieogitaeducourses.org',
    'https://www.edu.gieogitaeducourses.org',
    'http://www.edu.gieogitaeducourses.org',
    'https://edu.gieogita.org/',
    'http://edu.gieogita.org/',
    'https://www.edu.gieogita.org/',
    'http://www.edu.gieogita.org/',
    'https://gieogita.org/',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // fallback for dev tools / testing
    header("Access-Control-Allow-Origin: http://localhost:3000");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --------------------
// Route all requests
// --------------------
require_once __DIR__ . '/routes/api.php';

echo "\n"; // Ensure there's a newline at the end of the response
echo "testing the main route";
