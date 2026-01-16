<?php
/**
 * Mobile Login API - ResQTech System
 * API สำหรับเข้าสู่ระบบผ่าน Mobile App
 */

require_once __DIR__ . '/../includes/init.php';

// Handle CORS - Allow only localhost origins for mobile app testing
$allowedOrigins = ['http://localhost', 'http://127.0.0.1', 'http://localhost:3000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$corsOrigin = in_array($origin, $allowedOrigins) ? $origin : '';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($corsOrigin) {
        header("Access-Control-Allow-Origin: $corsOrigin");
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

header('Content-Type: application/json');
if ($corsOrigin) {
    header("Access-Control-Allow-Origin: $corsOrigin");
    header("Access-Control-Allow-Credentials: true");
}

// รับข้อมูล JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
    exit;
}

// ตรวจสอบ Brute Force
$loginCheck = checkLoginAttempts();
if (!$loginCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Too many login attempts. Please try again later.',
        'wait_seconds' => $loginCheck['remaining_time']
    ]);
    exit;
}

// ตรวจสอบรหัสผ่าน
if (validateLogin($username, $password)) {
    // Login สำเร็จ
    createLoginSession($username, 'mobile');
    resetLoginAttempts();

    // Note: Do NOT expose session_id to client for security
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user' => [
            'username' => $username
        ]
    ]);
} else {
    // Login ผิดพลาด
    recordFailedAttempt();

    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid username or password'
    ]);
}
