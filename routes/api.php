<?php
// routes/api.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AdmissionController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ExportController.php';
require_once __DIR__ . '/../controllers/SetupController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// ✅ Removed duplicate CORS headers (they now live only in index.php)
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
$route = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
$route = trim($route, '/');

// --------------------
// Setup Routes
// --------------------
$setupController = new SetupController();

if ($route === 'api/setup/install' && $method === 'POST') {
    $setupController->install();
    exit();
}
if ($route === 'api/setup/install' && $method === 'GET') {
    // $setupController->install();
    echo json_encode(['success' => false, 'message' => 'Use POST method to install the database.']);
    exit();
}

if ($route === 'api/setup/status' && $method === 'GET') {
    $setupController->checkStatus();
    exit();
}

if ($route === 'api/setup/reset' && $method === 'POST') {
    $setupController->reset();
    exit();
}

// --------------------
// Database
// --------------------
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please run /api/setup/install first or check your configuration.'
    ]);
    exit();
}

// --------------------
// Controllers & Middleware
// --------------------
$admissionController = new AdmissionController($db);
$authController = new AuthController($db);
$exportController = new ExportController($db);
$authMiddleware = new AuthMiddleware();

// --------------------
// Public Routes
// --------------------
if ($route === 'api/admission' && $method === 'POST') {
    $admissionController->create();
    exit();
}

// --------------------
// Auth Routes
// --------------------
if ($route === 'api/auth/login' && $method === 'POST') {
    $authController->login();
    exit();
}

if ($route === 'api/auth/register' && $method === 'POST') {
    $authController->register();
    exit();
}

if ($route === 'api/auth/verify' && $method === 'GET') {
    $authController->verifyToken();
    exit();
}

// --------------------
// Protected Admin Routes
// --------------------
$protectedRoutes = [
    'api/admin/admissions',
    'api/admin/admission',
    'api/admin/export'
];

$isProtectedRoute = false;
foreach ($protectedRoutes as $protectedRoute) {
    if (strpos($route, $protectedRoute) === 0) {
        $isProtectedRoute = true;
        break;
    }
}

if ($isProtectedRoute) {
    $authMiddleware->authenticate();
}

// Admin Admission Routes
if ($route === 'api/admin/admissions' && $method === 'GET') {
    $admissionController->getAll();
    exit();
}

if (preg_match('/^api\/admin\/admission\/(\d+)$/', $route, $matches) && $method === 'GET') {
    $admissionController->getById($matches[1]);
    exit();
}

if (preg_match('/^api\/admin\/admission\/(\d+)\/status$/', $route, $matches) && $method === 'PUT') {
    $admissionController->updateStatus($matches[1]);
    exit();
}

if (preg_match('/^api\/admin\/admission\/(\d+)$/', $route, $matches) && $method === 'DELETE') {
    $admissionController->delete($matches[1]);
    exit();
}

// Export Routes
if ($route === 'api/admin/export/csv' && $method === 'GET') {
    $exportController->exportCSV();
    exit();
}

if ($route === 'api/admin/export/excel' && $method === 'GET') {
    $exportController->exportExcel();
    exit();
}

if ($route === 'api/admin/export/pdf' && $method === 'GET') {
    $exportController->exportPDF();
    exit();
}

// --------------------
// 404 Not Found
// --------------------
http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Route not found','current_route'=>$route]);
